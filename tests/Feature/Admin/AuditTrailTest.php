<?php

use App\Enums\Admin\AuditEvent;
use App\Listeners\Admin\RecordFailedLogin;
use App\Listeners\Admin\RecordLogin;
use App\Listeners\Admin\RecordLogout;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Role;
use App\Models\User;
use App\Services\Admin\AuditRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

/*
|--------------------------------------------------------------------------
| The audit trail — the mechanism (ARCHITECTURE.md §9.3)
|--------------------------------------------------------------------------
|
| **A test that exercises writing calls `auditing()` first.** The suite runs in
| console and `config('audit.console')` is false, so auditing is off unless a
| test turns it on. The flag is read per write, so setting it inside the test
| is enough. Call it *after* any fixture whose creation should not appear.
|
*/

function auditing(): void
{
    config(['audit.console' => true]);
}

test('creating an audited model records the new values and the actor', function () {
    $actor = User::factory()->create(['name' => 'Ada Admin', 'username' => 'ada']);
    $this->actingAs($actor);

    auditing();

    $user = User::factory()->create(['name' => 'New Person']);

    $audit = AuditLog::query()->where('event', 'created')->sole();

    expect($audit->auditable_type)->toBe('user')
        ->and($audit->auditable_id)->toBe($user->id)
        ->and($audit->user_id)->toBe($actor->id)
        ->and($audit->actor_name)->toBe('Ada Admin')
        ->and($audit->actor_username)->toBe('ada')
        ->and($audit->new_values['name'])->toBe('New Person');
});

test('updating an audited model records only the columns that changed', function () {
    $user = User::factory()->create(['name' => 'Before', 'phone' => '111']);
    $this->actingAs(User::factory()->create());

    auditing();

    $user->update(['name' => 'After']);

    $audit = AuditLog::query()->where('event', 'updated')->sole();

    expect($audit->old_values)->toBe(['name' => 'Before'])
        ->and($audit->new_values)->toBe(['name' => 'After']);
});

test('a credential never reaches the audit trail', function () {
    $this->actingAs(User::factory()->create());

    auditing();

    $user = User::factory()->create();
    $user->update(['password' => compliantPassword()]);

    AuditLog::all()->each(function (AuditLog $audit): void {
        expect(array_keys($audit->old_values ?? []))->not->toContain('password', 'remember_token')
            ->and(array_keys($audit->new_values ?? []))->not->toContain('password', 'remember_token');
    });
});

test('the actor survives the actor being deleted', function () {
    $actor = User::factory()->create(['name' => 'Gone Soon']);
    $this->actingAs($actor);

    auditing();

    User::factory()->create();
    $actor->delete();

    expect(AuditLog::query()->where('event', 'created')->sole()->actor_name)->toBe('Gone Soon');
});

test('a write with no authenticated actor records no actor rather than no audit', function () {
    auditing();

    User::factory()->create();

    $audit = AuditLog::query()->where('event', 'created')->sole();

    expect($audit->user_id)->toBeNull()
        ->and($audit->actor_name)->toBeNull();
});

test('console writes leave no trail when the console flag is off', function () {
    User::factory()->create()->update(['name' => 'Changed']);

    expect(AuditLog::count())->toBe(0);
});

test('the recorder honours the console rule the package applies to model events', function () {
    $user = User::factory()->create();

    app(AuditRecorder::class)->record($user, AuditEvent::RolesChanged, ['roles' => []], ['roles' => ['admin']]);

    expect(AuditLog::count())->toBe(0);
});

test('a custom event is written through the package with the actor stamped', function () {
    $actor = User::factory()->create(['username' => 'ada']);
    $subject = User::factory()->create();
    $this->actingAs($actor);

    auditing();

    app(AuditRecorder::class)->record($subject, AuditEvent::RolesChanged, ['roles' => []], ['roles' => ['admin']]);

    $audit = AuditLog::query()->where('event', 'roles-changed')->sole();

    expect($audit->auditable_type)->toBe('user')
        ->and($audit->auditable_id)->toBe($subject->id)
        ->and($audit->actor_username)->toBe('ada')
        ->and($audit->new_values)->toBe(['roles' => ['admin']]);
});

test('a staged custom event does not leak into the next ordinary save', function () {
    $subject = User::factory()->create();
    $this->actingAs(User::factory()->create());

    auditing();

    app(AuditRecorder::class)->record($subject, AuditEvent::RolesChanged, [], ['roles' => ['admin']]);
    $subject->update(['name' => 'Renamed']);

    expect(AuditLog::query()->where('event', 'updated')->sole()->new_values)->toBe(['name' => 'Renamed']);
});

test('recordChange writes nothing when nothing changed', function () {
    $subject = User::factory()->create();
    $this->actingAs(User::factory()->create());

    auditing();

    app(AuditRecorder::class)->recordChange($subject, AuditEvent::RolesChanged, ['roles' => ['admin']], ['roles' => ['admin']]);

    expect(AuditLog::count())->toBe(0);
});

test('roles are audited models with a morph alias', function () {
    $this->actingAs(User::factory()->create());

    auditing();

    $role = Role::findOrCreate('auditor', 'web');

    expect(AuditLog::query()->where('event', 'created')->sole()->auditable_type)->toBe('role')
        ->and(AuditLog::query()->where('event', 'created')->sole()->auditable_id)->toBe($role->id);
});

test('a successful sign-in is recorded against the user', function () {
    $user = User::factory()->create();

    auditing();

    $this->post(route('login.store'), ['login' => $user->username, 'password' => 'password']);

    $audit = AuditLog::query()->where('event', 'logged-in')->sole();

    expect($audit->auditable_id)->toBe($user->id)
        ->and($audit->new_values['username'])->toBe($user->username);
});

test('the sign-in stamp writes no updated row', function () {
    $user = User::factory()->create();

    auditing();

    $this->post(route('login.store'), ['login' => $user->username, 'password' => 'password']);

    expect(AuditLog::query()->where('event', 'updated')->exists())->toBeFalse();
});

test('a failed sign-in records the login against the account and never the password', function () {
    $user = User::factory()->create(['username' => 'jdoe']);

    auditing();

    $this->post(route('login.store'), ['login' => 'jdoe', 'password' => 'wrong-secret']);

    $audit = AuditLog::query()->where('event', 'login-failed')->sole();

    expect($audit->auditable_id)->toBe($user->id)
        ->and($audit->new_values)->toMatchArray(['login' => 'jdoe', 'user_exists' => true])
        ->and(json_encode($audit->getAttributes()))->not->toContain('wrong-secret');
});

test('a failed sign-in for an unknown login is recorded without a subject', function () {
    auditing();

    $this->post(route('login.store'), ['login' => 'nobody', 'password' => 'whatever']);

    $audit = AuditLog::query()->where('event', 'login-failed')->sole();

    expect($audit->auditable_type)->toBeNull()
        ->and($audit->new_values)->toMatchArray(['login' => 'nobody', 'user_exists' => false]);
});

test('signing out is recorded', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    auditing();

    $this->post(route('logout'));

    expect(AuditLog::query()->where('event', 'logged-out')->sole()->auditable_id)->toBe($user->id);
});

test('the sign-in listeners are discovered exactly once', function (string $event, string $listener) {
    $registered = collect(Event::getRawListeners()[$event] ?? [])
        ->filter(fn ($entry): bool => is_string($entry) && str_starts_with($entry, $listener));

    expect($registered)->toHaveCount(1);
})->with([
    'login' => [Login::class, RecordLogin::class],
    'logout' => [Logout::class, RecordLogout::class],
    'failed' => [Failed::class, RecordFailedLogin::class],
]);

test('the actor observer stamps who created and who last changed a user', function () {
    $creator = User::factory()->create();
    $this->actingAs($creator);

    $user = User::factory()->create();

    expect($user->inserted_by)->toBe($creator->id);

    $editor = User::factory()->create();
    $this->actingAs($editor);

    $user->update(['name' => 'Edited']);

    expect($user->refresh()->last_updated_by)->toBe($editor->id);
});
