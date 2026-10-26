<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Contracts\Auditable;
use Database\Seeders\Admin\RolePermissionSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * The application's permission model.
 *
 * Names read `{module}.{resource}.{action}` in kebab case — `admin.users.view`
 * (ARCHITECTURE.md §9.1). The catalogue is defined in code by
 * {@see RolePermissionSeeder}; there is no UI to create or rename one, because
 * a permission no code checks does nothing.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Role> $roles
 */
class Permission extends SpatiePermission implements Auditable
{
    use Audited;

    /**
     * The module a permission belongs to — the first segment of its name.
     */
    public function module(): string
    {
        return explode('.', $this->name)[0];
    }

    /**
     * Limit the query to one module's permissions.
     *
     * Backs the permissions list's module filter, which is not a column of its
     * own: the module is the name's prefix. LIKE wildcards in the input are
     * escaped so `admin_` cannot match `adminx`.
     *
     * @param  Builder<static>  $query
     */
    public function scopeModule(Builder $query, ?string $module): void
    {
        $module = trim((string) $module);

        if ($module === '') {
            return;
        }

        $query->where('name', 'like', addcslashes($module, '%_\\').'.%');
    }
}
