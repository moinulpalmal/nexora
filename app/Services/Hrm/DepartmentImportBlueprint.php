<?php

namespace App\Services\Hrm;

use App\Concerns\MasterDataValidationRules;
use App\Enums\Imports\DuplicateAction;
use App\Enums\RecordStatus;
use App\Models\Hrm\Department;
use App\Models\SheetImport;
use App\Services\Imports\ImportBlueprint;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportOutcome;
use App\Services\Imports\ImportRowWriter;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Departments from a spreadsheet (ARCHITECTURE.md §9.13) — the first shipped
 * importer, and a row writer.
 *
 * **Why a row writer.** A department has *two* natural keys, the code and the
 * name, and the owner's rule is that **a row is an existing department when
 * either matches** (2026-10-29). The engine's bulk path knows one
 * `uniqueField()`, so this blueprint matches and writes each row itself,
 * through {@see DepartmentService} so every row is audited like a form save,
 * and reports the rows it cannot take instead of refusing the file:
 *
 * - code and name both new → created (status Active unless the sheet says);
 * - code or name matches one department → that department, skipped or updated
 *   as the uploader chose — an update can rename it or change its code;
 * - code matches one department and name another → rejected as a conflict;
 * - the match is a deleted department → rejected: restore it first, as the
 *   form says (its values stay reserved);
 * - a name repeated in the file → rejected (the engine already catches a
 *   repeated code, `uniqueField()`).
 *
 * Every row lands in the staged import's company — the uploader's selected
 * company when they uploaded (§9.10) — never one read from the sheet.
 */
class DepartmentImportBlueprint implements ImportBlueprint, ImportRowWriter
{
    // For its normalisers — the import fields' transforms are the form's.
    use MasterDataValidationRules;

    public function __construct(private readonly DepartmentService $departments) {}

    public function key(): string
    {
        return 'hrm.departments';
    }

    public function label(): string
    {
        return 'Departments';
    }

    public function modelClass(): string
    {
        return Department::class;
    }

    public function isCompanyScoped(): bool
    {
        return true;
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
            new ImportField('status', 'Status', transform: self::normaliseStatus(...)),
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
     * and checks names in {@see self::writeRows()}, which the engine's
     * guarded-field pass (bulk path only) never reaches.
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
        $companyId = (int) $import->company_id;

        /*
         * What the company holds, deleted departments included, kept current
         * as rows are written (or, on a dry run, would be) — so a later row is
         * judged against the earlier rows' effects, not the starting state.
         */
        $known = [];
        $byCode = [];
        $byName = [];

        $existing = Department::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get(['id', 'code', 'name', 'deleted_at']);

        foreach ($existing as $department) {
            $known[$department->id] = ['code' => $department->code, 'name' => $department->name, 'deleted' => $department->deleted_at !== null];
            $byCode[mb_strtoupper($department->code)] = $department->id;
            $byName[mb_strtolower($department->name)] = $department->id;
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
                $reject(__('The code ":code" belongs to ":codeOwner" but the name ":name" belongs to the department coded ":nameOwner". Correct one of them.', [
                    'code' => $code,
                    'codeOwner' => $known[$codeMatch]['name'],
                    'name' => $name,
                    'nameOwner' => $known[$nameMatch]['code'],
                ]));

                continue;
            }

            $matchId = $codeMatch ?? $nameMatch;

            if ($matchId !== null && $known[$matchId]['deleted']) {
                $reject(__('Matches the deleted department ":code — :name". Restore it from the Deleted tab first.', [
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
                        : $this->departments->create($companyId, [
                            'code' => $code,
                            'name' => $name,
                            // A sheet with no Status column starts every row active.
                            'status' => $attributes['status'] ?? RecordStatus::Active->value,
                        ])->id;

                    $created++;
                    $notes[] = __('Will create :code — :name.', ['code' => $code, 'name' => $name]);
                } else {
                    if (! $dryRun) {
                        $this->departments->update(Department::withoutGlobalScopes()->findOrFail($matchId), $attributes);
                    }

                    unset($byCode[mb_strtoupper($known[$matchId]['code'])], $byName[mb_strtolower($known[$matchId]['name'])]);

                    $id = $matchId;
                    $updated++;
                }
            } catch (UniqueConstraintViolationException) {
                // Someone saved a clashing department between the read above
                // and this write; the row is reported, the rest still land.
                $reject(__('Another department took this code or name while the import ran. Upload the row again.'));

                continue;
            }

            $known[$id] = ['code' => $code, 'name' => $name, 'deleted' => false];
            $byCode[$code] = $id;
            $byName[$nameKey] = $id;
        }

        // Notes are a preview; after a real write the counts say it all.
        return new ImportOutcome($created, $updated, $skipped, $rejected, $dryRun ? $notes : []);
    }

    /**
     * "Active" / "A" / "Inactive" / "I", any case, to the stored letter; blank
     * to null (the default applies). Anything else is kept as typed so
     * {@see self::writeRows()} can name it in the rejection.
     */
    public static function normaliseStatus(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return match (mb_strtolower(trim($value))) {
            'a', 'active' => RecordStatus::Active->value,
            'i', 'inactive' => RecordStatus::Inactive->value,
            default => trim($value),
        };
    }
}
