<?php

namespace App\Http\Requests\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\ProjectManagement\CertificationCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The certification categories list: column filters plus the active/deleted tab
 * (`view`), as {@see TenderTypeIndexRequest}.
 */
class CertificationCategoryIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return CertificationCategory::SORTABLE;
    }

    protected function filterable(): array
    {
        return CertificationCategory::FILTERABLE;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,trashed'],
            'filter.status' => ['nullable', Rule::enum(RecordStatus::class)],
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
     * Which tab is open: the live categories, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
