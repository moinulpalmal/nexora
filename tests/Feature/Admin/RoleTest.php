<?php

use App\Models\Admin\AuditLog;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\User;
use Database\Seeders\Admin\RolePermissionSeeder;

test('the roles list is refused without the view permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

test('the roles list renders with counts and the grouped picker', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(userWithPermissions('admin.roles.view'));

    $this->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/roles/index')
            ->has('roles.data', 3)
            ->has('permissions.admin')
            ->has('moduleHelp.admin')
            ->where('roles.data.0.name', 'admin'),
        );
});

test('a role is created with permissions and the grant is audited by name', function () {
    Permission::findOrCreate('admin.users.view', 'web');
    Permission::findOrCreate('admin.roles.view', 'web');
    $this->actingAs(userWithPermissions('admin.roles.create'));
    config(['audit.console' => true]);

    assertToast($this->post(route('admin.roles.store'), [
        'name' => 'data-entry',
        'permissions' => ['admin.users.view', 'admin.roles.view'],
    ]), 'success');

    $role = Role::findByName('data-entry');

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(['admin.roles.view', 'admin.users.view'])
        ->and(AuditLog::where('event', 'permissions-changed')->sole()->new_values)
        ->toBe(['permissions' => ['admin.roles.view', 'admin.users.view']]);
});

test('role names are kebab-case and unique', function (string $name) {
    Role::findOrCreate('taken', 'web');

    $this->actingAs(userWithPermissions('admin.roles.create'))
        ->post(route('admin.roles.store'), ['name' => $name])
        ->assertSessionHasErrors('name');
})->with(['Data Entry', 'data_entry', '-lead', 'taken']);

test('an unknown permission is refused', function () {
    $this->actingAs(userWithPermissions('admin.roles.create'))
        ->post(route('admin.roles.store'), ['name' => 'x', 'permissions' => ['made.up.thing']])
        ->assertSessionHasErrors('permissions.0');
});

test('a role is renamed and re-permissioned', function () {
    Permission::findOrCreate('admin.users.view', 'web');
    $role = Role::findOrCreate('old-name', 'web');
    $role->givePermissionTo('admin.users.view');

    $this->actingAs(userWithPermissions('admin.roles.update'))
        ->put(route('admin.roles.update', $role), ['name' => 'new-name', 'permissions' => []])
        ->assertSessionHasNoErrors();

    expect($role->refresh()->name)->toBe('new-name')
        ->and($role->permissions)->toBeEmpty();
});

test('the super-admin role cannot be edited or deleted', function () {
    $role = Role::findOrCreate(Role::SUPER_ADMIN, 'web');
    $this->actingAs(userWithPermissions('admin.roles.update', 'admin.roles.delete'));

    assertToast($this->put(route('admin.roles.update', $role), ['name' => 'renamed']), 'error');
    assertToast($this->delete(route('admin.roles.destroy', $role)), 'error');

    expect(Role::findByName(Role::SUPER_ADMIN))->not->toBeNull();
});

test('a role still held by a user cannot be deleted', function () {
    $role = Role::findOrCreate('held', 'web');
    User::factory()->create()->assignRole($role);

    assertToast($this->actingAs(userWithPermissions('admin.roles.delete'))->delete(route('admin.roles.destroy', $role)), 'error');

    expect(Role::where('name', 'held')->exists())->toBeTrue();
});

test('an unused role is deleted', function () {
    $role = Role::findOrCreate('unused', 'web');

    assertToast($this->actingAs(userWithPermissions('admin.roles.delete'))->delete(route('admin.roles.destroy', $role)), 'success');

    expect(Role::where('name', 'unused')->exists())->toBeFalse();
});

test('each role write is refused without its own permission', function () {
    $role = Role::findOrCreate('target', 'web');
    $this->actingAs(userWithPermissions('admin.roles.view'));

    $this->post(route('admin.roles.store'), ['name' => 'x'])->assertForbidden();
    $this->put(route('admin.roles.update', $role), ['name' => 'y'])->assertForbidden();
    $this->delete(route('admin.roles.destroy', $role))->assertForbidden();
});
