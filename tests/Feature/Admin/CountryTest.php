<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Country;
use Database\Seeders\Admin\CountrySeeder;

/*
|--------------------------------------------------------------------------
| Admin → Countries
|--------------------------------------------------------------------------
|
| A group-wide master table seeded with the ISO 3166-1 list (ARCHITECTURE.md
| §9.14, owner 2026-11-02). Name and both ISO codes are unique across the
| table — ignoring case, deleted countries included.
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function countryPayload(array $overrides = []): array
{
    return [
        'name' => 'Bangladesh',
        'iso2' => 'BD',
        'iso3' => 'BGD',
        'numeric_code' => '050',
        'dial_code' => '+880',
        'currency_code' => 'BDT',
        'nationality' => 'Bangladeshi',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('admin.countries.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(userWithPermissions())
        ->get(route('admin.countries.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $country = Country::factory()->create();

    if ($trashed) {
        $country->delete();
    }

    $this->actingAs(userWithPermissions('admin.countries.view'))
        ->{$method}(route($route, $country), countryPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'admin.countries.update', false],
    'delete' => ['delete', 'admin.countries.destroy', false],
    'restore' => ['put', 'admin.countries.restore', true],
    'force delete' => ['delete', 'admin.countries.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('admin.countries.view'))
        ->post(route('admin.countries.store'), countryPayload())
        ->assertForbidden();
});

/*
| List
*/

test('the list shows the active countries and counts both tabs', function () {
    Country::factory()->create(['iso2' => 'QA']);
    Country::factory()->create(['iso2' => 'QB'])->delete();

    $this->actingAs(userWithPermissions('admin.countries.view'))
        ->get(route('admin.countries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/countries/index')
            ->has('countries.data', 1)
            ->where('countries.data.0.iso2', 'QA')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('the list filters by dial code prefix and sorts by name', function () {
    Country::factory()->create(['name' => 'Zeta Land', 'dial_code' => '+880']);
    Country::factory()->create(['name' => 'Alpha Land', 'dial_code' => '+88']);
    Country::factory()->create(['name' => 'Other Land', 'dial_code' => '+1']);

    $this->actingAs(userWithPermissions('admin.countries.view'))
        ->get(route('admin.countries.index', ['filter' => ['dial_code' => '+88'], 'sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn ($page) => $page
            ->has('countries.data', 2)
            ->where('countries.data.0.name', 'Alpha Land'),
        );
});

/*
| Validation
*/

test('a country is created with its codes uppercased, its name tidied and blank fields stored as null', function () {
    $this->actingAs(userWithPermissions('admin.countries.create'));

    assertToast($this->post(route('admin.countries.store'), countryPayload([
        'name' => ' Bangla   desh ',
        'iso2' => 'bd',
        'iso3' => ' bgd',
        'currency_code' => 'bdt',
        'nationality' => '',
        'numeric_code' => ' ',
    ])), 'success');

    $country = Country::sole();

    expect($country->name)->toBe('Bangla desh')
        ->and($country->iso2)->toBe('BD')
        ->and($country->iso3)->toBe('BGD')
        ->and($country->currency_code)->toBe('BDT')
        ->and($country->nationality)->toBeNull()
        ->and($country->numeric_code)->toBeNull();
});

test('malformed codes are refused', function (string $field, string $value) {
    $this->actingAs(userWithPermissions('admin.countries.create'))
        ->post(route('admin.countries.store'), countryPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'iso2 too long' => ['iso2', 'BGD'],
    'iso3 with a digit' => ['iso3', 'BG1'],
    'numeric code of two digits' => ['numeric_code', '50'],
    'dial code without plus' => ['dial_code', '880'],
    'currency of four letters' => ['currency_code', 'BDTT'],
]);

test('a name or ISO code already used is refused whatever its case', function (string $field, string $value) {
    Country::factory()->create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD']);

    $this->actingAs(userWithPermissions('admin.countries.create'))
        ->post(route('admin.countries.store'), countryPayload(['name' => 'Other', 'iso2' => 'QQ', 'iso3' => 'QQQ', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'name, other case' => ['name', 'BANGLADESH'],
    'iso2, lower case' => ['iso2', 'bd'],
    'iso3, lower case' => ['iso3', 'bgd'],
]);

test('dial and currency codes may repeat across countries', function () {
    Country::factory()->create(['dial_code' => '+1', 'currency_code' => 'USD']);

    $this->actingAs(userWithPermissions('admin.countries.create'))
        ->post(route('admin.countries.store'), countryPayload(['dial_code' => '+1', 'currency_code' => 'USD']))
        ->assertSessionHasNoErrors();
});

test('a deleted country keeps its name and ISO codes reserved and says to restore it', function () {
    Country::factory()->create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD'])->delete();

    $this->actingAs(userWithPermissions('admin.countries.create'))
        ->post(route('admin.countries.store'), countryPayload())
        ->assertSessionHasErrors([
            'name' => 'A deleted country is named "Bangladesh". Restore it from the Deleted tab instead.',
            'iso2' => 'The ISO code "BD" belongs to a deleted country. Restore it from the Deleted tab instead.',
            'iso3' => 'The ISO code "BGD" belongs to a deleted country. Restore it from the Deleted tab instead.',
        ]);
});

/*
| Writes
*/

test('a country can be edited, keeping its own values', function () {
    $country = Country::factory()->create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD']);

    $this->actingAs(userWithPermissions('admin.countries.update'))
        ->put(route('admin.countries.update', $country), countryPayload(['status' => RecordStatus::Inactive->value]))
        ->assertSessionHasNoErrors();

    expect($country->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a country is soft deleted, restored and permanently deleted', function () {
    $country = Country::factory()->create();

    $this->actingAs(userWithPermissions('admin.countries.delete', 'admin.countries.restore', 'admin.countries.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('admin.countries.destroy', $country)), 'success');
    expect($country->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('admin.countries.restore', $country)), 'success');
    expect($country->refresh()->trashed())->toBeFalse();

    $country->delete();
    assertToast($this->delete(route('admin.countries.force-delete', $country)), 'success');

    expect(Country::withTrashed()->find($country->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'country')->exists())->toBeTrue();
});

test('creating a country is audited', function () {
    $this->actingAs(userWithPermissions('admin.countries.create'));
    config(['audit.console' => true]);

    $this->post(route('admin.countries.store'), countryPayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'country')->sole();

    expect($audit->new_values['iso2'])->toBe('BD');
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $country = Country::factory()->create();

    $this->actingAs(userWithPermissions('admin.countries.view'))
        ->delete(route($route), ['ids' => [$country->id]])
        ->assertForbidden();
})->with(['admin.countries.bulk-destroy', 'admin.countries.bulk-force-delete']);

test('bulk delete moves the ticked countries to Deleted', function () {
    [$first, $second, $untouched] = Country::factory()->count(3)->create();

    $this->actingAs(userWithPermissions('admin.countries.delete'));

    $response = $this->delete(route('admin.countries.bulk-destroy'), ['ids' => [$first->id, $second->id]]);

    assertToast($response, 'success')->assertSessionHas('inertia.flash_data.toast.message', '2 countries moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($untouched->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes the ticked deleted countries and records each', function () {
    [$first, $second] = Country::factory()->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(userWithPermissions('admin.countries.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('admin.countries.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(Country::withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'country')->count())->toBe(2);
});

/*
| Seeder
*/

test('the seeder adds every ISO country once and is safe to re-run', function () {
    $this->seed(CountrySeeder::class);
    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(count(CountrySeeder::COUNTRIES))
        ->and(Country::where('iso2', 'BD')->sole()->nationality)->toBe('Bangladeshi');
});

test('the seeder never overwrites an edited, renamed or deleted country', function () {
    $this->seed(CountrySeeder::class);

    Country::where('iso2', 'BD')->sole()->update(['nationality' => 'Edited']);
    Country::where('iso2', 'IN')->sole()->update(['iso2' => 'QI', 'iso3' => 'QIN']);
    Country::where('iso2', 'NP')->sole()->delete();

    $this->seed(CountrySeeder::class);

    expect(Country::where('iso2', 'BD')->sole()->nationality)->toBe('Edited')
        ->and(Country::where('name', 'India')->count())->toBe(1)
        ->and(Country::where('iso2', 'IN')->exists())->toBeFalse()
        ->and(Country::withTrashed()->where('iso2', 'NP')->count())->toBe(1)
        ->and(Country::where('iso2', 'NP')->exists())->toBeFalse();
});
