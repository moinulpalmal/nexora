<?php

namespace App\Http\Requests\Admin;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\Admin\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a country. One request for both verbs, as
 * `Hrm\DesignationRequest`.
 *
 * Countries are group-wide, so the name and both ISO codes are each unique
 * across the whole table, deleted countries included — see
 * {@see MasterDataValidationRules}. The other codes are optional and shared
 * freely (`+1`, `EUR`).
 */
class CountryRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => self::normaliseName($this->input('name')),
            'iso2' => self::normaliseCode($this->input('iso2')),
            'iso3' => self::normaliseCode($this->input('iso3')),
            'numeric_code' => self::blankToNull(self::normaliseName($this->input('numeric_code'))),
            'dial_code' => self::blankToNull(self::normaliseName($this->input('dial_code'))),
            'currency_code' => self::blankToNull(self::normaliseCode($this->input('currency_code'))),
            'nationality' => self::blankToNull(self::normaliseName($this->input('nationality'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $country = $this->route('country');
        $ignore = $country instanceof Country ? $country->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $this->uniqueIgnoringCase(
                    Country::withTrashed(),
                    'name',
                    $ignore,
                    __('A country named ":value" already exists.'),
                    __('A deleted country is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'iso2' => [
                'required',
                'string',
                'regex:/^[A-Z]{2}$/',
                $this->uniqueIgnoringCase(
                    Country::withTrashed(),
                    'iso2',
                    $ignore,
                    __('The ISO code ":value" is already taken.'),
                    __('The ISO code ":value" belongs to a deleted country. Restore it from the Deleted tab instead.'),
                ),
            ],
            'iso3' => [
                'required',
                'string',
                'regex:/^[A-Z]{3}$/',
                $this->uniqueIgnoringCase(
                    Country::withTrashed(),
                    'iso3',
                    $ignore,
                    __('The ISO code ":value" is already taken.'),
                    __('The ISO code ":value" belongs to a deleted country. Restore it from the Deleted tab instead.'),
                ),
            ],
            'numeric_code' => ['nullable', 'string', 'regex:/^\d{3}$/'],
            'dial_code' => ['nullable', 'string', 'max:10', 'regex:/^\+\d{1,4}(?:-\d{1,4})?$/'],
            'currency_code' => ['nullable', 'string', 'regex:/^[A-Z]{3}$/'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'iso2.regex' => __('Use exactly two letters, e.g. "BD".'),
            'iso3.regex' => __('Use exactly three letters, e.g. "BGD".'),
            'numeric_code.regex' => __('Use exactly three digits, e.g. "050".'),
            'dial_code.regex' => __('Start with + and digits, e.g. "+880" or "+1-684".'),
            'currency_code.regex' => __('Use a three-letter currency code, e.g. "BDT".'),
        ];
    }

    /**
     * @return array{name: string, iso2: string, iso3: string, numeric_code: string|null, dial_code: string|null, currency_code: string|null, nationality: string|null, status: string}
     */
    public function countryAttributes(): array
    {
        return [
            'name' => $this->string('name')->value(),
            'iso2' => $this->string('iso2')->value(),
            'iso3' => $this->string('iso3')->value(),
            'numeric_code' => $this->input('numeric_code'),
            'dial_code' => $this->input('dial_code'),
            'currency_code' => $this->input('currency_code'),
            'nationality' => $this->input('nationality'),
            'status' => $this->string('status')->value(),
        ];
    }

    /**
     * An emptied optional field is stored as null, not `''`.
     */
    private static function blankToNull(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
