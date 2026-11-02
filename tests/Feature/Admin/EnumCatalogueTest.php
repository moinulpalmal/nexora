<?php

use App\Enums\EnumRegistry;
use App\Models\Admin\Permission;
use App\Models\User;
use Database\Seeders\Admin\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

test('the enum catalogue is refused without the view permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.enums.index'))
        ->assertForbidden();
});

test('the enum catalogue lists every registered enum with its cases and purpose', function () {
    $this->actingAs(userWithPermissions('admin.enums.view'));

    $this->get(route('admin.enums.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/enums/index')
            ->has('enums', count(EnumRegistry::classes()))
            ->where('enums', function ($enums): bool {
                $stage = collect($enums)->firstWhere('id', 'project-stage');

                return $stage['module'] === 'Project Management'
                    && $stage['summary'] === 'Where a client project stands in its life.'
                    && $stage['cases'][3] === ['case' => 'OnHold', 'value' => 'on-hold', 'label' => 'On Hold']
                    && collect($enums)->firstWhere('id', 'record-status')['module'] === 'General';
            })
            ->where('modules', [
                ['value' => 'General', 'label' => 'General'],
                ['value' => 'Admin', 'label' => 'Admin'],
                ['value' => 'Imports', 'label' => 'Imports'],
                ['value' => 'Project Management', 'label' => 'Project Management'],
            ]),
        );
});

test('a summary turns docblock references into plain names', function () {
    $this->actingAs(userWithPermissions('admin.enums.view'));

    $this->get(route('admin.enums.index'))
        ->assertInertia(fn ($page) => $page
            ->where('enums', fn ($enums) => collect($enums)->every(
                fn (array $enum): bool => ! str_contains($enum['summary'], '{@see') && ! str_contains($enum['summary'], '**'),
            )),
        );
});

test('the admin and viewer roles hold the enum catalogue by rule', function () {
    $this->seed(RolePermissionSeeder::class);

    $holders = Permission::findByName('admin.enums.view')->roles->pluck('name')->sort()->values()->all();

    expect($holders)->toBe(['admin', 'super-admin', 'viewer']);
});

test('the enum catalogue is read-only: no route writes an enum', function () {
    $writes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.enums.'))
        ->flatMap(fn ($route): array => $route->methods())
        ->unique()
        ->values()
        ->all();

    expect($writes)->toEqualCanonicalizing(['GET', 'HEAD']);
});
