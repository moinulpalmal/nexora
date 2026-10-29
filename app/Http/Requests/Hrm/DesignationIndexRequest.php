<?php

namespace App\Http\Requests\Hrm;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\Hrm\Designation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The designations list: column filters plus the active/deleted tab (`view`),
 * as {@see DepartmentIndexRequest}.
 */
class DesignationIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Designation::SORTABLE;
    }

    protected function filterable(): array
    {
        return Designation::FILTERABLE;
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
     * Which tab is open: the live designations, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
