<?php

namespace App\Services\Hrm;

use App\Concerns\MasterDataValidationRules;
use App\Enums\Imports\DuplicateAction;
use App\Enums\RecordStatus;
use App\Models\Hrm\Designation;
use App\Models\SheetImport;
use App\Services\Imports\ImportBlueprint;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportOutcome;
use App\Services\Imports\ImportRowWriter;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Designations from a spreadsheet (ARCHITECTURE.md §9.13) — a row writer, for
 * the reason {@see DepartmentImportBlueprint} is one: two natural keys, and **a
 * row is an existing designation when either the code or the name matches**.
 *
 * - code and name both new → created (status Active unless the sheet says);
 * - code or name matches one designation → skipped or updated, as chosen;
 * - code matches one designation and name another → rejected as a conflict;
 * - the match is a deleted designation → rejected: restore it first;
 * - a name repeated in the file → rejected (the engine catches a repeated
 *   code, `uniqueField()`).
 *
 * Unlike departments, designations are group-wide: no company is read,
 * written or required (§9.10).
 */
class DesignationImportBlueprint implements ImportBlueprint, ImportRowWriter
{
    // For its normalisers — the import fields' transforms are the form's.
    use MasterDataValidationRules;

    public function __construct(private readonly DesignationService $designations) {}

    public function key(): string
    {
        return 'hrm.designations';
    }

    public function label(): string
    {
        return 'Designations';
    }

    public function modelClass(): string
    {
        return Designation::class;
    }

    public function isCompanyScoped(): bool
    {
        return false;
    }

    public function scopeField(): ?string
    {
        return null;
    }

    /**
     * The same normalisers the form request applies, and the same lengths as
     * the migration, so a value imported and typed land identically.
     */
    public function fields(): array
    {
        return [
            new ImportField('code', 'Code', required: true, maxLength: 20, transform: self::normaliseCode(...)),
            new ImportField('name', 'Name', required: true, maxLength: 100, transform: self::normaliseName(...)),
            new ImportField('status', 'Status', transform: DepartmentImportBlueprint::normaliseStatus(...)),
        ];
    }

    public function uniqueField(): string
    {
        return 'code';
    }

    public function normaliseRow(array $values): array
    {
        return $values;
    }

    /**
     * Empty although `name` is unique too: this blueprint writes its own rows
     * and checks names in {@see self::writeRows()}.
     */
    public function guardedFields(): array
    {
        return [];
    }

    public function defaultAttributes(): array
    {
        return ['status' => RecordStatus::Active->value];
    }

    public function confirmRules(): array
    {
        return [];
    }

    public function writeRows(array $rows, DuplicateAction $onDuplicate, array $options, SheetImport $import, bool $dryRun): ImportOutcome
    {
        /*
         * Every designation, deleted ones included, kept current as rows are
         * written (or, on a dry run, would be) — so a later row is judged
         * against the earlier rows' effects, not the starting state.
         */
        $known = [];
        $byCode = [];
        $byName = [];

        foreach (Designation::withTrashed()->get(['id', 'code', 'name', 'deleted_at']) as $designation) {
            $known[$designation->id] = ['code' => $designation->code, 'name' => $designation->name, 'deleted' => $designation->deleted_at !== null];
            $byCode[mb_strtoupper($designation->code)] = $designation->id;
            $byName[mb_strtolower($designation->name)] = $designation->id;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $rejected = [];
        $notes = [];
        $namesInFile = [];
        $provisionalId = -1;

        foreach ($rows as $row) {
            $code = (string) $row['values']['code'];
            $name = (string) $row['values']['name'];
            $status = $row['values']['status'] ?? null;
            $nameKey = mb_strtolower($name);

            $reject = function (string $message) use (&$rejected, $row): void {
                $rejected[] = ['sheet' => $row['sheet'], 'line' => $row['line'], 'message' => $message];
            };

            if ($status !== null && RecordStatus::tryFrom((string) $status) === null) {
                $reject(__('Status must be Active or Inactive.'));

                continue;
            }

            if (isset($namesInFile[$nameKey])) {
                $first = $namesInFile[$nameKey];
                $reject(__('The name ":name" duplicates ":sheet" row :line.', ['name' => $name, 'sheet' => $first['sheet'], 'line' => $first['line']]));

                continue;
            }

            $namesInFile[$nameKey] = ['sheet' => $row['sheet'], 'line' => $row['line']];

            $codeMatch = $byCode[$code] ?? null;
            $nameMatch = $byName[$nameKey] ?? null;

            if ($codeMatch !== null && $nameMatch !== null && $codeMatch !== $nameMatch) {
                $reject(__('The code ":code" belongs to ":codeOwner" but the name ":name" belongs to the designation coded ":nameOwner". Correct one of them.', [
                    'code' => $code,
                    'codeOwner' => $known[$codeMatch]['name'],
                    'name' => $name,
                    'nameOwner' => $known[$nameMatch]['code'],
                ]));

                continue;
            }

            $matchId = $codeMatch ?? $nameMatch;

            if ($matchId !== null && $known[$matchId]['deleted']) {
                $reject(__('Matches the deleted designation ":code — :name". Restore it from the Deleted tab first.', [
                    'code' => $known[$matchId]['code'],
                    'name' => $known[$matchId]['name'],
                ]));

                continue;
            }

            if ($matchId !== null && $onDuplicate === DuplicateAction::Skip) {
                $skipped++;

                continue;
            }

            $attributes = ['code' => $code, 'name' => $name];

            if ($status !== null) {
                $attributes['status'] = (string) $status;
            }

            try {
                if ($matchId === null) {
                    $id = $dryRun
                        ? $provisionalId--
                        : $this->designations->create([
                            'code' => $code,
                            'name' => $name,
                            // A sheet with no Status column starts every row active.
                            'status' => $attributes['status'] ?? RecordStatus::Active->value,
                        ])->id;

                    $created++;
                    $notes[] = __('Will create :code — :name.', ['code' => $code, 'name' => $name]);
                } else {
                    if (! $dryRun) {
                        $this->designations->update(Designation::withTrashed()->findOrFail($matchId), $attributes);
                    }

                    unset($byCode[mb_strtoupper($known[$matchId]['code'])], $byName[mb_strtolower($known[$matchId]['name'])]);

                    $id = $matchId;
                    $updated++;
                }
            } catch (UniqueConstraintViolationException) {
                // Someone saved a clashing designation between the read above
                // and this write; the row is reported, the rest still land.
                $reject(__('Another designation took this code or name while the import ran. Upload the row again.'));

                continue;
            }

            $known[$id] = ['code' => $code, 'name' => $name, 'deleted' => false];
            $byCode[$code] = $id;
            $byName[$nameKey] = $id;
        }

        // Notes are a preview; after a real write the counts say it all.
        return new ImportOutcome($created, $updated, $skipped, $rejected, $dryRun ? $notes : []);
    }
}
