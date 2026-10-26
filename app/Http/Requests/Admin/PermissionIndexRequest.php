<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ListRequest;
use App\Models\Admin\Permission;

class PermissionIndexRequest extends ListRequest
{
    public const int DEFAULT_PER_PAGE = 25;

    protected function sortable(): array
    {
        return Permission::SORTABLE;
    }

    protected function filterable(): array
    {
        return Permission::FILTERABLE;
    }
}
