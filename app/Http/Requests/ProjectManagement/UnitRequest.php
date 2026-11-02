<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a unit of measure. One request for both verbs, as
 * `TenderTypeRequest`.
 *
 * Units are group-wide (ARCHITECTURE.md §9.10), so the code and the name are
 * each unique across the whole table, deleted units included — see
 * {@see MasterDataValidationRules}. The symbol is only trimmed, never
 * re-cased (`kg`, `m²`), and may repeat: two units can print the same way.
 */
class UnitRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $symbol = $this->input('symbol');

        $this->merge([
            'code' => self::normaliseCode($this->input('code')),
            'name' => self::normaliseName($this->input('name')),
            'symbol' => is_string($symbol) ? trim($symbol) : $symbol,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');
        $ignore = $unit instanceof Unit ? $unit->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    Unit::withTrashed(),
                    'code',
                    $ignore,
                    __('The code ":value" is already taken.'),
                    __('The code ":value" belongs to a deleted unit. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                $this->uniqueIgnoringCase(
                    Unit::withTrashed(),
                    'name',
                    $ignore,
                    __('A unit named ":value" already exists.'),
                    __('A deleted unit is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'symbol' => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['required', 'integer', 'between:0,6'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "KG".'),
        ];
    }

    /**
     * @return array{code: string, name: string, symbol: string|null, decimal_places: int, description: string|null, status: string}
     */
    public function unitAttributes(): array
    {
        return [
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'symbol' => $this->filled('symbol') ? $this->string('symbol')->value() : null,
            'decimal_places' => $this->integer('decimal_places'),
            'description' => $this->filled('description') ? $this->string('description')->trim()->value() : null,
            'status' => $this->string('status')->value(),
        ];
    }
}
