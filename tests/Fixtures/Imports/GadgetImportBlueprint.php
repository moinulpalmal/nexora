<?php

namespace Tests\Fixtures\Imports;

use App\Enums\Imports\DuplicateAction;
use App\Enums\RecordStatus;
use App\Models\SheetImport;
use App\Services\Imports\ImportOutcome;
use App\Services\Imports\ImportRowWriter;

/**
 * The row-writer blueprint the partial-import path is proved with: the widget
 * fields, written one row at a time, where a name containing "REJECT" is a row
 * the blueprint itself refuses — the stand-in for a service-level rule that only
 * a per-row write can apply. Writes into the same table as
 * {@see WidgetImportBlueprint}.
 */
class GadgetImportBlueprint extends WidgetImportBlueprint implements ImportRowWriter
{
    /**
     * The validated confirm options the last real (not dry-run) write received.
     *
     * @var array<string, mixed>|null
     */
    public static ?array $lastOptions = null;

    public function key(): string
    {
        return 'tests.gadgets';
    }

    public function label(): string
    {
        return 'Gadgets';
    }

    public function confirmRules(): array
    {
        return ['remark' => ['required', 'string', 'max:50']];
    }

    public function writeRows(array $rows, DuplicateAction $onDuplicate, array $options, SheetImport $import, bool $dryRun): ImportOutcome
    {
        if (! $dryRun) {
            self::$lastOptions = $options;
        }

        $existing = Widget::query()->pluck('code')->all();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $rejected = [];
        $notes = [];

        foreach ($rows as $row) {
            $values = $row['values'];

            if (str_contains((string) $values['name'], 'REJECT')) {
                $rejected[] = ['sheet' => $row['sheet'], 'line' => $row['line'], 'message' => 'The name is refused.'];

                continue;
            }

            $isExisting = in_array($values['code'], $existing, true);

            if ($isExisting && $onDuplicate === DuplicateAction::Skip) {
                $skipped++;

                continue;
            }

            $isExisting ? $updated++ : $created++;

            if ($dryRun) {
                $notes[] = "Will write gadget {$values['code']}.";

                continue;
            }

            Widget::query()->updateOrCreate(
                ['code' => $values['code']],
                ['name' => $values['name'], 'status' => RecordStatus::Active->value],
            );
        }

        return new ImportOutcome($created, $updated, $skipped, $rejected, $notes);
    }
}
