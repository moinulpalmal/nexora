<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleIndexRequest;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Admin\Role;
use App\Services\Admin\PermissionService;
use App\Services\Admin\RoleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Roles: one index page with a form dialog (name + grouped permission
 * picker) for create and edit.
 */
class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roles,
        protected PermissionService $permissions,
    ) {}

    public function index(RoleIndexRequest $request): Response
    {
        $filters = $request->filters();

        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount(['permissions', 'users'])
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'is_super_admin' => $role->isSuperAdmin(),
                'deletion_blocker' => $this->roles->deletionBlocker($role),
            ]);

        $permissions = $this->permissions->groupedByModule();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'moduleHelp' => $this->permissions->moduleHelp(array_keys($permissions)),
            'sortable' => Role::SORTABLE,
            'filterable' => Role::FILTERABLE,
            'perPageOptions' => RoleIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $this->roles->create($request->string('name')->value(), $request->submittedPermissions());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return back();
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $blocker = $this->roles->updateBlocker($role);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->roles->update($role, $request->string('name')->value(), $request->submittedPermissions());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return back();
    }

    public function destroy(Role $role): RedirectResponse
    {
        $blocker = $this->roles->deletionBlocker($role);

        if ($blocker !== null) {
            // A held role is a `warning` — the actor can clear it by unassigning
            // it; the super-admin role is an `error` that no work lifts (§8.8).
            return $this->refuse($blocker, $role->isSuperAdmin() ? 'error' : 'warning');
        }

        $this->roles->delete($role);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return back();
    }

    protected function refuse(string $message, string $type = 'error'): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return back();
    }
}
