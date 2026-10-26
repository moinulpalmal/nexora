<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionIndexRequest;
use App\Models\Admin\Permission;
use App\Services\Admin\PermissionService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Permissions: the read-only catalogue — what each permission allows
 * and which roles hold it. Permissions are defined in code; roles are where
 * they are granted.
 */
class PermissionController extends Controller
{
    public function __construct(protected PermissionService $permissions) {}

    public function index(PermissionIndexRequest $request): Response
    {
        $filters = $request->filters();

        $permissions = Permission::query()
            ->with('roles:id,name')
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
                'module' => $permission->module(),
                'roles' => $permission->roles->pluck('name')->sort()->values(),
                'help' => $this->permissions->help($permission->name),
            ]);

        return Inertia::render('admin/permissions/index', [
            'permissions' => $permissions,
            'modules' => $this->permissions->moduleOptions(),
            'sortable' => Permission::SORTABLE,
            'filterable' => Permission::FILTERABLE,
            'perPageOptions' => PermissionIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
        ]);
    }
}
