<?php

namespace App\Models\Admin;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * The application's role model.
 *
 * Extends spatie/laravel-permission's role so roles live under the Admin module
 * like every other Admin-owned model. Bound through
 * `config('permission.models.role')`.
 *
 * Roles are never named in authorization checks — routes and the UI check
 * permissions (ARCHITECTURE.md §9.1). The one exception is the super-admin
 * bypass in {@see AppServiceProvider::configureAuthorization()}.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Permission> $permissions
 */
class Role extends SpatieRole
{
    /**
     * The role that bypasses every permission check.
     *
     * @see AppServiceProvider::configureAuthorization()
     */
    public const string SUPER_ADMIN = 'super-admin';

    public function isSuperAdmin(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }
}
