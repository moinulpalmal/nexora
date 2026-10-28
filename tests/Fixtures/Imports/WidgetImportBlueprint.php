<?php

namespace Tests\Fixtures\Imports;

use App\Enums\RecordStatus;
use App\Services\Imports\ImportBlueprint;
use App\Services\Imports\ImportField;

/**
 * The all-or-nothing (upsert) blueprint the engine is proved with: a global
 * code/name list, unique on the code, the code trimmed and uppercased the way a
 * form request's `prepareForValidation()` would.
 */
class WidgetImportBlueprint implements ImportBlueprint
{
    public function key(): string
    {
        return 'tests.widgets';
    }

    public function label(): string
    {
        return 'Widgets';
    }

    public function modelClass(): string
    {
        return Widget::class;
    }

    public function isCompanyScoped(): bool
    {
        return false;
    }

    public function scopeField(): ?string
    {
        return null;
    }

    public function fields(): array
    {
        return [
            new ImportField('code', 'Code', required: true, maxLength: 20, transform: self::normaliseCode(...)),
            new ImportField('name', 'Name', required: true, maxLength: 100, transform: fn (mixed $value): mixed => is_string($value) ? trim($value) : $value),
        ];
    }

    public function uniqueField(): string
    {
        return 'code';
    }

    public function normaliseRow(array $values): array
    {
        return $values;
    }

    public function guardedFields(): array
    {
        return [];
    }

    public function defaultAttributes(): array
    {
        return ['status' => RecordStatus::Active->value];
    }

    public static function normaliseCode(mixed $value): mixed
    {
        return is_string($value) ? mb_strtoupper(trim($value)) : $value;
    }
}
