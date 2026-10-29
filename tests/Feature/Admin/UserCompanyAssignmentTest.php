<?php

use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Admin → Users → Companies
|--------------------------------------------------------------------------
|
| Which companies a user may work in (ARCHITECTURE.md §9.10): its own
| permission, audited by id, never self-granted, and a selection the new set
| no longer covers is cleared.
|
*/

test('granting companies is refused without its permission', function () {
    $user = User::factory()->create();

    $this->actingAs(userWithPermissions('admin.users.view', 'admin.users.assign-roles'))
        ->put(route('admin.users.companies', $user), ['companies' => []])
        ->assertForbidden();
});

test('companies are synced and the change is audited by id', function () {
    [$first, $second] = Company::factory()->count(2)->create();
    $user = User::factory()->create();
    $user->companies()->attach($first);

    $this->actingAs(userWithPermissions('admin.users.assign-companies'));
    config(['audit.console' => true]);

    assertToast($this->put(route('admin.users.companies', $user), ['companies' => [$second->id]]), 'success');

    $audit = AuditLog::where('event', 'companies-changed')->sole();

    expect($user->companies()->pluck('companies.id')->all())->toBe([$second->id])
        ->and($audit->old_values)->toBe(['companies' => [$first->id]])
        ->and($audit->new_values)->toBe(['companies' => [$second->id]]);
});

test('sending no companies removes them all', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);

    $this->actingAs(userWithPermissions('admin.users.assign-companies'))
        ->put(route('admin.users.companies', $user))
        ->assertSessionHasNoErrors();

    expect($user->companies()->count())->toBe(0);
});

test('removing the selected company clears the selection', function () {
    [$kept, $removed] = Company::factory()->count(2)->create();
    $user = User::factory()->create();
    $user->companies()->attach([$kept->id, $removed->id]);
    $user->forceFill(['selected_company_id' => $removed->id])->saveQuietly();

    $this->actingAs(userWithPermissions('admin.users.assign-companies'))
        ->put(route('admin.users.companies', $user), ['companies' => [$kept->id]]);

    expect($user->refresh()->selected_company_id)->toBeNull();
});

test('a deleted company cannot be granted', function () {
    $company = Company::factory()->create();
    $company->delete();

    $this->actingAs(userWithPermissions('admin.users.assign-companies'))
        ->put(route('admin.users.companies', User::factory()->create()), ['companies' => [$company->id]])
        ->assertSessionHasErrors('companies.0');
});

test('nobody can change their own companies', function () {
    $actor = userWithPermissions('admin.users.assign-companies');

    assertToast(
        $this->actingAs($actor)->put(route('admin.users.companies', $actor), ['companies' => [Company::factory()->create()->id]]),
        'error',
    );

    expect($actor->companies()->count())->toBe(0);
});

test('the users list carries each user\'s companies, and the options only for a granter', function () {
    $company = Company::factory()->create();
    User::factory()->create()->companies()->attach($company);

    $this->actingAs(userWithPermissions('admin.users.view'))
        ->get(route('admin.users.index'))
        ->assertInertia(fn ($page) => $page->where('companies', []));

    $this->actingAs(userWithPermissions('admin.users.view', 'admin.users.assign-companies'))
        ->get(route('admin.users.index'))
        ->assertInertia(fn ($page) => $page
            ->has('companies', 1)
            ->where('companies.0.id', $company->id),
        );
});
