<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Services\Admin\PermissionDescriptions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the RBAC catalogue — the **only** place a permission is defined.
 *
 * Names follow `{module}.{resource}.{action}` (ARCHITECTURE.md §9.1). There is
 * no UI to create a permission: one that no code checks does nothing. Adding a
 * permission means adding it here, its sentence to {@see PermissionDescriptions}
 * (a test fails otherwise), and the check that uses it.
 *
 * Idempotent: it creates what is missing and re-syncs every role's permissions,
 * so it is safe to re-run after adding a module surface. On a deployed database,
 * ship new permissions with a migration that calls this seeder.
 */
class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Every permission the application ships with, grouped by `{module}.{resource}`.
     *
     * @var array<string, list<string>>
     */
    protected const array CATALOGUE = [
        'admin.users' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete', 'reset-password', 'assign-roles', 'assign-companies'],
        'admin.roles' => ['view', 'create', 'update', 'delete'],
        'admin.companies' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
        // `view` alone: the catalogue is defined in code, never edited in the UI.
        'admin.permissions' => ['view'],
        // `view` alone: the audit trail is read, never written or corrected.
        'admin.audit-logs' => ['view'],
        /*
         * One pair per Application Settings tab, so the tabs are independently
         * gated: the hub opens for either `.view` and hides the tab the actor
         * cannot read.
         */
        'admin.mail-settings' => ['view', 'update'],
        'admin.quick-login' => ['view', 'update'],
        'admin.branding' => ['view', 'update'],
        'admin.import-limits' => ['view', 'update'],
        'admin.sent-mails' => ['view'],

        // One overview per ERP module; each opens the module's dashboard.
        'projects.dashboard' => ['view'],
        'accounting.dashboard' => ['view'],
        'hrm.dashboard' => ['view'],
        'hrm.departments' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete', 'import'],
        // Group-wide (§9.10): one list every company shares.
        'hrm.designations' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete', 'import'],
        // `create-account` opens an employee's sign-in account; `view-compensation`
        // shows and edits the salary (owner, 2026-10-29).
        'hrm.employees' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete', 'import', 'create-account', 'view-compensation'],
    ];

    /**
     * Permissions a role's rules would match but the role must not receive.
     *
     * Designations are shared by every company, so an HR officer — who works
     * for one — may add and correct them but not remove one from all of them
     * (owner, 2026-10-29). Applied after {@see self::ROLES}; an administrator
     * can still grant one to a role by hand.
     *
     * @var array<string, list<string>>
     */
    protected const array ROLE_EXCLUSIONS = [
        'hr-officer' => [
            'hrm.designations.delete',
            'hrm.designations.restore',
            'hrm.designations.force-delete',
        ],
    ];

    /**
     * Permissions no role but super-admin receives by rule.
     *
     * Each one is a read or a switch that is sensitive in itself: the audit trail
     * is every recorded value in the application, the mail settings hold SMTP
     * credentials, the sent-mail log shows every recipient's mail, quick
     * login signs anyone in as anyone, and the import limits decide how much
     * memory one upload may take from the server. An administrator can still grant one to a
     * role by hand; this only keeps the rules below from handing them out.
     *
     * @var list<string>
     */
    protected const array SUPER_ADMIN_ONLY = [
        'admin.audit-logs.view',
        'admin.mail-settings.view',
        'admin.mail-settings.update',
        'admin.quick-login.view',
        'admin.quick-login.update',
        'admin.import-limits.view',
        'admin.import-limits.update',
        'admin.sent-mails.view',
    ];

    /**
     * Each role's permissions, as rules rather than lists, so a permission added
     * to the catalogue later reaches the right roles without editing this.
     *
     * A rule is a name prefix (`admin.`), a suffix starting with a dot (`.view`),
     * or a whole-name pattern where `*` stands for one segment (`settings.*.view`).
     * `super-admin` is synced to everything; it also passes every check through
     * `Gate::before`, so its list is informational.
     *
     * @var array<string, list<string>>
     */
    protected const array ROLES = [
        Role::SUPER_ADMIN => [],
        'admin' => ['admin.', 'settings.'],
        'viewer' => ['.view'],
        'project-manager' => ['projects.'],
        'accountant' => ['accounting.'],
        'hr-officer' => ['hrm.'],
    ];

    /**
     * Seed the roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->seedPermissions();

        foreach (self::ROLES as $name => $rules) {
            $role = Role::findOrCreate($name, 'web');

            $role->syncPermissions(
                $name === Role::SUPER_ADMIN
                    ? $permissions
                    : $permissions->filter(
                        fn (Permission $permission): bool => $this->matchesAny($permission->name, $rules)
                            && ! in_array($permission->name, self::ROLE_EXCLUSIONS[$name] ?? [], true),
                    ),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Every catalogued permission name, in catalogue order.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        $names = [];

        foreach (self::CATALOGUE as $resource => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$resource}.{$action}";
            }
        }

        return $names;
    }

    /**
     * Create every catalogued permission.
     *
     * Models are returned rather than names so the role sync does not have to
     * resolve them through the permission cache. Eloquent's `firstOrCreate`
     * rather than spatie's `findOrCreate`, which is typed to its contract; the
     * cache is cleared around the whole run in {@see self::run()}.
     *
     * @return Collection<int, Permission>
     */
    protected function seedPermissions(): Collection
    {
        return collect(self::names())->map(
            fn (string $name): Permission => Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']),
        );
    }

    /**
     * Determine whether a permission name matches any of the given rules.
     *
     * @param  list<string>  $rules
     */
    protected function matchesAny(string $permission, array $rules): bool
    {
        if (in_array($permission, self::SUPER_ADMIN_ONLY, true)) {
            return false;
        }

        foreach ($rules as $rule) {
            $matched = match (true) {
                str_contains($rule, '*') => $this->matchesPattern($permission, $rule),
                str_starts_with($rule, '.') => str_ends_with($permission, $rule),
                default => str_starts_with($permission, $rule),
            };

            if ($matched) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether a permission name matches a `*` pattern, where `*`
     * stands for exactly one dot-free segment.
     */
    protected function matchesPattern(string $permission, string $pattern): bool
    {
        $regex = '/^'.str_replace('\*', '[^.]+', preg_quote($pattern, '/')).'$/';

        return preg_match($regex, $permission) === 1;
    }
}
