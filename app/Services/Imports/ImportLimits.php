<?php

namespace App\Services\Imports;

/**
 * The caps {@see SheetReader} reads a workbook under.
 *
 * `final readonly`. It exists so the reader takes one
 * argument that cannot be assembled wrongly, rather than a run of bare `int`s
 * whose order is a silent correctness question — `read($path, $name, 100, 20)`
 * and `read($path, $name, 20, 100)` are both valid PHP and only one is right.
 *
 * **`max_file_size_kb` is not here.** It is enforced by the form request before
 * any work happens, never by the reader, and it stays in
 * `config/sheet-import.php` because PHP's own `upload_max_filesize` bounds it —
 * see the `import_limits` migration for why that makes it unfit for the screen.
 */
final readonly class ImportLimits
{
    public function __construct(
        public int $maxRows,
        public int $maxColumns,
        public int $maxSheets,
    ) {}

    /**
     * The compiled defaults, used when no `import_limits` row exists.
     *
     * The migration inserts that row, so this is the answer to "the table is
     * there but empty", which happens in a half-migrated database and in any
     * test that truncates. It reads the same config keys these values lived in
     * before the screen existed, so the fallback and the original behaviour are
     * the same numbers rather than a second set to keep in step.
     */
    public static function fromConfig(): self
    {
        return new self(
            (int) config('sheet-import.limits.max_rows'),
            (int) config('sheet-import.limits.max_columns'),
            (int) config('sheet-import.limits.max_sheets'),
        );
    }

    /**
     * @return array{max_rows: int, max_columns: int, max_sheets: int}
     */
    public function toArray(): array
    {
        return [
            'max_rows' => $this->maxRows,
            'max_columns' => $this->maxColumns,
            'max_sheets' => $this->maxSheets,
        ];
    }
}
