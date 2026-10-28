<?php

namespace App\Services\Imports;

use App\Enums\Imports\DuplicateAction;
use App\Models\SheetImport;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An optional extra a {@see ImportBlueprint} may implement to write its rows
 * itself, one at a time, and to **import the valid rows and report the rest**.
 *
 * **The one exception to the engine's all-or-nothing rule** (ARCHITECTURE.md
 * §9.13), and opt-in for that reason: every blueprint that does not implement
 * this is untouched — still validated as a whole, still one `upsert()`. It is
 * for a table whose rows must each go through its service (audited per row,
 * with side effects) rather than be bulk-upserted, where a forgiving import
 * serves the uploader better than a strict one.
 *
 * When the resolved blueprint `instanceof` this, `ImportRunner::confirm()`:
 * turns each row the per-field checks failed into a rejected row rather than
 * refusing the sheet; hands the rest to {@see self::writeRows()}; and records
 * the rejects on `sheet_imports.rejected_rows`. `ImportRunner::review()` runs
 * the same path with `$dryRun` true and writes nothing — the preview the
 * mapping step shows before Import.
 */
interface ImportRowWriter
{
    /**
     * Extra inputs the confirm step posts beside the mapping — validated by
     * `Imports\ImportConfirmRequest` on confirm, never on review, and handed to
     * {@see self::writeRows()} as `$options`. Never stored on the import.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function confirmRules(): array;

    /**
     * Validate and (unless `$dryRun`) write every row the engine's own per-field
     * checks passed, returning what happened to each.
     *
     * A row this method cannot write is a **rejected** row in the outcome —
     * never an exception — so the rest still land. `notes` carries anything the
     * uploader should see before confirming that is not a rejection
     * ("Will create department …").
     *
     * @param  list<array{sheet: string, line: int, values: array<string, mixed>}>  $rows
     * @param  array<string, mixed>  $options  validated {@see self::confirmRules()} inputs; empty on review
     */
    public function writeRows(array $rows, DuplicateAction $onDuplicate, array $options, SheetImport $import, bool $dryRun): ImportOutcome;
}
