<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The uploaded spreadsheet is kept so an import can always be read back
    | against the file it came from. With `retain_original` off,
    | `sheet_imports.stored_path` is null and the structured rows staged in
    | `staged_rows` are all that survives.
    |
    | The `local` disk (storage/app/private): an uploaded workbook is never
    | served back over HTTP, so it has no business on the `public` disk.
    |
    */

    'storage' => [
        'disk' => env('SHEET_IMPORT_DISK', 'local'),
        'upload' => 'sheet-imports',
        'retain_original' => env('SHEET_IMPORT_RETAIN', true),
        /* Under storage/app: the template and rejected-rows downloads, deleted after sending. */
        'tmp' => 'sheet-imports/tmp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | Reading runs inside the upload request. `max_file_size_kb` is enforced
    | by each importer's upload request before any work happens; `max_rows`,
    | `max_columns` and `max_sheets` are enforced by the reader, because none
    | of them is knowable until the sheet is open.
    |
    | **Three of these four are defaults, not the live values.** `max_rows`,
    | `max_columns` and `max_sheets` are administered on Application Settings →
    | Import Limits and read from the `import_limits` row (ARCHITECTURE.md
    | §9.13); the keys below are what `ImportLimits::fromConfig()` falls back to
    | until that row has been saved once.
    |
    | **`max_file_size_kb` stays authoritative here.** It is bounded by PHP's
    | own `upload_max_filesize` and `post_max_size`, which no screen can raise,
    | so exposing it would let an administrator set a number PHP then refuses
    | to honour. Do not move it.
    |
    */

    'limits' => [
        'max_file_size_kb' => env('SHEET_IMPORT_MAX_KB', 10240),
        'max_rows' => env('SHEET_IMPORT_MAX_ROWS', 10000),
        'max_columns' => env('SHEET_IMPORT_MAX_COLUMNS', 100),
        'max_sheets' => env('SHEET_IMPORT_MAX_SHEETS', 20),
    ],

];
