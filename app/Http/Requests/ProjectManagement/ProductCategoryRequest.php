<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\ProductCategory;
use App\Services\Admin\SelectedCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a product category. One request for both verbs, as
 * `ClientRequest`.
 *
 * **There is no `company_id` field** (ARCHITECTURE.md §9.10): the category
 * belongs to the actor's selected company, which `ProductCategoryService`
 * stamps. The code and the name are each unique *within that company*,
 * deleted categories included — the same values in another company are legal,
 * which they must be: every company starts with the same starter set.
 */
class ProductCategoryRequest extends FormRequest
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
        $category = $this->route('product_category');
        $ignore = $category instanceof ProductCategory ? $category->id : null;

        // `withoutGlobalScopes()` drops the soft-delete scope too, so deleted
        // categories are counted, and the company is named explicitly.
        $siblings = ProductCategory::withoutGlobalScopes()->where('company_id', app(SelectedCompany::class)->id() ?? 0);

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
                    __('The code ":value" belongs to a deleted product category in this company. Restore it from the Deleted tab instead.'),
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
                    __('A product category named ":value" already exists in this company.'),
                    __('A deleted product category in this company is named ":value". Restore it from the Deleted tab instead.'),
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
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "RAW" or "RAW-01".'),
        ];
    }

    /**
     * The validated values, an empty description saved as null rather than as
     * an empty string.
     *
     * @return array{code: string, name: string, description: string|null, status: string}
     */
    public function productCategoryAttributes(): array
    {
        return [
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'description' => $this->filled('description') ? $this->string('description')->trim()->value() : null,
            'status' => $this->string('status')->value(),
        ];
    }
}
