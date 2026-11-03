<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Product;
use App\Services\Admin\SelectedCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a product. One request for both verbs, as
 * {@see SupplierRequest}.
 *
 * **There is no `company_id` field** (ARCHITECTURE.md §9.10): the product
 * belongs to the actor's selected company, which `ProductService` stamps. The
 * code and the name are each unique *within that company*, deleted products
 * included.
 *
 * **The category** is required and must be the selected company's: product
 * categories are company-scoped, and `Rule::exists` builds a raw query that
 * ignores the model's scope, so the company is named here. It must be active
 * to be *chosen*, but a product already in a category that was later
 * deactivated keeps it on edit — as the supplier's category.
 */
class ProductRequest extends FormRequest
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
        $product = $this->route('product');
        $ignore = $product instanceof Product ? $product->id : null;
        $companyId = app(SelectedCompany::class)->id() ?? 0;

        // `withoutGlobalScopes()` drops the soft-delete scope too, so deleted
        // products are counted, and the company is named explicitly.
        $siblings = Product::withoutGlobalScopes()->where('company_id', $companyId);

        return [
            'product_category_id' => [
                'required',
                'integer',
                Rule::exists('product_categories', 'id')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->where(fn (Builder $query) => $query
                        ->where('status', RecordStatus::Active->value)
                        ->orWhere('id', $product instanceof Product ? $product->product_category_id : 0)),
            ],
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
                    __('The code ":value" belongs to a deleted product in this company. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                $this->uniqueIgnoringCase(
                    clone $siblings,
                    'name',
                    $ignore,
                    __('A product named ":value" already exists in this company.'),
                    __('A deleted product in this company is named ":value". Restore it from the Deleted tab instead.'),
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
            'product_category_id.exists' => __('Choose an active product category.'),
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "CEM" or "CEM-50KG".'),
        ];
    }

    /**
     * "The category field is required.", not "product category id", as
     * `CertificationRequest`.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_category_id' => __('category'),
        ];
    }

    /**
     * The validated values, an empty description saved as null rather than as
     * an empty string.
     *
     * @return array{product_category_id: int, code: string, name: string, description: string|null, status: string}
     */
    public function productAttributes(): array
    {
        return [
            'product_category_id' => $this->integer('product_category_id'),
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'description' => $this->filled('description') ? $this->string('description')->trim()->value() : null,
            'status' => $this->string('status')->value(),
        ];
    }
}
