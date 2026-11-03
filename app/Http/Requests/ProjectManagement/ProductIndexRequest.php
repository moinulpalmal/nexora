<?php

namespace App\Http\Requests\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\ProjectManagement\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The products list: column filters plus the active/deleted tab (`view`), as
 * {@see SupplierIndexRequest}. The category filter is an id from the
 * combobox; a value that is not an integer is an error, not an empty list.
 */
class ProductIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Product::SORTABLE;
    }

    protected function filterable(): array
    {
        return Product::FILTERABLE;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,trashed'],
            'filter.status' => ['nullable', Rule::enum(RecordStatus::class)],
            'filter.product_category_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function filterValues(): array
    {
        return [
            'view' => $this->view(),
        ];
    }

    /**
     * Which tab is open: the live products, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
