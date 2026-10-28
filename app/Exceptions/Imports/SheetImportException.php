<?php

namespace App\Exceptions\Imports;

use Exception;

/**
 * A spreadsheet that cannot be imported at all, or a mapping that cannot be
 * confirmed at all.
 *
 * One type rather than a hierarchy: there is exactly one `catch`, in
 * `Imports\SheetImportController`,
 * turning any of these into an `error` toast (ARCHITECTURE.md §8.8 — refused by a
 * rule no amount of work by the actor lifts).
 *
 * **Every message names the thing to fix** — the blank header row, the duplicated
 * header, the unmapped required field, the tab, the row and the reason — because
 * it is read by whoever is holding the spreadsheet, not by a developer.
 *
 * **Since a workbook may carry several tabs, a message that could apply to any of
 * them names the one it means.** "Row 7 is invalid" is unanswerable when the file
 * has four tabs; `"Kids" row 7` is not.
 */
class SheetImportException extends Exception
{
    /**
     * The upload is neither an `.xlsx` nor an `.xls`, whatever it is named.
     */
    public static function unsupportedFormat(string $fileName): self
    {
        return new self(__(
            ':file is not a spreadsheet. Upload an .xlsx or .xls file.',
            ['file' => $fileName],
        ));
    }

    /**
     * No tab in the workbook has a header row, so there is nothing to map.
     *
     * The reader does not scan for a header: it has no anchor text to look
     * for, so row 1 of each tab **is** its header, by contract.
     *
     * A workbook with *some* headerless tabs is not this: those tabs are
     * skipped with a warning and the rest import, because a file of data plus a
     * Notes tab is the ordinary shape people send. This fires only when every
     * tab was skipped, which for a single-sheet workbook is the same condition
     * this exception has always described.
     */
    public static function noHeaderRow(string $fileName): self
    {
        return new self(__(
            'The first row of :file has no column headers. Add a header row and upload it again.',
            ['file' => $fileName],
        ));
    }

    /**
     * Every tab had a header row but none had any data beneath it.
     *
     * Distinct from {@see self::noHeaderRow()} because the fix is different —
     * that one says "add headers", this one says "the file is empty" — and
     * telling someone to add a header row they already have is the kind of
     * message that costs a support call.
     */
    public static function emptyWorkbook(string $fileName): self
    {
        return new self(__(
            ':file has column headers but no data rows. Nothing was imported.',
            ['file' => $fileName],
        ));
    }

    /**
     * Two columns on one tab share the same header text, which makes a mapping
     * ambiguous.
     *
     * **Refused, not skipped**, unlike a headerless tab: this is data the
     * uploader meant to import, and quietly dropping the tab would import two
     * thirds of a file and leave the rest missing with no error.
     */
    public static function duplicateHeader(string $header, ?string $sheetName = null): self
    {
        if ($sheetName === null) {
            return new self(__(
                'The column ":header" appears more than once. Each column needs its own heading.',
                ['header' => $header],
            ));
        }

        return new self(__(
            'The column ":header" appears more than once on the tab ":sheet". Each column needs its own heading.',
            ['header' => $header, 'sheet' => $sheetName],
        ));
    }

    /**
     * The workbook holds more data rows, in total across every tab, than one
     * import is allowed to read.
     *
     * **The budget is workbook-wide, not per tab.** A row cap is for stopping
     * a workbook with a runaway blank tail, not rationing legitimate work — and
     * a twenty-tab file of 9,999 rows each is exactly that runaway. The tab named is where the budget ran out,
     * which is the one a person can act on.
     */
    public static function tooManyRows(int $limit, string $sheetName): self
    {
        return new self(__(
            'The tab ":sheet" takes this workbook past :limit rows, which is the limit. Split it into smaller files.',
            ['limit' => $limit, 'sheet' => $sheetName],
        ));
    }

    /**
     * One tab is wider than an import is allowed to read.
     *
     * Per tab rather than workbook-wide, because a column count describes one
     * sheet's shape and means nothing summed across tabs.
     */
    public static function tooManyColumns(int $found, int $limit, ?string $sheetName = null): self
    {
        if ($sheetName === null) {
            return new self(__(
                'This sheet holds :found columns and the limit is :limit.',
                ['found' => $found, 'limit' => $limit],
            ));
        }

        return new self(__(
            'The tab ":sheet" holds :found columns and the limit is :limit.',
            ['found' => $found, 'limit' => $limit, 'sheet' => $sheetName],
        ));
    }

    /**
     * The workbook carries more tabs than an import is allowed to open.
     */
    public static function tooManySheets(int $found, int $limit): self
    {
        return new self(__(
            'This workbook holds :found worksheets and the limit is :limit.',
            ['found' => $found, 'limit' => $limit],
        ));
    }

    /**
     * A required field was left unmapped when the mapping was confirmed.
     */
    public static function missingRequiredField(string $label): self
    {
        return new self(__(
            ':field is required and was not mapped to any column.',
            ['field' => $label],
        ));
    }

    /**
     * As {@see self::missingRequiredField()}, but naming the tab whose mapping
     * is incomplete — with several tabs mapped at once, "Name is required"
     * does not say which panel to go back to.
     */
    public static function missingRequiredFieldOnSheet(string $label, string $sheetName): self
    {
        return new self(__(
            ':field is required and was not mapped to any column on the tab ":sheet".',
            ['field' => $label, 'sheet' => $sheetName],
        ));
    }

    /**
     * The confirmed mapping names a tab that was never staged.
     *
     * Only reachable by a hand-built request — the dialog can only offer tabs
     * the server staged — so the message is short and does not try to help.
     */
    public static function unknownSheet(int $sheetIndex): self
    {
        return new self(__(
            'Worksheet :index is not part of this upload.',
            ['index' => $sheetIndex],
        ));
    }

    /**
     * A company-scoped import was staged with no company to write into.
     *
     * Reached only when the uploader has no selected company — the controller
     * resolves it server-side and never trusts one posted from the browser
     * (ARCHITECTURE.md §9.10).
     */
    public static function noCompany(): self
    {
        return new self(__('Select a company before importing. Every row of this import belongs to one.'));
    }

    /**
     * No tab was selected for import.
     */
    public static function noSheetsSelected(): self
    {
        return new self(__('Choose at least one worksheet to import.'));
    }

    /**
     * The sheet failed validation, refused whole rather than partly written.
     *
     * **All-or-nothing across every selected tab**, and the message lists what
     * it can rather than pointing at a screen that does not exist: up to five
     * rows are named inline and the rest are counted.
     * Nothing is written either way; the sheet is fixed and re-uploaded.
     *
     * Row numbers are **tab-local**: the Excel row on the tab named, which is
     * what the person holding the file needs in order to go and look at it.
     *
     * @param  list<array{sheet: string, line: int, message: string}>  $errors
     */
    public static function invalidRows(array $errors): self
    {
        $shown = array_slice($errors, 0, 5);
        $lines = array_map(
            static fn (array $error): string => __('":sheet" row :line (:message)', $error),
            $shown,
        );
        $remaining = count($errors) - count($shown);

        $summary = implode(', ', $lines);

        if ($remaining > 0) {
            $summary .= ' '.__('and :count more', ['count' => $remaining]);
        }

        return new self(__(
            'This workbook has :count row(s) that cannot be imported: :summary. Fix the sheet and upload it again. Nothing was imported.',
            ['count' => count($errors), 'summary' => $summary],
        ));
    }
}
