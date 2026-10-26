<?php

use App\Models\Admin\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * The profile payload for the user as they are; override any field.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function profilePayload(User $user, array $overrides = []): array
{
    return [
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'phone' => $user->phone,
        ...$overrides,
    ];
}

test('profile page is displayed', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk();
});

test('name and phone change without the password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, ['name' => 'New Name', 'phone' => '0123']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh())->name->toBe('New Name')->phone->toBe('0123');
});

test('changing the username or email asks for the current password', function (string $field, string $value) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [$field => $value]))
        ->assertSessionHasErrors('current_password');

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [$field => $value, 'current_password' => 'wrong']))
        ->assertSessionHasErrors('current_password');

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [$field => $value, 'current_password' => 'password']))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->{$field})->toBe($value);
})->with([
    'username' => ['username', 'renamed'],
    'email' => ['email', 'renamed@example.com'],
]);

test('re-sending the same username in other case is not a change', function () {
    $user = User::factory()->create(['username' => 'jdoe']);

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, ['username' => 'JDoe']))
        ->assertSessionHasNoErrors();
});

test('an email change is audited with the actor being the user', function () {
    // Re-read, as a real request would: a factory instance lacks the columns it
    // never set, so a null phone would read as a change.
    $user = User::factory()->create(['email' => 'old@example.com'])->fresh();
    $this->actingAs($user);
    config(['audit.console' => true]);

    $this->patch(route('profile.update'), profilePayload($user, ['email' => 'new@example.com', 'current_password' => 'password']));

    $audit = AuditLog::where('event', 'updated')->sole();

    expect($audit->user_id)->toBe($user->id)
        ->and($audit->new_values)->toBe(['email' => 'new@example.com']);
});

test('users cannot change their own status, roles or employee code here', function () {
    $user = User::factory()->create(['employee_code' => 'E-1']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'status' => 'I',
        'employee_code' => 'E-2',
        'roles' => ['super-admin'],
    ]));

    expect($user->refresh())
        ->isActive()->toBeTrue()
        ->employee_code->toBe('E-1')
        ->and($user->roles)->toBeEmpty();
});

test('a user can set and remove their own photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patch(route('profile.update'), profilePayload($user, ['avatar' => UploadedFile::fake()->image('me.png')]))
        ->assertSessionHasNoErrors();

    $path = $user->refresh()->avatar_path;
    Storage::disk('public')->assertExists($path);

    $this->patch(route('profile.update'), profilePayload($user, ['remove_avatar' => true]));

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('there is no self-delete route', function () {
    expect(Route::has('profile.destroy'))->toBeFalse();
});
