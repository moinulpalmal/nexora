<?php

use App\Models\User;
use Database\Seeders\Admin\DemoUserSeeder;
use Database\Seeders\Admin\RolePermissionSeeder;

test('guests are redirected to the login page', function () {
    $this->get(route('hrm.dashboard'))->assertRedirect(route('login'));
});

test('the overview is refused without the view permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hrm.dashboard'))
        ->assertForbidden();
});

test('the overview renders for a holder of the view permission', function () {
    $this->actingAs(userWithPermissions('hrm.dashboard.view'))
        ->get(route('hrm.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('hrm/dashboard'));
});

test('the hr-officer demo account opens the overview', function () {
    $this->seed([RolePermissionSeeder::class, DemoUserSeeder::class]);

    $this->actingAs(User::where('username', 'hr')->sole())
        ->get(route('hrm.dashboard'))
        ->assertOk();
});

test('the admin role does not open the overview', function () {
    $this->seed([RolePermissionSeeder::class, DemoUserSeeder::class]);

    $this->actingAs(User::where('username', 'admin')->sole())
        ->get(route('hrm.dashboard'))
        ->assertForbidden();
});
