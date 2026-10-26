<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $this->get(route('login'))->assertOk();
});

test('users can sign in with their email', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can sign in with their username', function () {
    $user = User::factory()->create(['username' => 'jdoe']);

    $this->post(route('login.store'), [
        'login' => 'jdoe',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('the login identifier is case-insensitive', function (string $typed) {
    $user = User::factory()->create(['username' => 'jdoe', 'email' => 'jane@example.com']);

    $this->post(route('login.store'), ['login' => $typed, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
})->with([
    'username' => 'JDoe',
    'email' => 'Jane@Example.com',
]);

test('usernames and emails are stored lowercased', function () {
    $user = User::factory()->create(['username' => ' JDoe ', 'email' => 'Jane@Example.COM']);

    expect($user->username)->toBe('jdoe')
        ->and($user->email)->toBe('jane@example.com');
});

test('users can not authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'login' => $user->username,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('an unknown login gets the generic failure message', function () {
    $this->post(route('login.store'), [
        'login' => 'nobody',
        'password' => 'password',
    ])->assertSessionHasErrors(['login' => __('auth.failed')]);

    $this->assertGuest();
});

test('a deactivated user is refused with its own message', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'login' => $user->username,
        'password' => 'password',
    ])->assertSessionHasErrors(['login' => 'This account has been deactivated. Contact an administrator.']);

    $this->assertGuest();
});

test('a deactivated user with the wrong password learns nothing about the account', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'login' => $user->username,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['login' => __('auth.failed')]);
});

test('a soft-deleted user cannot sign in', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->post(route('login.store'), [
        'login' => $user->username,
        'password' => 'password',
    ])->assertSessionHasErrors(['login' => __('auth.failed')]);

    $this->assertGuest();
});

test('signing in stamps the last login time and address', function () {
    $user = User::factory()->create(['last_login_at' => null]);

    $this->post(route('login.store'), ['login' => $user->username, 'password' => 'password']);

    $user->refresh();

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip)->toBe('127.0.0.1');
});

test('a user deactivated mid-session is signed out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->update(['status' => 'I']);

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->username, '127.0.0.1'])), amount: 5);

    $this->post(route('login.store'), [
        'login' => $user->username,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});
