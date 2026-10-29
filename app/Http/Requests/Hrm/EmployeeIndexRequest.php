<?php

namespace App\Http\Requests\Hrm;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\Hrm\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The employees list: column filters plus the active/deleted tab (`view`), as
 * {@see DepartmentIndexRequest}. The department and designation filters are
 * ids from the comboboxes; a value that is not an integer is an error, not an
 * empty list.
 */
class EmployeeIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Employee::SORTABLE;
    }

    protected function filterable(): array
    {
        return Employee::FILTERABLE;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,trashed'],
            'filter.status' => ['nullable', Rule::enum(RecordStatus::class)],
            'filter.department_id' => ['nullable', 'integer'],
            'filter.designation_id' => ['nullable', 'integer'],
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
     * Which tab is open: the live employees, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
