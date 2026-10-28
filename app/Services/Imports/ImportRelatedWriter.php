<?php

namespace App\Services\Imports;

use App\Exceptions\Imports\SheetImportException;
use App\Models\SheetImport;

/**
 * An optional extra a {@see ImportBlueprint} may implement when one sheet row
 * writes more than its own model's row — a parent row plus the children named
 * in one of its cells, say (ARCHITECTURE.md §9.13).
 *
 * **Opt-in, not a method every blueprint must answer**: `ImportRunner` only
 * calls these when the resolved blueprint `instanceof` this interface, and a
 * blueprint that needs nothing here stays untouched.
 */
interface ImportRelatedWriter
{
    /**
     * Field keys that are mappable and reach {@see self::afterWrite()} but name
     * no column on `ImportBlueprint::modelClass()` — the cell a sibling write
     * reads. The runner leaves them out of the upserted row and its
     * update-column list.
     *
     * @return list<string>
     */
    public function virtualFields(): array;

    /**
     * Do the extra write for every row this import actually wrote — after
     * `ImportBlueprint::modelClass()`'s own `upsert()`, inside the same
     * transaction, so throwing here (a {@see SheetImportException})
     * rolls that upsert back too and the import stays `Pending` for another try,
     * exactly as if the row had failed before anything was written.
     *
     * A **skipped** duplicate (`DuplicateAction::Skip`) is not in `$rows` — it
     * was never written, so there is nothing here to do the extra write for.
     *
     * @param  list<array{sheet: string, line: int, values: array<string, mixed>}>  $rows
     */
    public function afterWrite(array $rows, SheetImport $import): void;
}
