<?php

use App\Models\User;
use Database\Seeders\Admin\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

test('the catalogue is refused without the view permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.permissions.index'))
        ->assertForbidden();
});

test('the catalogue lists permissions with their help and holders', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(userWithPermissions('admin.permissions.view'));

    $this->get(route('admin.permissions.index', ['filter' => ['name' => 'assign-roles']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/permissions/index')
            ->has('permissions.data', 1)
            ->where('permissions.data.0.name', 'admin.users.assign-roles')
            ->where('permissions.data.0.help.curated', true)
            ->where('permissions.data.0.roles', ['admin', 'super-admin'])
            ->where('modules', [
                ['value' => 'accounting', 'label' => 'Accounts'],
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'hrm', 'label' => 'HRM'],
                ['value' => 'projects', 'label' => 'Projects'],
            ]),
        );
});

test('the catalogue pages 25 at a time by default', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(userWithPermissions('admin.permissions.view'));

    $this->get(route('admin.permissions.index'))
        ->assertInertia(fn ($page) => $page->where('filters.per_page', 25));
});

test('the catalogue is read-only: no route writes a permission', function () {
    $writes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.permissions.'))
        ->flatMap(fn ($route): array => $route->methods())
        ->unique()
        ->values()
        ->all();

    expect($writes)->toEqualCanonicalizing(['GET', 'HEAD']);
});
