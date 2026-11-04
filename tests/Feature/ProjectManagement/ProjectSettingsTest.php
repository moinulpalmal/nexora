<?php

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Projects → Settings
|--------------------------------------------------------------------------
|
| The module's setup lists moved under Settings (owner, 2026-11-04): the menu,
| URL, route name and permission all carry the extra level. The old addresses
| are gone, and Settings has no page of its own.
|
*/

dataset('moved lists', [
    'clients',
    'product-categories',
    'products',
    'supplier-categories',
    'suppliers',
    'tender-types',
    'units',
    'certification-categories',
    'certifications',
    'verification-items',
]);

test('a list lives under projects settings and its old address is gone', function (string $list) {
    expect(Route::has("projects.settings.{$list}.index"))->toBeTrue()
        ->and(Route::has("projects.{$list}.index"))->toBeFalse()
        ->and(route("projects.settings.{$list}.index", absolute: false))->toBe("/projects/settings/{$list}");

    $this->actingAs(userWithPermissions("projects.settings.{$list}.view"))
        ->get("/projects/{$list}")
        ->assertNotFound();
})->with('moved lists');

test('settings has no page of its own and the overview stays where it was', function () {
    expect(route('projects.dashboard', absolute: false))->toBe('/projects');

    $this->actingAs(userWithPermissions('projects.dashboard.view'))
        ->get('/projects/settings')
        ->assertNotFound();
});

test('the rename keeps what a role was granted and leaves no old name behind', function () {
    $migration = require database_path('migrations/2026_11_04_063421_rename_projects_settings_permissions.php');

    $permission = Permission::query()->create(['name' => 'projects.clients.view', 'guard_name' => 'web']);
    $role = Role::query()->create(['name' => 'site-reviewer', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $migration->up();

    expect($permission->fresh()->name)->toBe('projects.settings.clients.view')
        ->and(Permission::query()->where('name', 'projects.clients.view')->exists())->toBeFalse()
        ->and($role->fresh()->hasPermissionTo('projects.settings.clients.view'))->toBeTrue();

    $migration->down();

    expect($permission->fresh()->name)->toBe('projects.clients.view');
});

test('the rename does nothing on a database with no permissions yet', function () {
    $migration = require database_path('migrations/2026_11_04_063421_rename_projects_settings_permissions.php');

    $migration->up();

    expect(Permission::query()->where('name', 'like', 'projects.settings.%')->exists())->toBeFalse();
});
