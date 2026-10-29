<?php

namespace App\Http\Requests\Hrm;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\Hrm\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The departments list: column filters plus the active/deleted tab (`view`),
 * as `Admin\UserIndexRequest`. The selected company is not a filter — the
 * model's scope already confines the list to it.
 */
class DepartmentIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Department::SORTABLE;
    }

    protected function filterable(): array
    {
        return Department::FILTERABLE;
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
     * Which tab is open: the live departments, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
