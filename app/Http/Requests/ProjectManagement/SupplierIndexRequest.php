<?php

namespace App\Http\Requests\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\ProjectManagement\Supplier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The suppliers list: column filters plus the active/deleted tab (`view`), as
 * {@see ClientIndexRequest}. The category filter is an id from the combobox; a
 * value that is not an integer is an error, not an empty list, as
 * `Hrm\EmployeeIndexRequest`'s designation filter.
 */
class SupplierIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Supplier::SORTABLE;
    }

    protected function filterable(): array
    {
        return Supplier::FILTERABLE;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,trashed'],
            'filter.status' => ['nullable', Rule::enum(RecordStatus::class)],
            'filter.supplier_category_id' => ['nullable', 'integer'],
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
     * Which tab is open: the live suppliers, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
