<?php

use App\Models\Admin\Role;
use App\Models\User;
use App\Notifications\Admin\AccountChanged;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| The bell
|--------------------------------------------------------------------------
|
| Database notifications, pushed live over Reverb. The suite's broadcaster is
| `null` (phpunit.xml), so a push never needs a running server here.
|
*/

function notifyUser(User $user, string $title = 'Hello'): void
{
    $user->notify(new AccountChanged($title, 'Body text'));
}

test('the bell lists only the signed-in user notifications, newest first', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    notifyUser($me, 'First');
    $this->travel(1)->minutes();
    notifyUser($me, 'Second');
    notifyUser($other, 'Not mine');

    $this->actingAs($me)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread_count', 2)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.data.title', 'Second')
        ->assertJsonPath('data.0.data.url', '/settings/profile');
});

test('a notification is marked read, and only the owner can do it', function () {
    $me = User::factory()->create();
    notifyUser($me);
    $id = $me->notifications()->sole()->id;

    $this->actingAs(User::factory()->create())
        ->put(route('notifications.read', $id))
        ->assertNotFound();

    $this->actingAs($me)->put(route('notifications.read', $id));

    expect($me->unreadNotifications()->count())->toBe(0);
});

test('mark all read clears only my unread count', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();
    notifyUser($me);
    notifyUser($me);
    notifyUser($other);

    $this->actingAs($me)->put(route('notifications.read-all'));

    expect($me->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});

test('the unread count is shared with every page, null for guests', function () {
    $me = User::factory()->create();
    notifyUser($me);

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('notifications.unread_count', 1));

    auth()->logout();

    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('notifications', null));
});

test('the broadcasting prop carries the public key and never the secret', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'public-key',
        'broadcasting.connections.reverb.secret' => 'top-secret',
        'broadcasting.connections.reverb.options' => ['host' => 'ws.example.com', 'port' => 8080, 'scheme' => 'http'],
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('broadcasting', [
            'key' => 'public-key',
            'host' => 'ws.example.com',
            'port' => 8080,
            'scheme' => 'http',
        ]))
        ->assertDontSee('top-secret');
});

test('there is no live push without Reverb', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('broadcasting', null));
});

test('a user is told when an administrator changes their roles, and not for a no-op', function () {
    Notification::fake();
    Role::findOrCreate('viewer', 'web');
    $user = User::factory()->create();
    $this->actingAs(userWithPermissions('admin.users.assign-roles'));

    $this->put(route('admin.users.roles', $user), ['roles' => ['viewer']]);
    Notification::assertSentToTimes($user, AccountChanged::class, 1);

    $this->put(route('admin.users.roles', $user), ['roles' => ['viewer']]);
    Notification::assertSentToTimes($user, AccountChanged::class, 1);
});

test('a user is told when an administrator resets their password', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs(userWithPermissions('admin.users.reset-password'))
        ->put(route('admin.users.password', $user), [
            'password' => compliantPassword(),
            'password_confirmation' => compliantPassword(),
        ]);

    Notification::assertSentTo($user, AccountChanged::class, fn (AccountChanged $notification): bool => $notification->tone === 'warning');
});

test('the private user channel admits only its owner', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $callback = Broadcast::getChannels()->get('App.Models.User.{id}');

    expect($callback($me, $me->id))->toBeTrue()
        ->and($callback($me, $other->id))->toBeFalse();
});
