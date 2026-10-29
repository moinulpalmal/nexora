<?php

namespace App\Http\Requests\Hrm;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\Hrm\Department;
use App\Services\Admin\SelectedCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a department. One request for both verbs, as
 * `Admin\CompanyRequest`.
 *
 * **There is no `company_id` field** (ARCHITECTURE.md §9.10): the department
 * belongs to the actor's selected company, which `DepartmentService` stamps.
 * A browser-sent `company_id` is ignored, not validated. The code and the name
 * are each unique *within that company*, deleted departments included — the
 * same values in another company are legal, and the messages say "in this
 * company" so that does not read as a bug.
 */
class DepartmentRequest extends FormRequest
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
        $department = $this->route('department');
        $ignore = $department instanceof Department ? $department->id : null;

        // `withoutGlobalScopes()` drops the soft-delete scope too, so deleted
        // departments are counted, and the company is named explicitly.
        $siblings = Department::withoutGlobalScopes()->where('company_id', app(SelectedCompany::class)->id() ?? 0);

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    clone $siblings,
                    'code',
                    $ignore,
                    __('The code ":value" is already taken in this company.'),
                    __('The code ":value" belongs to a deleted department in this company. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                $this->uniqueIgnoringCase(
                    clone $siblings,
                    'name',
                    $ignore,
                    __('A department named ":value" already exists in this company.'),
                    __('A deleted department in this company is named ":value". Restore it from the Deleted tab instead.'),
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
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "HR" or "PROD-1".'),
        ];
    }

    /**
     * @return array{code: string, name: string, status: string}
     */
    public function departmentAttributes(): array
    {
        return [
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'status' => $this->string('status')->value(),
        ];
    }
}
