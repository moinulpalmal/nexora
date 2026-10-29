<?php

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\User;
use Database\Seeders\Admin\DemoUserSeeder;
use Database\Seeders\Admin\RolePermissionSeeder;
use Database\Seeders\Admin\SuperAdminSeeder;
use Illuminate\Foundation\Http\Kernel;

test('the models bound by spatie are the application models', function () {
    expect(config('permission.models.role'))->toBe(Role::class)
        ->and(config('permission.models.permission'))->toBe(Permission::class);
});

test('the seeder creates the catalogue and is idempotent', function () {
    $this->seed(RolePermissionSeeder::class);

    $permissions = Permission::count();
    $roles = Role::count();

    expect($permissions)->toBe(count(RolePermissionSeeder::names()))
        ->and(Role::pluck('name')->sort()->values()->all())->toBe(['accountant', 'admin', 'hr-officer', 'project-manager', Role::SUPER_ADMIN, 'viewer']);

    $this->seed(RolePermissionSeeder::class);

    expect(Permission::count())->toBe($permissions)
        ->and(Role::count())->toBe($roles);
});

test('every catalogued permission name reads module.resource.action in kebab case', function (string $name) {
    expect($name)->toMatch('/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\.[a-z0-9]+(?:-[a-z0-9]+)*){2}$/');
})->with(fn () => RolePermissionSeeder::names());

test('super-admin holds the whole catalogue', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::findByName(Role::SUPER_ADMIN)->permissions)->toHaveCount(count(RolePermissionSeeder::names()));
});

test('admin holds the admin module except the super-admin-only permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = Role::findByName('admin');

    expect($admin->hasPermissionTo('admin.users.create'))->toBeTrue()
        ->and($admin->hasPermissionTo('admin.roles.update'))->toBeTrue()
        ->and($admin->hasPermissionTo('admin.audit-logs.view'))->toBeFalse()
        ->and($admin->hasPermissionTo('admin.mail-settings.update'))->toBeFalse()
        ->and($admin->hasPermissionTo('admin.quick-login.update'))->toBeFalse()
        ->and($admin->hasPermissionTo('admin.sent-mails.view'))->toBeFalse();
});

test('the viewer role is read-only and never reads the sensitive screens', function () {
    $this->seed(RolePermissionSeeder::class);

    $names = Role::findByName('viewer')->permissions->pluck('name');

    expect($names)->not->toBeEmpty()
        ->and($names->every(fn (string $name): bool => str_ends_with($name, '.view')))->toBeTrue()
        ->and($names)->not->toContain('admin.audit-logs.view')
        ->and($names)->not->toContain('admin.sent-mails.view');
});

test('a super admin passes every gate check', function () {
    $user = superAdmin();

    expect($user->can('admin.users.force-delete'))->toBeTrue()
        ->and($user->can('anything.at.all'))->toBeTrue();
});

test('an ordinary user only passes the checks it was granted', function () {
    $user = userWithPermissions('admin.users.view');

    expect($user->can('admin.users.view'))->toBeTrue()
        ->and($user->can('admin.users.update'))->toBeFalse();
});

test('users assigned a role inherit its permissions', function () {
    $role = Role::findOrCreate('auditor', 'web');
    $role->givePermissionTo(Permission::findOrCreate('admin.users.view', 'web'));

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('admin.users.view'))->toBeTrue();
});

test('role assignments store the morph alias, not the class name', function () {
    $user = superAdmin();

    $this->assertDatabaseHas('model_has_roles', ['model_id' => $user->id, 'model_type' => 'user']);
});

test('permissions are shared with every inertia page', function () {
    $this->actingAs(userWithPermissions('admin.roles.view'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.permissions', ['admin.roles.view']));
});

test('a super admin is shared as a wildcard rather than the whole catalogue', function () {
    $this->actingAs(superAdmin());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.permissions', ['*']));
});

test('guests are shared no permissions', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.permissions', []));
});

test('the permission middleware aliases are registered', function () {
    expect(app(Kernel::class)->getMiddlewareAliases())->toHaveKeys(['role', 'permission', 'role_or_permission']);
});

test('the super admin seeder creates the configured account and is idempotent', function () {
    config(['auth.super_admin' => [
        'name' => 'Root',
        'username' => 'Root',
        'email' => 'root@example.com',
        'password' => compliantPassword(),
    ]]);

    $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class, SuperAdminSeeder::class]);

    $user = User::where('username', 'root')->sole();

    expect($user->hasRole(Role::SUPER_ADMIN))->toBeTrue()
        ->and($user->email)->toBe('root@example.com');
});

test('the super admin seeder refuses to run in production without a password', function () {
    $this->seed(RolePermissionSeeder::class);

    config(['auth.super_admin.password' => null]);
    app()->detectEnvironment(fn () => 'production');

    // Called directly: `db:seed` would stop at its production confirmation prompt.
    app(SuperAdminSeeder::class)->run();
})->throws(RuntimeException::class, 'SUPER_ADMIN_PASSWORD');

test('the demo seeder gives one account per non-super role', function () {
    $this->seed([RolePermissionSeeder::class, DemoUserSeeder::class]);

    foreach (DemoUserSeeder::ACCOUNTS as $username => $account) {
        expect(User::where('username', $username)->sole()->hasRole($account['role']))->toBeTrue();
    }

    expect(DemoUserSeeder::ACCOUNTS)->toHaveCount(Role::where('name', '!=', Role::SUPER_ADMIN)->count());
});

test('each module role holds its own module and nothing else', function () {
    $this->seed(RolePermissionSeeder::class);

    foreach (['project-manager' => 'projects.', 'accountant' => 'accounting.', 'hr-officer' => 'hrm.'] as $role => $prefix) {
        $names = Role::findByName($role)->permissions->pluck('name');

        expect($names)->not->toBeEmpty()
            ->and($names->every(fn (string $name): bool => str_starts_with($name, $prefix)))->toBeTrue();
    }
});

test('an hr officer may add and correct designations but not remove them', function () {
    $this->seed(RolePermissionSeeder::class);

    $officer = Role::findByName('hr-officer');

    expect($officer->hasPermissionTo('hrm.designations.view'))->toBeTrue()
        ->and($officer->hasPermissionTo('hrm.designations.create'))->toBeTrue()
        ->and($officer->hasPermissionTo('hrm.designations.update'))->toBeTrue()
        ->and($officer->hasPermissionTo('hrm.designations.import'))->toBeTrue()
        ->and($officer->hasPermissionTo('hrm.designations.delete'))->toBeFalse()
        ->and($officer->hasPermissionTo('hrm.designations.restore'))->toBeFalse()
        ->and($officer->hasPermissionTo('hrm.designations.force-delete'))->toBeFalse()
        ->and($officer->hasPermissionTo('hrm.departments.delete'))->toBeTrue();
});
