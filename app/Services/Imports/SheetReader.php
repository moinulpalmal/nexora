<?php

namespace App\Services\Imports;

use App\Enums\Imports\SheetFileType;
use App\Exceptions\Imports\SheetImportException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Reads **every worksheet** of a spreadsheet for the mapped-import engine — the
 * application's one `PhpOffice\` consumer (ARCHITECTURE.md §2, §9.13).
 *
 * **Every tab, not just the first.** Tabs in one workbook legitimately carry
 * different header layouts, so each is read on its own terms and mapped
 * separately; reading only `getSheet(0)` would import one tab of four and
 * report success.
 *
 * **`setReadDataOnly(true)`**: there are no merged band headers to honour, so
 * loading styles would cost more than it buys.
 *
 * Row 1 of each tab **is** that tab's header, by contract — this reader does
 * not scan for one. A generic sheet has no anchor text a scan could look for;
 * asking the uploader to put headers on row 1 is the honest trade for that.
 */
class SheetReader
{
    /**
     * Read every worksheet's header and data rows.
     *
     * **Skipped versus refused is the central decision here**, and the rule is
     * whether the tab is plausibly data the uploader meant to import:
     *
     * | Condition | Behaviour | Why |
     * | --- | --- | --- |
     * | Row 1 blank | skipped, with a warning | Unambiguously not data — the Notes or Instructions tab a real workbook carries |
     * | Headers, no data rows | skipped, with a warning | Nothing to import, and a mapping panel for it is noise |
     * | Two identical headers | **refuses the file**, naming the tab | It *is* data, and skipping it silently would leave rows missing |
     * | Wider than `maxColumns` | **refuses the file**, naming the tab | Same reasoning |
     * | Every tab skipped | **refuses the file** | See {@see SheetImportException::noHeaderRow()} and `emptyWorkbook()` |
     *
     * Headers are read *before* the width check so the skip decision cannot
     * depend on width — otherwise a wide Notes tab would refuse a workbook it
     * should merely have been dropped from.
     *
     * `$limits->maxRows` is a **workbook-wide budget** decremented per tab, not
     * a per-tab allowance: the cap exists to stop a runaway blank tail, and twenty tabs of 9,999 rows is exactly
     * that. `maxColumns` is per tab, because a column count describes one
     * sheet's shape and means nothing summed.
     *
     * `file_type` is returned rather than left for the caller to detect again —
     * `ImportRunner::stage()` used to re-open the same file to ask the
     * identical question a second time.
     *
     * @return array{
     *     file_type: SheetFileType,
     *     sheets: list<array{index: int, name: string, headers: list<string>, rows: list<list<string|null>>}>,
     *     skipped: list<array{index: int, name: string, reason: string}>,
     * }
     *
     * @throws SheetImportException
     */
    public function read(string $path, string $originalName, ImportLimits $limits): array
    {
        $type = SheetFileType::detect($path);

        if (! $type instanceof SheetFileType) {
            throw SheetImportException::unsupportedFormat($originalName);
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (Throwable) {
            throw SheetImportException::unsupportedFormat($originalName);
        }

        $worksheets = $spreadsheet->getAllSheets();

        if (count($worksheets) > $limits->maxSheets) {
            throw SheetImportException::tooManySheets(count($worksheets), $limits->maxSheets);
        }

        $sheets = [];
        $skipped = [];
        $rowBudget = $limits->maxRows;

        foreach ($worksheets as $index => $sheet) {
            $name = $sheet->getTitle();

            /*
             * The true column count, compared directly against the limit, so
             * a 500-column paste is reported as 500 rather than as one more
             * than the cap.
             */
            $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

            $headers = $this->readHeaders($sheet, $lastColumn, $name);

            if ($headers === []) {
                $skipped[] = ['index' => $index, 'name' => $name, 'reason' => 'no-header'];

                continue;
            }

            if ($lastColumn > $limits->maxColumns) {
                throw SheetImportException::tooManyColumns($lastColumn, $limits->maxColumns, $name);
            }

            $rows = $this->readRows($sheet, $lastColumn, $rowBudget, $name);

            if ($rows === []) {
                $skipped[] = ['index' => $index, 'name' => $name, 'reason' => 'no-rows'];

                continue;
            }

            $rowBudget -= count($rows);

            $sheets[] = [
                'index' => $index,
                'name' => $name,
                'headers' => $headers,
                'rows' => $rows,
            ];
        }

        if ($sheets === []) {
            /* Every tab skipped: say which fix applies — add headers, or the file is empty. */
            $everySkipLackedHeaders = array_all(
                $skipped,
                static fn (array $entry): bool => $entry['reason'] === 'no-header',
            );

            throw $everySkipLackedHeaders
                ? SheetImportException::noHeaderRow($originalName)
                : SheetImportException::emptyWorkbook($originalName);
        }

        return [
            'file_type' => $type,
            'sheets' => $sheets,
            'skipped' => $skipped,
        ];
    }

    /**
     * Human-readable warnings for the tabs that were skipped.
     *
     * These ride `sheet_imports.payload.warnings`, which `pendingFor()` returns
     * to the mapping step.
     *
     * @param  list<array{index: int, name: string, reason: string}>  $skipped
     * @return list<string>
     */
    public function warningsFor(array $skipped): array
    {
        return array_map(static fn (array $entry): string => match ($entry['reason']) {
            'no-header' => __('Skipped the tab ":sheet" — its first row has no column headers.', ['sheet' => $entry['name']]),
            default => __('Skipped the tab ":sheet" — it has headers but no data rows.', ['sheet' => $entry['name']]),
        }, $skipped);
    }

    /**
     * Guess a field's column from its header text, by normalised equality
     * against the field's label or its key with underscores turned to spaces.
     *
     * Never final — this only pre-fills the mapping step; the uploader
     * confirms or overrides every guess before anything is written. It is run
     * once **per tab**, because tabs may spell the same field differently.
     *
     * @param  list<string>  $headers
     * @param  list<ImportField>  $fields
     * @return array<string, int>
     */
    public function suggestMap(array $headers, array $fields): array
    {
        $normalisedHeaders = array_map(self::normalise(...), $headers);
        $suggestions = [];

        foreach ($fields as $field) {
            $candidates = [self::normalise($field->label), self::normalise(str_replace('_', ' ', $field->key))];

            foreach ($candidates as $candidate) {
                $index = array_search($candidate, $normalisedHeaders, true);

                if ($index !== false) {
                    $suggestions[$field->key] = $index;

                    break;
                }
            }
        }

        return $suggestions;
    }

    /**
     * One tab's header row, or `[]` when row 1 is blank.
     *
     * **Returns `[]` rather than throwing**, which is the change that lets a
     * Notes tab be skipped instead of refusing the workbook. What an empty
     * result *means* is decided by {@see self::read()}, the only place that
     * knows whether any other tab succeeded.
     *
     * @return list<string>
     *
     * @throws SheetImportException
     */
    private function readHeaders(Worksheet $sheet, int $lastColumn, string $sheetName): array
    {
        $headers = [];
        $seen = [];

        for ($column = 1; $column <= $lastColumn; $column++) {
            $value = $sheet->getCell(Coordinate::stringFromColumnIndex($column).'1')->getValue();
            $text = trim((string) (is_scalar($value) ? $value : ''));

            if ($text === '') {
                continue;
            }

            $key = self::normalise($text);

            if (isset($seen[$key])) {
                throw SheetImportException::duplicateHeader($text, $sheetName);
            }

            $seen[$key] = true;
            $headers[] = $text;
        }

        return $headers;
    }

    /**
     * One tab's data rows, drawing on the workbook-wide row budget.
     *
     * @return list<list<string|null>>
     *
     * @throws SheetImportException
     */
    private function readRows(Worksheet $sheet, int $lastColumn, int $rowBudget, string $sheetName): array
    {
        $rows = [];
        $lastRow = $sheet->getHighestDataRow();

        for ($line = 2; $line <= $lastRow; $line++) {
            $row = [];
            $blank = true;

            for ($column = 1; $column <= $lastColumn; $column++) {
                $value = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$line)->getValue();
                $text = is_scalar($value) ? trim((string) $value) : null;
                $text = $text === '' ? null : $text;

                if ($text !== null) {
                    $blank = false;
                }

                $row[] = $text;
            }

            /* A row with nothing in it is the blank tail Excel routinely leaves. */
            if ($blank) {
                continue;
            }

            $rows[] = $row;

            if (count($rows) > $rowBudget) {
                throw SheetImportException::tooManyRows($rowBudget, $sheetName);
            }
        }

        return $rows;
    }

    /**
     * Normalise a header for matching — lowercased, whitespace collapsed,
     * because a source file's header text is typed by hand.
     */
    private static function normalise(string $value): string
    {
        $value = str_replace(["\u{00A0}", "\r", "\n", "\t"], ' ', $value);

        return trim(mb_strtolower(preg_replace('/\s+/u', ' ', $value) ?? ''));
    }
}
