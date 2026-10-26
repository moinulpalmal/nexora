<?php

namespace App\Services\Admin;

use App\Enums\Admin\AuditEvent;
use App\Models\Admin\Role;
use Illuminate\Support\Facades\DB;

/**
 * Every write to a role, and the guards that may refuse one.
 *
 * The super-admin role is fixed: it passes every check through `Gate::before`,
 * so renaming it would silently break that bypass and its permission list is
 * informational. It can be neither edited nor deleted.
 */
class RoleService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  list<string>  $permissions
     */
    public function create(string $name, array $permissions = []): Role
    {
        return DB::transaction(function () use ($name, $permissions): Role {
            $role = Role::query()->create(['name' => $name, 'guard_name' => 'web']);

            $this->syncPermissions($role, $permissions);

            return $role;
        });
    }

    /**
     * @param  list<string>  $permissions
     */
    public function update(Role $role, string $name, array $permissions = []): void
    {
        DB::transaction(function () use ($role, $name, $permissions): void {
            $role->update(['name' => $name]);

            $this->syncPermissions($role, $permissions);
        });
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function updateBlocker(Role $role): ?string
    {
        if ($role->isSuperAdmin()) {
            return __('The super-admin role cannot be changed. It already passes every check.');
        }

        return null;
    }

    public function deletionBlocker(Role $role): ?string
    {
        if ($role->isSuperAdmin()) {
            return __('The super-admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return __('This role is still assigned to users. Remove it from them first.');
        }

        return null;
    }

    /**
     * Sync permissions and record the change by name: the pivot write fires no
     * model event.
     *
     * @param  list<string>  $permissions
     */
    private function syncPermissions(Role $role, array $permissions): void
    {
        $before = $this->permissionNames($role);

        $role->syncPermissions($permissions);

        $this->audits->recordChange($role, AuditEvent::PermissionsChanged, ['permissions' => $before], ['permissions' => $this->permissionNames($role)]);
    }

    /**
     * Sorted, so an unchanged set compares equal in `recordChange()`.
     *
     * @return list<string>
     */
    private function permissionNames(Role $role): array
    {
        $names = array_values(array_filter($role->permissions()->pluck('name')->all(), is_string(...)));
        sort($names);

        return $names;
    }
}
