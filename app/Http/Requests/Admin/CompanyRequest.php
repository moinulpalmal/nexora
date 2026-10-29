<?php

namespace App\Http\Requests\Admin;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a company. One request for both verbs, as
 * {@see RoleRequest}: the rules differ only by the row the unique checks ignore.
 *
 * The name and the short code are each unique across every company, deleted
 * ones included (ARCHITECTURE.md §9.10) — see {@see MasterDataValidationRules}.
 */
class CompanyRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => self::normaliseName($this->input('name')),
            'short_code' => self::normaliseCode($this->input('short_code')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $company = $this->route('company');
        $ignore = $company instanceof Company ? $company->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                $this->uniqueIgnoringCase(
                    Company::withTrashed(),
                    'name',
                    $ignore,
                    __('A company named ":value" already exists.'),
                    __('A deleted company is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'short_code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    Company::withTrashed(),
                    'short_code',
                    $ignore,
                    __('The short code ":value" is already taken.'),
                    __('The short code ":value" belongs to a deleted company. Restore it from the Deleted tab instead.'),
                ),
            ],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'short_code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "ATL".'),
        ];
    }

    /**
     * @return array{name: string, short_code: string, status: string}
     */
    public function companyAttributes(): array
    {
        return [
            'name' => $this->string('name')->value(),
            'short_code' => $this->string('short_code')->value(),
            'status' => $this->string('status')->value(),
        ];
    }
}
