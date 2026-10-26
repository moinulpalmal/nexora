<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ListRequest;
use App\Models\Admin\Role;

class RoleIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return Role::SORTABLE;
    }

    protected function filterable(): array
    {
        return Role::FILTERABLE;
    }
}
