import type { DuplicateAction } from '@/lib/enums';

/**
 * The mapped-import engine's own types — module-less, like
 * `app/Services/Imports/` on the backend (ARCHITECTURE.md §9.13). A blueprint's
 * per-model types (`DepartmentListItem`, …) stay in their own module file; these
 * describe the mapping dialog every blueprint shares.
 */

/** A field the mapping step offers a column for — `App\Services\Imports\ImportField::toArray()`. */
export type ImportField = {
    key: string;
    label: string;
    required: boolean;
};

/**
 * What to do with a sheet row whose unique key already exists —
 * `App\Enums\Imports\DuplicateAction`, via `@/lib/enums` (ARCHITECTURE.md §8.2).
 */
export type { DuplicateAction };

/** One choice, as the mapping step's radio group renders it. */
export type DuplicateActionOption = {
    value: DuplicateAction;
    label: string;
};

/**
 * One staged worksheet — `App\Services\Imports\ImportPreview`.
 *
 * The mapping step renders a panel per entry, because tabs in one workbook
 * legitimately carry different header layouts and so are mapped separately.
 */
export type SheetImportSheet = {
    /**
     * The **workbook's own** zero-based worksheet index, not the position in
     * `sheets`. Auto-skipped tabs leave gaps, so this is what the posted map is
     * keyed by (`map[index][fieldKey]`) and what every server-side error
     * resolves a tab name from. Never use the array position.
     */
    index: number;
    /** The tab's title, as the uploader sees it in Excel. */
    name: string;
    headers: string[];
    /** field key => column index, guessed by header text. Overridable, never final. */
    suggested_map: Record<string, number>;
    sample_rows: (string | null)[][];
    row_count: number;
};

/**
 * A spreadsheet import of the signed-in user's, staged and waiting on a
 * confirmed mapping.
 *
 * The full workbook stays on the server — this carries only what the mapping
 * step needs, per tab: that tab's own headers, a suggested field → column
 * mapping, and a few sample rows to show beside each field so the uploader can
 * see what they are mapping without opening the file again.
 */
export type SheetImportPending = {
    id: number;
    source_file_name: string;
    /** Every data row across every staged tab. */
    row_count: number;
    /** One per worksheet that was staged; never empty. */
    sheets: SheetImportSheet[];
    /**
     * Sentences about the tabs that were *not* staged — a Notes tab with no
     * header row, or one with headers and no data. Distinct from a tab the
     * uploader deselects, which the server never hears about.
     */
    warnings: string[];
};

/** One row a partial import turned away — `sheet_imports.rejected_rows[]`. */
export type ImportReject = {
    sheet: string;
    /** The Excel row on that tab, header included. */
    line: number;
    message: string;
};

/**
 * What Import would do — the dry run an `Imports\ImportRowWriter` blueprint's
 * mapping step shows before Import (`UserImportController::review()`).
 * `rejects` is capped server-side; `rejected` is the full count.
 */
export type ImportReview = {
    created: number;
    updated: number;
    skipped: number;
    rejected: number;
    rejects: ImportReject[];
    /** "Will create department: …" and the like. */
    notes: string[];
};

/**
 * A partial import's outcome, flashed as `importResult` by
 * `Imports\SheetImportController::confirm()` when any row was rejected.
 */
export type ImportResult = {
    id: number;
    file: string;
    created: number;
    updated: number;
    skipped: number;
    rejected: number;
    rejects: ImportReject[];
};
