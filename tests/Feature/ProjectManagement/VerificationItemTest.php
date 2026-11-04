<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\VerificationItem;
use App\Models\User;
use Database\Seeders\Admin\RolePermissionSeeder;

/*
|--------------------------------------------------------------------------
| Projects → Settings → Verification Items
|--------------------------------------------------------------------------
|
| Company-scoped as Projects → Settings → Product Categories (ARCHITECTURE.md §9.10), but
| with no code: the name is unique per company — ignoring case, deleted rows
| included (owner, 2026-11-03). Nothing references an item yet, so no delete
| is refused.
|
*/

/**
 * A user holding the permissions *and* working in the company.
 */
function verificationItemActor(Company $company, string ...$permissions): User
{
    $user = userWithPermissions(...$permissions);
    $user->companies()->attach($company);
    $user->forceFill(['selected_company_id' => $company->id])->saveQuietly();

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function verificationItemPayload(array $overrides = []): array
{
    return [
        'name' => 'Trade licence checked',
        'description' => null,
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.settings.verification-items.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(verificationItemActor(Company::factory()->create()))
        ->get(route('projects.settings.verification-items.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();
    $item = VerificationItem::factory()->for($company)->create();

    if ($trashed) {
        $item->delete();
    }

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.view'))
        ->{$method}(route($route, $item), verificationItemPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.settings.verification-items.update', false],
    'delete' => ['delete', 'projects.settings.verification-items.destroy', false],
    'restore' => ['put', 'projects.settings.verification-items.restore', true],
    'force delete' => ['delete', 'projects.settings.verification-items.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(verificationItemActor(Company::factory()->create(), 'projects.settings.verification-items.view'))
        ->post(route('projects.settings.verification-items.store'), verificationItemPayload())
        ->assertForbidden();
});

test('the project-manager role holds every verification item permission and the viewer only view', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create()->assignRole('project-manager');
    $viewer = User::factory()->create()->assignRole('viewer');

    foreach (['view', 'create', 'update', 'delete', 'restore', 'force-delete'] as $action) {
        expect($manager->can("projects.settings.verification-items.{$action}"))->toBeTrue()
            ->and($viewer->can("projects.settings.verification-items.{$action}"))->toBe($action === 'view');
    }
});

/*
| Listing and scoping
*/

test('the list shows only the selected company\'s items', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    VerificationItem::factory()->for($mine)->create(['name' => 'Mine']);
    VerificationItem::factory()->for($theirs)->create(['name' => 'Theirs']);

    $this->actingAs(verificationItemActor($mine, 'projects.settings.verification-items.view'))
        ->get(route('projects.settings.verification-items.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/settings/verification-items/index')
            ->where('company.id', $mine->id)
            ->has('items.data', 1)
            ->where('items.data.0.name', 'Mine')
            ->where('counts', ['active' => 1, 'trashed' => 0]),
        );
});

test('the deleted tab lists only deleted items', function () {
    $company = Company::factory()->create();
    VerificationItem::factory()->for($company)->create(['name' => 'Live']);
    VerificationItem::factory()->for($company)->create(['name' => 'Gone'])->delete();

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.view'))
        ->get(route('projects.settings.verification-items.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.name', 'Gone')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('the list filters by name and status', function () {
    $company = Company::factory()->create();
    VerificationItem::factory()->for($company)->create(['name' => 'Trade licence checked']);
    VerificationItem::factory()->for($company)->inactive()->create(['name' => 'Tax licence checked']);
    VerificationItem::factory()->for($company)->create(['name' => 'Site visited']);

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.view'))
        ->get(route('projects.settings.verification-items.index', ['filter' => ['name' => 'licence', 'status' => RecordStatus::Active->value]]))
        ->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.name', 'Trade licence checked'),
        );
});

test('another company\'s item is a 404', function (string $method, string $route) {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = VerificationItem::factory()->for($theirs)->create();

    $this->actingAs(verificationItemActor($mine, 'projects.settings.verification-items.update', 'projects.settings.verification-items.delete'))
        ->{$method}(route($route, $foreign), verificationItemPayload())
        ->assertNotFound();
})->with([
    'update' => ['put', 'projects.settings.verification-items.update'],
    'delete' => ['delete', 'projects.settings.verification-items.destroy'],
]);

test('with no company selected the page says so and lists nothing', function () {
    VerificationItem::factory()->create();
    $user = userWithPermissions('projects.settings.verification-items.view');
    $user->companies()->attach(Company::factory()->count(2)->create());

    $this->actingAs($user)
        ->get(route('projects.settings.verification-items.index'))
        ->assertInertia(fn ($page) => $page
            ->where('company', null)
            ->has('items.data', 0),
        );
});

test('an item lands in the selected company whatever company_id is posted', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();

    $this->actingAs(verificationItemActor($mine, 'projects.settings.verification-items.create'));

    assertToast($this->post(route('projects.settings.verification-items.store'), verificationItemPayload(['company_id' => $theirs->id])), 'success');

    expect(VerificationItem::withoutGlobalScopes()->sole()->company_id)->toBe($mine->id);
});

test('creating without a selected company is refused', function () {
    $user = userWithPermissions('projects.settings.verification-items.create');

    assertToast($this->actingAs($user)->post(route('projects.settings.verification-items.store'), verificationItemPayload()), 'warning');

    expect(VerificationItem::withoutGlobalScopes()->count())->toBe(0);
});

/*
| Validation, writes and duplicates
*/

test('an item is created with its name tidied and an empty description saved as null', function () {
    $company = Company::factory()->create();

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.create'))
        ->post(route('projects.settings.verification-items.store'), verificationItemPayload([
            'name' => ' Trade   licence checked ',
            'description' => '',
        ]))
        ->assertSessionHasNoErrors();

    $item = VerificationItem::withoutGlobalScopes()->sole();

    expect($item->name)->toBe('Trade licence checked')
        ->and($item->description)->toBeNull();
});

test('a malformed field is refused', function (string $field, string $value) {
    $this->actingAs(verificationItemActor(Company::factory()->create(), 'projects.settings.verification-items.create'))
        ->post(route('projects.settings.verification-items.store'), verificationItemPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'name missing' => ['name', ''],
    'name too long' => ['name', str_repeat('n', 101)],
    'status out of range' => ['status', 'X'],
]);

test('a name already used in the company is refused whatever its case', function () {
    $company = Company::factory()->create();
    VerificationItem::factory()->for($company)->create(['name' => 'Trade licence checked']);

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.create'))
        ->post(route('projects.settings.verification-items.store'), verificationItemPayload(['name' => 'TRADE LICENCE CHECKED']))
        ->assertSessionHasErrors(['name' => 'A verification item named "TRADE LICENCE CHECKED" already exists in this company.']);
});

test('the same name is allowed in another company', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    VerificationItem::factory()->for($theirs)->create(['name' => 'Trade licence checked']);

    $this->actingAs(verificationItemActor($mine, 'projects.settings.verification-items.create'))
        ->post(route('projects.settings.verification-items.store'), verificationItemPayload())
        ->assertSessionHasNoErrors();

    expect(VerificationItem::withoutGlobalScopes()->where('name', 'Trade licence checked')->count())->toBe(2);
});

test('a deleted item keeps its name reserved and says to restore it', function () {
    $company = Company::factory()->create();
    VerificationItem::factory()->for($company)->create(['name' => 'Trade licence checked'])->delete();

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.create'))
        ->post(route('projects.settings.verification-items.store'), verificationItemPayload())
        ->assertSessionHasErrors([
            'name' => 'A deleted verification item in this company is named "Trade licence checked". Restore it from the Deleted tab instead.',
        ]);
});

test('an item can be edited, keeping its own name', function () {
    $company = Company::factory()->create();
    $item = VerificationItem::factory()->for($company)->create(['name' => 'Trade licence checked']);

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.update'))
        ->put(route('projects.settings.verification-items.update', $item), verificationItemPayload(['status' => RecordStatus::Inactive->value, 'description' => 'Seen in the original.']))
        ->assertSessionHasNoErrors();

    expect($item->refresh()->status)->toBe(RecordStatus::Inactive)
        ->and($item->description)->toBe('Seen in the original.');
});

test('an item is soft deleted, restored and permanently deleted', function () {
    $company = Company::factory()->create();
    $item = VerificationItem::factory()->for($company)->create();

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.delete', 'projects.settings.verification-items.restore', 'projects.settings.verification-items.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.settings.verification-items.destroy', $item)), 'success');
    expect($item->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.settings.verification-items.restore', $item)), 'success');
    expect($item->refresh()->trashed())->toBeFalse();

    $item->delete();
    assertToast($this->delete(route('projects.settings.verification-items.force-delete', $item)), 'success');

    expect(VerificationItem::withoutGlobalScopes()->withTrashed()->find($item->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'verification-item')->exists())->toBeTrue();
});

test('creating an item is audited with its company', function () {
    $company = Company::factory()->create();

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.create'));
    config(['audit.console' => true]);

    $this->post(route('projects.settings.verification-items.store'), verificationItemPayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'verification-item')->sole();

    expect($audit->new_values['name'])->toBe('Trade licence checked')
        ->and((int) $audit->new_values['company_id'])->toBe($company->id);
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $company = Company::factory()->create();
    $item = VerificationItem::factory()->for($company)->create();

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.view'))
        ->delete(route($route), ['ids' => [$item->id]])
        ->assertForbidden();
})->with(['projects.settings.verification-items.bulk-destroy', 'projects.settings.verification-items.bulk-force-delete']);

test('bulk delete moves the ticked items to Deleted, never another company\'s', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    [$first, $second] = VerificationItem::factory()->for($mine)->count(2)->create();
    $foreign = VerificationItem::factory()->for($theirs)->create();

    $this->actingAs(verificationItemActor($mine, 'projects.settings.verification-items.delete'));

    $response = $this->delete(route('projects.settings.verification-items.bulk-destroy'), ['ids' => [$first->id, $second->id, $foreign->id]]);

    assertToast($response, 'success')->assertSessionHas('inertia.flash_data.toast.message', '2 verification items moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($foreign->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes the ticked deleted items and records each', function () {
    $company = Company::factory()->create();
    [$first, $second] = VerificationItem::factory()->for($company)->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(verificationItemActor($company, 'projects.settings.verification-items.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.settings.verification-items.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(VerificationItem::withoutGlobalScopes()->withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'verification-item')->count())->toBe(2);
});
