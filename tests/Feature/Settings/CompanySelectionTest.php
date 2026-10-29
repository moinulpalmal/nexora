<?php

use App\Models\Admin\Company;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| The selected company
|--------------------------------------------------------------------------
|
| ARCHITECTURE.md §9.10: a user works in one usable company at a time —
| active, undeleted, granted (any active one for a super admin) — chosen in
| the header switcher and remembered on the user. The server checks the
| remembered choice on every request and falls back when it is no longer
| usable.
|
*/

test('a user switches to a granted company and it is remembered', function () {
    [$first, $second] = Company::factory()->count(2)->create();
    $user = User::factory()->create();
    $user->companies()->attach([$first->id, $second->id]);

    $response = $this->actingAs($user)->put(route('company-selection.update'), ['company_id' => $second->id]);

    assertToast($response, 'success');
    expect($user->refresh()->selected_company_id)->toBe($second->id);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.company.id', $second->id)
            ->has('auth.companies', 2),
        );
});

test('a company the user was not granted cannot be selected', function () {
    $company = Company::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('company-selection.update'), ['company_id' => $company->id])
        ->assertSessionHasErrors('company_id');
});

test('an inactive company cannot be selected, even by a super admin', function () {
    $company = Company::factory()->inactive()->create();

    $this->actingAs(superAdmin())
        ->put(route('company-selection.update'), ['company_id' => $company->id])
        ->assertSessionHasErrors('company_id');
});

test('a super admin may select any active company without a grant', function () {
    $company = Company::factory()->create();
    $admin = superAdmin();

    $this->actingAs($admin)
        ->put(route('company-selection.update'), ['company_id' => $company->id])
        ->assertSessionHasNoErrors();

    expect($admin->refresh()->selected_company_id)->toBe($company->id);
});

test('a user with exactly one usable company works in it without choosing', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.company.id', $company->id));

    expect($user->refresh()->selected_company_id)->toBe($company->id);
});

test('a user with several companies and no choice has none selected', function () {
    $user = User::factory()->create();
    $user->companies()->attach(Company::factory()->count(2)->create());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.company', null)->has('auth.companies', 2));
});

test('a selected company that is deactivated falls back to the only one left', function () {
    [$kept, $retired] = Company::factory()->count(2)->create();
    $user = User::factory()->create();
    $user->companies()->attach([$kept->id, $retired->id]);
    $user->forceFill(['selected_company_id' => $retired->id])->saveQuietly();

    $retired->update(['status' => 'I']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.company.id', $kept->id)
            ->has('auth.companies', 1),
        );

    expect($user->refresh()->selected_company_id)->toBe($kept->id);
});

test('a selected company whose grant is gone is cleared', function () {
    [$first, $second, $ungranted] = Company::factory()->count(3)->create();
    $user = User::factory()->create();
    $user->companies()->attach([$first->id, $second->id]);
    $user->forceFill(['selected_company_id' => $ungranted->id])->saveQuietly();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.company', null));

    expect($user->refresh()->selected_company_id)->toBeNull();
});
