<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Admin → Users
|--------------------------------------------------------------------------
|
| Every action is gated by its own permission (route middleware) and every
| self-protection rule by a UserService guard that holds for a super admin too.
|
*/

/**
 * A valid new-user payload; override any field.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newUserPayload(array $overrides = []): array
{
    return [
        'name' => 'Jane Doe',
        'username' => 'jdoe',
        'email' => 'jane@example.com',
        'password' => compliantPassword(),
        'password_confirmation' => compliantPassword(),
        'phone' => '+880 1711-000000',
        'employee_code' => 'E-100',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/**
 * The edit payload for an existing user; override any field.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function editUserPayload(User $user, array $overrides = []): array
{
    return [
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'phone' => $user->phone,
        'employee_code' => $user->employee_code,
        'status' => $user->status->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $target = User::factory()->create();

    if ($trashed) {
        $target->delete();
    }

    $this->actingAs(userWithPermissions('admin.users.view'))
        ->{$method}(route($route, $target))
        ->assertForbidden();
})->with([
    'update' => ['put', 'admin.users.update', false],
    'delete' => ['delete', 'admin.users.destroy', false],
    'restore' => ['put', 'admin.users.restore', true],
    'force delete' => ['delete', 'admin.users.force-delete', true],
    'password' => ['put', 'admin.users.password', false],
    'roles' => ['put', 'admin.users.roles', false],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('admin.users.view'))
        ->post(route('admin.users.store'), newUserPayload())
        ->assertForbidden();
});

/*
| The list
*/

test('the list renders with its allow-lists and counts', function () {
    $this->actingAs(userWithPermissions('admin.users.view'));
    User::factory()->count(2)->create();
    User::factory()->create()->delete();

    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users.data', 3)
            ->where('counts', ['active' => 3, 'trashed' => 1])
            ->where('filters.view', 'active')
            ->where('sortable', User::SORTABLE)
            ->has('filterable.username'),
        );
});

test('the deleted tab lists only soft-deleted users', function () {
    $this->actingAs(userWithPermissions('admin.users.view'));
    $gone = User::factory()->create();
    $gone->delete();

    $this->get(route('admin.users.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $gone->id)
            ->where('users.data.0.deleted_at', $gone->fresh()->deleted_at->toDateString()),
        );
});

test('the list filters by role', function () {
    $this->actingAs(userWithPermissions('admin.users.view'));
    Role::findOrCreate('auditor', 'web');
    $auditor = User::factory()->create();
    $auditor->assignRole('auditor');

    $this->get(route('admin.users.index', ['filter' => ['role' => 'auditor']]))
        ->assertInertia(fn ($page) => $page->has('users.data', 1)->where('users.data.0.id', $auditor->id));
});

test('a non-super admin is not offered the super-admin role', function () {
    Role::findOrCreate(Role::SUPER_ADMIN, 'web');
    Role::findOrCreate('viewer', 'web');

    $this->actingAs(userWithPermissions('admin.users.view'))
        ->get(route('admin.users.index'))
        ->assertInertia(fn ($page) => $page->where('roles', ['viewer']));
});

/*
| Create
*/

test('a user can be created with roles', function () {
    Role::findOrCreate('viewer', 'web');
    $this->actingAs(userWithPermissions('admin.users.create'));

    $this->post(route('admin.users.store'), newUserPayload(['roles' => ['viewer']]))
        ->assertSessionHasNoErrors();

    $user = User::where('username', 'jdoe')->sole();

    expect($user->email)->toBe('jane@example.com')
        ->and($user->hasRole('viewer'))->toBeTrue()
        ->and(Hash::check(compliantPassword(), $user->password))->toBeTrue();

    assertToast($this->post(route('admin.users.store'), newUserPayload(['username' => 'other', 'email' => 'o@example.com', 'employee_code' => null])), 'success');
});

test('a new user must meet the password policy', function () {
    $this->actingAs(userWithPermissions('admin.users.create'))
        ->post(route('admin.users.store'), newUserPayload(['password' => 'password', 'password_confirmation' => 'password']))
        ->assertSessionHasErrors('password');
});

test('username, email and employee code are unique, deleted users included', function (string $field, string $value) {
    $existing = User::factory()->create(['username' => 'taken', 'email' => 'taken@example.com', 'employee_code' => 'E-9']);
    $existing->delete();

    $this->actingAs(userWithPermissions('admin.users.create'))
        ->post(route('admin.users.store'), newUserPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'username' => ['username', 'Taken'],
    'email' => ['email', 'taken@example.com'],
    'employee code' => ['employee_code', 'E-9'],
]);

test('a username may not look like an email', function () {
    $this->actingAs(userWithPermissions('admin.users.create'))
        ->post(route('admin.users.store'), newUserPayload(['username' => 'jane@example']))
        ->assertSessionHasErrors('username');
});

test('only a super admin may grant the super-admin role', function () {
    Role::findOrCreate(Role::SUPER_ADMIN, 'web');

    $this->actingAs(userWithPermissions('admin.users.create'))
        ->post(route('admin.users.store'), newUserPayload(['roles' => [Role::SUPER_ADMIN]]))
        ->assertSessionHasErrors('roles.0');

    expect(User::where('username', 'jdoe')->exists())->toBeFalse();
});

test('an avatar is stored on the public disk under a random name', function () {
    Storage::fake('public');
    $this->actingAs(userWithPermissions('admin.users.create'));

    $this->post(route('admin.users.store'), newUserPayload([
        'avatar' => UploadedFile::fake()->image('me.png', 200, 200),
    ]))->assertSessionHasNoErrors();

    $path = User::where('username', 'jdoe')->sole()->avatar_path;

    expect($path)->toStartWith('avatars/')->not->toContain('me.png');
    Storage::disk('public')->assertExists($path);
});

test('an avatar must be a small jpg, png or webp image', function (UploadedFile $file) {
    Storage::fake('public');

    $this->actingAs(userWithPermissions('admin.users.create'))
        ->post(route('admin.users.store'), newUserPayload(['avatar' => $file]))
        ->assertSessionHasErrors('avatar');
})->with([
    'too large' => fn () => UploadedFile::fake()->image('big.png')->size(3000),
    'not an image' => fn () => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
    'gif' => fn () => UploadedFile::fake()->image('anim.gif'),
]);

/*
| Update
*/

test('a user can be edited', function () {
    $user = User::factory()->create();

    $this->actingAs(userWithPermissions('admin.users.update'))
        ->put(route('admin.users.update', $user), editUserPayload($user, ['name' => 'Renamed', 'phone' => '0123']))
        ->assertSessionHasNoErrors();

    expect($user->refresh())->name->toBe('Renamed')->phone->toBe('0123');
});

test('a replaced avatar deletes the old file, and it can be removed', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs(userWithPermissions('admin.users.update'));

    $this->put(route('admin.users.update', $user), editUserPayload($user, ['avatar' => UploadedFile::fake()->image('a.png')]));
    $first = $user->refresh()->avatar_path;

    $this->put(route('admin.users.update', $user), editUserPayload($user, ['avatar' => UploadedFile::fake()->image('b.png')]));
    $second = $user->refresh()->avatar_path;

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->put(route('admin.users.update', $user), editUserPayload($user, ['remove_avatar' => true]));

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('nobody can deactivate themselves', function () {
    $actor = superAdmin();

    assertToast(
        $this->actingAs($actor)->put(route('admin.users.update', $actor), editUserPayload($actor, ['status' => 'I'])),
        'error',
    );

    expect($actor->refresh()->isActive())->toBeTrue();
});

test('the last active super admin cannot be deactivated', function () {
    $last = superAdmin();

    $this->actingAs(userWithPermissions('admin.users.update'))
        ->put(route('admin.users.update', $last), editUserPayload($last, ['status' => 'I']));

    expect($last->refresh()->isActive())->toBeTrue();
});

test('a super admin can be deactivated while another active one remains', function () {
    $one = superAdmin();
    superAdmin();

    $this->actingAs(userWithPermissions('admin.users.update'))
        ->put(route('admin.users.update', $one), editUserPayload($one, ['status' => 'I']));

    expect($one->refresh()->isActive())->toBeFalse();
});

/*
| Delete, restore, force delete
*/

test('a user is soft deleted and can be restored', function () {
    $user = User::factory()->create();
    $this->actingAs(userWithPermissions('admin.users.delete', 'admin.users.restore'));

    assertToast($this->delete(route('admin.users.destroy', $user)), 'success');
    expect($user->fresh()->trashed())->toBeTrue();

    $this->put(route('admin.users.restore', $user));
    expect($user->fresh()->trashed())->toBeFalse();
});

test('nobody can delete themselves', function () {
    $actor = superAdmin();
    superAdmin();

    assertToast($this->actingAs($actor)->delete(route('admin.users.destroy', $actor)), 'error');

    expect($actor->fresh()->trashed())->toBeFalse();
});

test('the last active super admin cannot be deleted', function () {
    $last = superAdmin();

    $this->actingAs(userWithPermissions('admin.users.delete'))->delete(route('admin.users.destroy', $last));

    expect($last->fresh()->trashed())->toBeFalse();
});

test('force delete removes the row and the avatar and records who it was', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/x.png', 'img');
    $user = User::factory()->create(['username' => 'leaver']);
    $user->forceFill(['avatar_path' => 'avatars/x.png'])->saveQuietly();
    $user->delete();

    $this->actingAs(userWithPermissions('admin.users.force-delete'));
    config(['audit.console' => true]);

    $this->delete(route('admin.users.force-delete', $user));

    expect(User::withTrashed()->find($user->id))->toBeNull();
    Storage::disk('public')->assertMissing('avatars/x.png');
    expect(AuditLog::where('event', 'force-deleted')->sole()->old_values['username'])->toBe('leaver');
});

/*
| Password and roles
*/

test('an administrator can set another user password, recorded as its own event', function () {
    $user = User::factory()->create();
    $this->actingAs(userWithPermissions('admin.users.reset-password'));
    config(['audit.console' => true]);

    $this->put(route('admin.users.password', $user), [
        'password' => compliantPassword(),
        'password_confirmation' => compliantPassword(),
    ])->assertSessionHasNoErrors();

    expect(Hash::check(compliantPassword(), $user->refresh()->password))->toBeTrue()
        ->and(AuditLog::where('event', 'password-reset')->where('auditable_id', $user->id)->exists())->toBeTrue();
});

test('roles are synced and the change is audited by name', function () {
    Role::findOrCreate('viewer', 'web');
    Role::findOrCreate('auditor', 'web');
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs(userWithPermissions('admin.users.assign-roles'));
    config(['audit.console' => true]);

    $this->put(route('admin.users.roles', $user), ['roles' => ['auditor']])->assertSessionHasNoErrors();

    $audit = AuditLog::where('event', 'roles-changed')->sole();

    expect($user->refresh()->getRoleNames()->all())->toBe(['auditor'])
        ->and($audit->old_values)->toBe(['roles' => ['viewer']])
        ->and($audit->new_values)->toBe(['roles' => ['auditor']]);
});

test('nobody can change their own roles', function () {
    $actor = superAdmin();

    assertToast($this->actingAs($actor)->put(route('admin.users.roles', $actor), ['roles' => []]), 'error');

    expect($actor->refresh()->hasRole(Role::SUPER_ADMIN))->toBeTrue();
});

test('only a super admin may revoke the super-admin role', function () {
    $target = superAdmin();
    superAdmin();

    $this->actingAs(userWithPermissions('admin.users.assign-roles'))
        ->put(route('admin.users.roles', $target), ['roles' => []]);

    expect($target->refresh()->hasRole(Role::SUPER_ADMIN))->toBeTrue();
});

test('the last active super admin keeps the role even when a super admin revokes it', function () {
    $last = superAdmin();
    $actor = superAdmin();
    $actor->update(['status' => 'I']);

    $this->actingAs($actor)->put(route('admin.users.roles', $last), ['roles' => []]);

    expect($last->refresh()->hasRole(Role::SUPER_ADMIN))->toBeTrue();
});

/*
| Availability
*/

test('availability reports taken usernames and emails, deleted users included', function () {
    $user = User::factory()->create(['username' => 'taken', 'email' => 'taken@example.com']);
    $user->delete();

    $this->actingAs(userWithPermissions('admin.users.create'));

    $this->getJson(route('admin.users.availability', ['field' => 'username', 'value' => 'TAKEN']))->assertJson(['available' => false]);
    $this->getJson(route('admin.users.availability', ['field' => 'email', 'value' => 'free@example.com']))->assertJson(['available' => true]);
    $this->getJson(route('admin.users.availability', ['field' => 'username', 'value' => 'taken', 'ignore' => $user->id]))->assertJson(['available' => true]);
});

test('availability refuses any other field', function () {
    $this->actingAs(userWithPermissions('admin.users.create'))
        ->getJson(route('admin.users.availability', ['field' => 'password', 'value' => 'x']))
        ->assertUnprocessable();
});
