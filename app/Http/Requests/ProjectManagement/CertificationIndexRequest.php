<?php

namespace App\Http\Requests\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\ProjectManagement\Certification;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The certifications list: column filters plus the active/deleted tab (`view`),
 * as {@see SupplierIndexRequest}: the category filter is an id from the
 * combobox, and a value that is not an integer is an error, not an empty list.
 */
class CertificationIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Certification::SORTABLE;
    }

    protected function filterable(): array
    {
        return Certification::FILTERABLE;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,trashed'],
            'filter.status' => ['nullable', Rule::enum(RecordStatus::class)],
            'filter.certification_category_id' => ['nullable', 'integer'],
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
     * Which tab is open: the live certifications, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
