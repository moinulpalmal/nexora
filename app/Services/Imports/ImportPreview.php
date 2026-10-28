<?php

namespace App\Services\Imports;

/**
 * One worksheet as the mapping step sees it: the tab's own headers, a suggested
 * mapping guessed from them, and enough rows to show a preview — never the
 * whole tab, which is why {@see self::sampleRows} is capped.
 *
 * `final readonly` with a `toArray()`. It crosses two boundaries — into
 * `sheet_imports.payload.sheets[]` and into the mapping step's props — so its
 * keys are a contract in both directions. There is one per staged tab, and
 * {@see ImportRunner::stage()} maps them into `payload`.
 *
 * `sheetIndex` is the **workbook's own** zero-based worksheet index, not the
 * position in the staged list. Skipped tabs leave gaps, so the array position
 * shifts while this does not — and it is what the posted column map, the stored
 * `column_map` and every error message key on.
 */
final readonly class ImportPreview
{
    /**
     * @param  int  $sheetIndex  The workbook's own worksheet index.
     * @param  string  $sheetName  The tab's title, as the uploader sees it in Excel.
     * @param  list<string>  $headers  The tab's own column headers, in column order.
     * @param  array<string, int>  $suggestedMap  field key => column index, guessed by
     *                                            normalised header text. Never final —
     *                                            the uploader confirms or overrides it.
     * @param  list<list<string|null>>  $sampleRows  The first few data rows, for the
     *                                               mapping step to show beside each column.
     * @param  int  $rowCount  Every data row the tab holds, not just the sample.
     */
    public function __construct(
        public int $sheetIndex,
        public string $sheetName,
        public array $headers,
        public array $suggestedMap,
        public array $sampleRows,
        public int $rowCount,
    ) {}

    /**
     * Build one from a {@see SheetReader} staged sheet.
     *
     * @param  array{index: int, name: string, headers: list<string>, rows: list<list<string|null>>}  $sheet
     * @param  array<string, int>  $suggestedMap
     */
    public static function fromSheet(array $sheet, array $suggestedMap, int $sampleRows): self
    {
        return new self(
            $sheet['index'],
            $sheet['name'],
            $sheet['headers'],
            $suggestedMap,
            array_slice($sheet['rows'], 0, $sampleRows),
            count($sheet['rows']),
        );
    }

    /**
     * @return array{index: int, name: string, headers: list<string>, suggested_map: array<string, int>, sample_rows: list<list<string|null>>, row_count: int}
     */
    public function toArray(): array
    {
        return [
            'index' => $this->sheetIndex,
            'name' => $this->sheetName,
            'headers' => $this->headers,
            'suggested_map' => $this->suggestedMap,
            'sample_rows' => $this->sampleRows,
            'row_count' => $this->rowCount,
        ];
    }
}
