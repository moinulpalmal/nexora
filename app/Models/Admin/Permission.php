<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
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
    use Audited, Listable;

    /**
     * The permissions catalogue's filter row. `module` is not a column: it is
     * the name's first segment, matched by {@see self::scopeModule()}.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'name' => FilterType::Contains,
        'module' => FilterType::Scope,
    ];

    /**
     * @var list<string>
     */
    public const array SORTABLE = ['name', 'created_at'];

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
     * escaped (Listable's driver-neutral escape) so `admin_` cannot match `adminx`.
     *
     * @param  Builder<static>  $query
     */
    public function scopeModule(Builder $query, ?string $module): void
    {
        $module = trim((string) $module);

        if ($module === '') {
            return;
        }

        static::whereLikeLiteral($query, 'name', static::escapeLike($module).'.%');
    }
}
