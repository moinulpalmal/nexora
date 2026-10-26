<?php

use App\Services\Admin\PermissionDescriptions;
use Database\Seeders\Admin\RolePermissionSeeder;

/*
|--------------------------------------------------------------------------
| Permission help copy
|--------------------------------------------------------------------------
|
| The copy lives in code beside the catalogue, so these tests keep the two in
| step: a catalogued permission or module without a sentence fails here, and so
| does a sentence left behind for a permission that was removed.
|
*/

test('every catalogued permission has curated help', function () {
    $missing = array_diff(RolePermissionSeeder::names(), app(PermissionDescriptions::class)->curatedNames());

    expect($missing)->toBe([], 'Add these to PermissionDescriptions::PERMISSIONS: '.implode(', ', $missing));
});

test('no curated help is left for a permission the catalogue no longer has', function () {
    $orphans = array_diff(app(PermissionDescriptions::class)->curatedNames(), RolePermissionSeeder::names());

    expect($orphans)->toBe([], 'Remove these from PermissionDescriptions::PERMISSIONS: '.implode(', ', $orphans));
});

test('every catalogued module has curated help', function () {
    $modules = collect(RolePermissionSeeder::names())
        ->map(fn (string $name): string => explode('.', $name)[0])
        ->unique()
        ->values()
        ->all();

    expect(array_diff($modules, app(PermissionDescriptions::class)->curatedModules()))->toBe([]);
});

test('a curated permission returns its written copy', function () {
    expect(app(PermissionDescriptions::class)->for('admin.users.assign-roles'))
        ->curated->toBeTrue()
        ->summary->toBe('Change which roles another user holds.')
        ->note->not->toBeNull()
        ->where->toBe('Admin → Users');
});

test('a permission outside the catalogue falls back to a sentence built from its name', function () {
    expect(app(PermissionDescriptions::class)->for('reports.sales-totals.view'))
        ->curated->toBeFalse()
        ->summary->toBe('Open and read sales totals.')
        ->where->toBeNull();
});

test('an unknown action falls back to the action itself', function () {
    expect(app(PermissionDescriptions::class)->for('reports.sales.approve-all'))
        ->summary->toBe('Approve all sales.');
});

test('an unknown module gets a generic sentence', function () {
    expect(app(PermissionDescriptions::class)->forModule('quality-control'))
        ->toBe(['summary' => 'Permissions for the quality control module.', 'where' => null]);
});
