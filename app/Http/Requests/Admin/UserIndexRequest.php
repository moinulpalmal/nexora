<?php

namespace App\Http\Requests\Admin;

use App\Enums\RecordStatus;
use App\Http\Requests\ListRequest;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The users list: column filters plus the active/deleted tab (`view`).
 */
class UserIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return User::SORTABLE;
    }

    protected function filterable(): array
    {
        return User::FILTERABLE;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,trashed'],
            'filter.status' => ['nullable', Rule::enum(RecordStatus::class)],
            'filter.role' => ['nullable', Rule::exists(Role::class, 'name')],
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
     * Which tab is open: the active users, or the soft-deleted ones.
     */
    public function view(): string
    {
        return $this->string('view')->value() === 'trashed' ? 'trashed' : 'active';
    }
}
