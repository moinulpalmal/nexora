<?php

namespace App\Services\Imports;

use App\Models\SheetImport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The two small workbooks the import dialog hands back: a blank template headed
 * with a blueprint's fields, and a partial import's rejected rows.
 *
 * Module-less for the reason the engine is (ARCHITECTURE.md §4, §9.13).
 * Synchronous, written to a scratch folder under `storage/app` and deleted
 * after sending: neither file is large enough to need a queued export.
 */
class ImportWorkbookWriter
{
    /**
     * A one-row workbook whose headers are the blueprint's field labels, in
     * order — what `SheetReader::suggestMap()` maps on its own.
     *
     * @return string the path written
     */
    public function template(ImportBlueprint $blueprint): string
    {
        return $this->write('template', function (Writer $writer) use ($blueprint): void {
            $writer->getCurrentSheet()->setName(Str::limit($blueprint->label(), 31, ''));
            $writer->addRow(Row::fromValuesWithStyle(
                array_map(static fn (ImportField $field): string => $field->label, $blueprint->fields()),
                new Style(fontBold: true),
            ));
        });
    }

    /**
     * The rows a partial import rejected, rebuilt from `staged_rows` — the
     * original cells, one worksheet per source tab, with a Reason column and
     * the source row number — so they can be fixed and uploaded again.
     *
     * @return string the path written
     */
    public function rejects(SheetImport $import): string
    {
        $byLocation = [];

        foreach ($import->rejected_rows ?? [] as $reject) {
            $byLocation[$reject['sheet']][$reject['line']] = $reject['message'];
        }

        return $this->write('rejects', function (Writer $writer) use ($import, $byLocation): void {
            $first = true;

            foreach ($import->staged_rows['sheets'] ?? [] as $sheet) {
                $reasons = $byLocation[$sheet['name']] ?? [];

                if ($reasons === []) {
                    continue;
                }

                $current = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $current->setName(Str::limit($sheet['name'], 31, ''));
                $first = false;

                $writer->addRow(Row::fromValuesWithStyle([...$sheet['headers'], __('Reason'), __('Source row')], new Style(fontBold: true)));

                foreach ($reasons as $line => $reason) {
                    $cells = $sheet['rows'][$line - 2] ?? [];
                    $cells = array_pad(array_slice($cells, 0, count($sheet['headers'])), count($sheet['headers']), null);

                    $writer->addRow(Row::fromValues([...array_map(static fn (?string $cell): string => $cell ?? '', $cells), $reason, $line]));
                }
            }

            if ($first) {
                $writer->getCurrentSheet()->setName(__('Rejected rows'));
                $writer->addRow(Row::fromValues([__('No rows were rejected.')]));
            }
        });
    }

    /**
     * @param  callable(Writer): void  $fill
     */
    private function write(string $kind, callable $fill): string
    {
        $folder = storage_path('app/'.config('sheet-import.storage.tmp'));
        File::ensureDirectoryExists($folder);
        $this->pruneStale($folder);

        $path = $folder.DIRECTORY_SEPARATOR."import-{$kind}-".Str::uuid().'.xlsx';

        $writer = new Writer(new Options(new Style, SHOULD_USE_INLINE_STRINGS: false, tempFolder: $folder));
        $writer->openToFile($path);

        try {
            $fill($writer);
        } finally {
            $writer->close();
        }

        return $path;
    }

    /**
     * Delete files an interrupted download left behind, older than an hour —
     * `deleteFileAfterSend()` only runs when the response finishes.
     */
    private function pruneStale(string $folder): void
    {
        $cutoff = now()->subHour()->getTimestamp();

        foreach (File::glob($folder.DIRECTORY_SEPARATOR.'import-*.xlsx') as $stale) {
            if (File::lastModified($stale) < $cutoff) {
                File::delete($stale);
            }
        }
    }
}
