<?php

use App\Models\User;
use Database\Seeders\Admin\DemoUserSeeder;
use Database\Seeders\Admin\RolePermissionSeeder;

test('guests are redirected to the login page', function () {
    $this->get(route('projects.dashboard'))->assertRedirect(route('login'));
});

test('the overview is refused without the view permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('projects.dashboard'))
        ->assertForbidden();
});

test('the overview renders for a holder of the view permission', function () {
    $this->actingAs(userWithPermissions('projects.dashboard.view'))
        ->get(route('projects.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('projects/dashboard'));
});

test('the project-manager demo account opens the overview', function () {
    $this->seed([RolePermissionSeeder::class, DemoUserSeeder::class]);

    $this->actingAs(User::where('username', 'pm')->sole())
        ->get(route('projects.dashboard'))
        ->assertOk();
});

test('the admin role does not open the overview', function () {
    $this->seed([RolePermissionSeeder::class, DemoUserSeeder::class]);

    $this->actingAs(User::where('username', 'admin')->sole())
        ->get(route('projects.dashboard'))
        ->assertForbidden();
});
