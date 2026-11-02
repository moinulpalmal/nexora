<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\CertificationCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a certification category. One request for both verbs, as
 * {@see TenderTypeRequest}.
 *
 * Certification categories are group-wide (ARCHITECTURE.md §9.10), so the code and
 * the name are each unique across the whole table, deleted categories
 * included — see {@see MasterDataValidationRules}.
 */
class CertificationCategoryRequest extends FormRequest
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
        $category = $this->route('certification_category');
        $ignore = $category instanceof CertificationCategory ? $category->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    CertificationCategory::withTrashed(),
                    'code',
                    $ignore,
                    __('The code ":value" is already taken.'),
                    __('The code ":value" belongs to a deleted certification category. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                $this->uniqueIgnoringCase(
                    CertificationCategory::withTrashed(),
                    'name',
                    $ignore,
                    __('A certification category named ":value" already exists.'),
                    __('A deleted certification category is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "MAT".'),
        ];
    }

    /**
     * @return array{code: string, name: string, description: string|null, status: string}
     */
    public function certificationCategoryAttributes(): array
    {
        return [
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'description' => $this->filled('description') ? $this->string('description')->trim()->value() : null,
            'status' => $this->string('status')->value(),
        ];
    }
}
