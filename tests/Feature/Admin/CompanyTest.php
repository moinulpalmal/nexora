<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Admin → Companies
|--------------------------------------------------------------------------
|
| The Company master (ARCHITECTURE.md §9.10): a global, restorable table whose
| name and short code stay unique — ignoring case, deleted rows included — and
| which cannot be deleted while a department belongs to it (§9.14).
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function companyPayload(array $overrides = []): array
{
    return [
        'name' => 'Palmal Garments Ltd.',
        'short_code' => 'PGL',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('admin.companies.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.companies.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();

    if ($trashed) {
        $company->delete();
    }

    $this->actingAs(userWithPermissions('admin.companies.view'))
        ->{$method}(route($route, $company), companyPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'admin.companies.update', false],
    'delete' => ['delete', 'admin.companies.destroy', false],
    'restore' => ['put', 'admin.companies.restore', true],
    'force delete' => ['delete', 'admin.companies.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('admin.companies.view'))
        ->post(route('admin.companies.store'), companyPayload())
        ->assertForbidden();
});

/*
| The list
*/

test('the list renders with its allow-lists and counts', function () {
    $this->actingAs(userWithPermissions('admin.companies.view'));
    Company::factory()->count(2)->create();
    Company::factory()->create()->delete();

    $this->get(route('admin.companies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/companies/index')
            ->has('companies.data', 2)
            ->where('counts', ['active' => 2, 'trashed' => 1])
            ->where('sortable', Company::SORTABLE)
            ->has('filterable.short_code'),
        );
});

test('the list filters by short code prefix and lists deleted companies on their tab', function () {
    $this->actingAs(userWithPermissions('admin.companies.view'));
    Company::factory()->create(['short_code' => 'PGL']);
    Company::factory()->create(['short_code' => 'XPG']);
    $gone = Company::factory()->create(['short_code' => 'OLD']);
    $gone->delete();

    $this->get(route('admin.companies.index', ['filter' => ['short_code' => 'PG']]))
        ->assertInertia(fn ($page) => $page->has('companies.data', 1)->where('companies.data.0.short_code', 'PGL'));

    $this->get(route('admin.companies.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page->has('companies.data', 1)->where('companies.data.0.id', $gone->id));
});

/*
| Writes and duplicates
*/

test('a company is created with its name tidied and its code uppercased', function () {
    $this->actingAs(userWithPermissions('admin.companies.create'));

    $response = $this->post(route('admin.companies.store'), companyPayload([
        'name' => '  Palmal   Garments  Ltd. ',
        'short_code' => ' pgl ',
    ]));

    assertToast($response, 'success');

    $company = Company::query()->sole();

    expect($company->name)->toBe('Palmal Garments Ltd.')
        ->and($company->short_code)->toBe('PGL')
        ->and($company->inserted_by)->toBe(auth()->id());
});

test('a name or short code already used is refused whatever its case or spacing', function (string $field, string $value) {
    Company::factory()->create(['name' => 'Palmal Garments Ltd.', 'short_code' => 'PGL']);

    $this->actingAs(userWithPermissions('admin.companies.create'))
        ->post(route('admin.companies.store'), companyPayload(['name' => 'Other', 'short_code' => 'OTH', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'name, other case' => ['name', 'palmal garments ltd.'],
    'name, extra spaces' => ['name', ' Palmal  Garments Ltd. '],
    'short code, lower case' => ['short_code', 'pgl'],
]);

test('a deleted company keeps its values reserved and says to restore it', function () {
    Company::factory()->create(['name' => 'Palmal Garments Ltd.', 'short_code' => 'PGL'])->delete();

    $this->actingAs(userWithPermissions('admin.companies.create'))
        ->post(route('admin.companies.store'), companyPayload())
        ->assertSessionHasErrors([
            'name' => 'A deleted company is named "Palmal Garments Ltd.". Restore it from the Deleted tab instead.',
            'short_code' => 'The short code "PGL" belongs to a deleted company. Restore it from the Deleted tab instead.',
        ]);
});

test('a company can be edited, keeping its own values', function () {
    $company = Company::factory()->create(['name' => 'Palmal Garments Ltd.', 'short_code' => 'PGL']);

    $this->actingAs(userWithPermissions('admin.companies.update'))
        ->put(route('admin.companies.update', $company), companyPayload(['status' => RecordStatus::Inactive->value]))
        ->assertSessionHasNoErrors();

    expect($company->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a short code must be letters and digits', function () {
    $this->actingAs(userWithPermissions('admin.companies.create'))
        ->post(route('admin.companies.store'), companyPayload(['short_code' => 'P G!']))
        ->assertSessionHasErrors('short_code');
});

test('a company is soft deleted and restored', function () {
    $company = Company::factory()->create();

    $this->actingAs(userWithPermissions('admin.companies.delete', 'admin.companies.restore'));

    assertToast($this->delete(route('admin.companies.destroy', $company)), 'success');
    expect($company->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('admin.companies.restore', $company)), 'success');
    expect($company->refresh()->trashed())->toBeFalse();
});

test('a company with departments cannot be deleted, deleted departments included', function () {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create()->delete();

    $this->actingAs(userWithPermissions('admin.companies.delete'));

    assertToast($this->delete(route('admin.companies.destroy', $company)), 'warning');

    expect($company->refresh()->trashed())->toBeFalse();
});

test('a deleted company with departments cannot be permanently deleted', function () {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create();
    $company->delete();

    $this->actingAs(userWithPermissions('admin.companies.force-delete'));

    assertToast($this->delete(route('admin.companies.force-delete', $company)), 'warning');

    expect(Company::withTrashed()->find($company->id))->not->toBeNull();
});

test('force delete removes the row and records what it was', function () {
    $company = Company::factory()->create(['short_code' => 'GONE']);
    $company->delete();

    $this->actingAs(userWithPermissions('admin.companies.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('admin.companies.force-delete', $company)), 'success');

    expect(Company::withTrashed()->find($company->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->sole()->old_values['short_code'])->toBe('GONE');
});

test('creating a company is audited', function () {
    $this->actingAs(userWithPermissions('admin.companies.create'));
    config(['audit.console' => true]);

    $this->post(route('admin.companies.store'), companyPayload());

    expect(AuditLog::where('event', 'created')->where('auditable_type', 'company')->sole()->new_values['short_code'])->toBe('PGL');
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $company = Company::factory()->create();

    $this->actingAs(userWithPermissions('admin.companies.view'))
        ->delete(route($route), ['ids' => [$company->id]])
        ->assertForbidden();
})->with(['admin.companies.bulk-destroy', 'admin.companies.bulk-force-delete']);

test('bulk delete moves the ticked companies to Deleted', function () {
    [$first, $second, $untouched] = Company::factory()->count(3)->create();

    $this->actingAs(userWithPermissions('admin.companies.delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('admin.companies.bulk-destroy'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(Company::onlyTrashed()->pluck('id')->sort()->values()->all())->toBe([$first->id, $second->id])
        ->and($untouched->refresh()->trashed())->toBeFalse()
        ->and(AuditLog::where('event', 'deleted')->where('auditable_type', 'company')->count())->toBe(2);
});

test('bulk delete skips a company departments belong to and says so', function () {
    [$free, $inUse] = Company::factory()->count(2)->create();
    Department::factory()->for($inUse)->create();

    $this->actingAs(userWithPermissions('admin.companies.delete'));

    $response = $this->delete(route('admin.companies.bulk-destroy'), ['ids' => [$free->id, $inUse->id]]);

    assertToast($response, 'warning');
    expect($free->refresh()->trashed())->toBeTrue()
        ->and($inUse->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes only ticked companies that are already deleted', function () {
    [$deleted, $live] = Company::factory()->count(2)->create();
    $deleted->delete();

    $this->actingAs(userWithPermissions('admin.companies.force-delete'));

    assertToast($this->delete(route('admin.companies.bulk-force-delete'), ['ids' => [$deleted->id, $live->id]]), 'success');

    expect(Company::withTrashed()->find($deleted->id))->toBeNull()
        ->and($live->refresh()->exists)->toBeTrue();
});

test('bulk delete needs at least one id and no more than a page', function (array $ids) {
    $this->actingAs(userWithPermissions('admin.companies.delete'))
        ->delete(route('admin.companies.bulk-destroy'), ['ids' => $ids])
        ->assertSessionHasErrors('ids');
})->with([
    'none' => [[]],
    'more than a page' => [range(1, 101)],
]);
