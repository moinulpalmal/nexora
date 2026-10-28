<?php

namespace App\Services\Imports;

use App\Exceptions\Imports\SheetImportException;

/**
 * The uploader's chosen mapping for a whole workbook — one {@see ColumnMap} per
 * worksheet they chose to import.
 *
 * **Keyed by the workbook's own worksheet index, never by position in the
 * staged list.** Skipped tabs leave gaps in that list and deselecting one
 * leaves a gap here, so a positional key would silently shift a mapping onto
 * the wrong tab. The index is what the dialog posts (`map[0][code]`),
 * what `sheet_imports.column_map` stores, and what every error message resolves
 * a tab name from.
 *
 * **A tab the uploader deselected is simply absent**, which is the whole
 * mechanism for opting out of one: there is no "enabled" flag to keep in step
 * with the presence of a mapping.
 *
 * `ColumnMap` is composed rather than replaced. It remains exactly what it
 * always was — one tab's field-to-column assignment — and this is the
 * collection of them, so the per-sheet logic and its tests were not disturbed
 * by the workbook becoming plural.
 */
final readonly class WorkbookMap
{
    /**
     * @param  array<int, ColumnMap>  $sheets  workbook sheet index => that tab's map
     */
    public function __construct(
        private array $sheets,
    ) {}

    /**
     * Build from the posted `map` input — `map[sheetIndex][fieldKey] = column`.
     *
     * A field left unmapped arrives as `null` or `''` and is dropped, matching
     * what the per-model confirm requests used to do individually: an absent
     * assignment means "unmapped", and whether that is allowed is
     * {@see self::validate()}'s question, not this one's.
     *
     * A tab whose every field is blank is dropped entirely rather than kept as
     * an empty map — an uploader who cleared every combobox on a panel has
     * deselected it in every sense that matters.
     *
     * @param  array<array-key, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $sheets = [];

        foreach ($input as $sheetIndex => $assignments) {
            if (! is_array($assignments)) {
                continue;
            }

            $filtered = array_filter(
                $assignments,
                static fn (mixed $value): bool => $value !== null && $value !== '',
            );

            if ($filtered === []) {
                continue;
            }

            $sheets[(int) $sheetIndex] = new ColumnMap(array_map(intval(...), $filtered));
        }

        ksort($sheets);

        return new self($sheets);
    }

    /**
     * The chosen worksheet indexes, ascending.
     *
     * Ascending so a workbook's tabs are read, written and reported in the
     * order they appear in Excel — an error naming "row 4" on the third tab is
     * easier to act on when the tabs were processed left to right.
     *
     * @return list<int>
     */
    public function sheetIndexes(): array
    {
        return array_keys($this->sheets);
    }

    public function forSheet(int $sheetIndex): ?ColumnMap
    {
        return $this->sheets[$sheetIndex] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->sheets === [];
    }

    /**
     * Refuse a mapping that names no tab, names a tab that was never staged, or
     * leaves a required field unmapped on a tab it does name.
     *
     * @param  list<ImportField>  $fields
     * @param  array<int, string>  $sheetNames  worksheet index => tab title, for every staged tab
     *
     * @throws SheetImportException
     */
    public function validate(array $fields, array $sheetNames): void
    {
        if ($this->isEmpty()) {
            throw SheetImportException::noSheetsSelected();
        }

        foreach ($this->sheets as $sheetIndex => $map) {
            if (! isset($sheetNames[$sheetIndex])) {
                throw SheetImportException::unknownSheet($sheetIndex);
            }

            $map->validate($fields, $sheetNames[$sheetIndex]);
        }
    }

    /**
     * The shape stored on `sheet_imports.column_map`.
     *
     * This is audited rather than excluded — it is the decision the trail
     * exists to record (ARCHITECTURE.md §9.3, §9.13), and with several tabs it
     * now records which of them were imported as well as how each was mapped.
     *
     * @return array<int, array<string, int>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (ColumnMap $map): array => $map->toArray(),
            $this->sheets,
        );
    }
}
