<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| A password someone else set must be changed
|--------------------------------------------------------------------------
|
| `EnsurePasswordIsChanged` keeps a flagged user on Settings → Security
| until they choose their own password (ARCHITECTURE.md §9.2). Admin's
| create and reset set the flag, and so does an HRM employee's account.
|
*/

test('a flagged user is sent to the security page from anywhere else', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('security.edit'));
});

test('a flagged user reaches the security page without confirming the password again', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/security')->where('auth.user.must_change_password', true));
});

test('changing the password clears the flag', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => compliantPassword(),
        'password_confirmation' => compliantPassword(),
    ])->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check(compliantPassword(), $user->password))->toBeTrue();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('an account an administrator creates or resets must change its password', function () {
    $admin = userWithPermissions('admin.users.view', 'admin.users.create', 'admin.users.reset-password');

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Person',
        'username' => 'newperson',
        'email' => 'new@example.com',
        'status' => 'A',
        'password' => compliantPassword(),
        'password_confirmation' => compliantPassword(),
    ])->assertSessionHasNoErrors();

    expect(User::query()->where('username', 'newperson')->sole()->must_change_password)->toBeTrue();

    $other = User::factory()->create();

    $this->actingAs($admin)->put(route('admin.users.password', $other), [
        'password' => compliantPassword(),
        'password_confirmation' => compliantPassword(),
    ])->assertSessionHasNoErrors();

    expect($other->fresh()->must_change_password)->toBeTrue();
});
