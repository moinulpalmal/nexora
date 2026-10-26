<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('security page requires a recent password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('security page is displayed once the password is confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/security'));
});

test('password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => compliantPassword(),
            'password_confirmation' => compliantPassword(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check(compliantPassword(), $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => compliantPassword(),
            'password_confirmation' => compliantPassword(),
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));
});
