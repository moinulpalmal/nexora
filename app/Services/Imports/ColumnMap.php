<?php

namespace App\Services\Imports;

use App\Exceptions\Imports\SheetImportException;

/**
 * The uploader's chosen mapping from a blueprint's fields to a sheet's columns.
 *
 * Built once, on confirm, from the posted `field key => column index` pairs —
 * the same shape `column_map` is stored as on `sheet_imports`. A field the
 * uploader left unmapped is simply absent from `$assignments`; whether that is
 * allowed depends on the field being required, which {@see self::validate()}
 * checks against the blueprint rather than assuming every field is.
 */
final readonly class ColumnMap
{
    /**
     * @param  array<string, int>  $assignments  field key => zero-based column index
     */
    public function __construct(
        private array $assignments,
    ) {}

    /**
     * The column index mapped to a field, or `null` when it was left unmapped.
     */
    public function columnFor(string $fieldKey): ?int
    {
        return $this->assignments[$fieldKey] ?? null;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return $this->assignments;
    }

    /**
     * Refuse a mapping that leaves a required field unmapped.
     *
     * `$sheetName` is optional rather than required so this class stays usable
     * on its own — a single map over a single tab is still a meaningful thing
     * to validate, and every existing test of it goes on passing unchanged.
     * {@see WorkbookMap::validate()} passes the name, because with several tabs
     * mapped at once "Name is required" does not say which panel to go
     * back to.
     *
     * @param  list<ImportField>  $fields
     *
     * @throws SheetImportException
     */
    public function validate(array $fields, ?string $sheetName = null): void
    {
        foreach ($fields as $field) {
            if ($field->required && $this->columnFor($field->key) === null) {
                throw $sheetName === null
                    ? SheetImportException::missingRequiredField($field->label)
                    : SheetImportException::missingRequiredFieldOnSheet($field->label, $sheetName);
            }
        }
    }
}
