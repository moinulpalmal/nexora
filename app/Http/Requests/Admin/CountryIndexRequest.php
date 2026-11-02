<?php

namespace App\Http\Requests\Admin;

use App\Enums\RecordStatus;
use App\Http\Requests\Hrm\DesignationIndexRequest;
use App\Http\Requests\ListRequest;
use App\Models\Admin\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The countries list: column filters plus the active/deleted tab (`view`),
 * as {@see DesignationIndexRequest}.
 */
class CountryIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Country::SORTABLE;
    }

    protected function filterable(): array
    {
        return Country::FILTERABLE;
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
     * Which tab is open: the live countries, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
