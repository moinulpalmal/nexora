<?php

namespace App\Http\Requests\Hrm;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\Hrm\Designation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a designation. One request for both verbs, as
 * `Admin\CompanyRequest`.
 *
 * Designations are group-wide (ARCHITECTURE.md §9.10), so the code and the
 * name are each unique across the whole table, deleted designations included
 * — see {@see MasterDataValidationRules}.
 */
class DesignationRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => self::normaliseCode($this->input('code')),
            'name' => self::normaliseName($this->input('name')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $designation = $this->route('designation');
        $ignore = $designation instanceof Designation ? $designation->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    Designation::withTrashed(),
                    'code',
                    $ignore,
                    __('The code ":value" is already taken.'),
                    __('The code ":value" belongs to a deleted designation. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                $this->uniqueIgnoringCase(
                    Designation::withTrashed(),
                    'name',
                    $ignore,
                    __('A designation named ":value" already exists.'),
                    __('A deleted designation is named ":value". Restore it from the Deleted tab instead.'),
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
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "SR-OP".'),
        ];
    }

    /**
     * @return array{code: string, name: string, status: string}
     */
    public function designationAttributes(): array
    {
        return [
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'status' => $this->string('status')->value(),
        ];
    }
}
