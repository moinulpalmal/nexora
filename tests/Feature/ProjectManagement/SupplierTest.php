<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\Supplier;
use App\Models\ProjectManagement\SupplierCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Projects → Suppliers
|--------------------------------------------------------------------------
|
| The ERP's one supplier list, company-scoped as Projects → Clients
| (ARCHITECTURE.md §9.10), each in a group-wide supplier category that must be
| active to be chosen. The rules shared with clients are proved there; this
| file proves the scoping once more and what suppliers add.
|
*/

/**
 * A user holding the permissions *and* working in the company.
 */
function supplierActor(Company $company, string ...$permissions): User
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
function supplierPayload(array $overrides = []): array
{
    return [
        'supplier_category_id' => $overrides['supplier_category_id'] ?? SupplierCategory::factory()->create()->id,
        'code' => 'BUILDMART',
        'name' => 'Buildmart Supplies Ltd.',
        'address' => '7 Example Lane, Chattogram',
        'description' => null,
        'contact_person' => 'Rafiq Ahmed',
        'phone' => '+880 1811 223344',
        'email' => 'sales@buildmart.example.com',
        'tin' => '123456789012',
        'bin' => '000123456-0101',
        'trade_license_no' => 'TRAD/DNCC/012345/2026',
        'bank_name' => 'Example Bank PLC',
        'bank_branch' => 'Motijheel',
        'bank_account_name' => 'Buildmart Supplies Ltd.',
        'bank_account_number' => '1234-5678-9012',
        'bank_routing_number' => '123456789',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access and scoping
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.suppliers.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(supplierActor(Company::factory()->create()))
        ->get(route('projects.suppliers.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();

    if ($trashed) {
        $supplier->delete();
    }

    $this->actingAs(supplierActor($company, 'projects.suppliers.view'))
        ->{$method}(route($route, $supplier), supplierPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.suppliers.update', false],
    'delete' => ['delete', 'projects.suppliers.destroy', false],
    'restore' => ['put', 'projects.suppliers.restore', true],
    'force delete' => ['delete', 'projects.suppliers.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(supplierActor(Company::factory()->create(), 'projects.suppliers.view'))
        ->post(route('projects.suppliers.store'), supplierPayload())
        ->assertForbidden();
});

test('the list shows only the selected company\'s suppliers, with every category offered', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $category = SupplierCategory::factory()->create(['name' => 'Materials']);
    SupplierCategory::factory()->inactive()->create(['name' => 'Retired']);
    Supplier::factory()->for($mine)->for($category, 'category')->create(['code' => 'MINE']);
    Supplier::factory()->for($theirs)->create(['code' => 'THEIRS']);

    $this->actingAs(supplierActor($mine, 'projects.suppliers.view'))
        ->get(route('projects.suppliers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/suppliers/index')
            ->where('company.id', $mine->id)
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.code', 'MINE')
            ->where('suppliers.data.0.category_name', 'Materials')
            ->has('categories', 3)
            ->where('counts', ['active' => 1, 'trashed' => 0]),
        );
});

test('the list filters on the category and the tax numbers', function (string $column, ?string $prefix) {
    $company = Company::factory()->create();
    $hitCategory = SupplierCategory::factory()->create();
    Supplier::factory()->for($company)->for($hitCategory, 'category')->create(['code' => 'HIT', 'tin' => '111122223333', 'bin' => '000111222-0101']);
    Supplier::factory()->for($company)->create(['code' => 'MISS', 'tin' => '999988887777', 'bin' => '000999888-0101']);

    $this->actingAs(supplierActor($company, 'projects.suppliers.view'))
        ->get(route('projects.suppliers.index', ['filter' => [$column => $prefix ?? $hitCategory->id]]))
        ->assertInertia(fn ($page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.code', 'HIT'),
        );
})->with([
    'category' => ['supplier_category_id', null],
    'TIN, from its start' => ['tin', '1111'],
    'BIN, from its start' => ['bin', '000111'],
]);

test('another company\'s supplier is a 404', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = Supplier::factory()->for($theirs)->create();

    $this->actingAs(supplierActor($mine, 'projects.suppliers.update'))
        ->put(route('projects.suppliers.update', $foreign), supplierPayload())
        ->assertNotFound();
});

test('with no company selected the page says so and lists nothing', function () {
    Supplier::factory()->create();
    $user = userWithPermissions('projects.suppliers.view');
    $user->companies()->attach(Company::factory()->count(2)->create());

    $this->actingAs($user)
        ->get(route('projects.suppliers.index'))
        ->assertInertia(fn ($page) => $page
            ->where('company', null)
            ->has('suppliers.data', 0)
            ->has('categories', 0),
        );
});

test('creating without a selected company is refused', function () {
    assertToast($this->actingAs(userWithPermissions('projects.suppliers.create'))->post(route('projects.suppliers.store'), supplierPayload()), 'warning');

    expect(Supplier::withoutGlobalScopes()->count())->toBe(0);
});

/*
| Validation and writes
*/

test('a supplier lands in the selected company with its values tidied and empty fields saved as null', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();

    $this->actingAs(supplierActor($mine, 'projects.suppliers.create'));

    assertToast($this->post(route('projects.suppliers.store'), supplierPayload([
        'company_id' => $theirs->id,
        'code' => ' bm-01 ',
        'name' => ' Buildmart   Supplies ',
        'email' => ' Sales@BUILDMART.example.com ',
        'tin' => ' 123456789012 ',
        'bank_account_number' => ' gb29 nwbk 6016 ',
        'trade_license_no' => '',
        'bank_routing_number' => '',
    ])), 'success');

    $supplier = Supplier::withoutGlobalScopes()->sole();

    expect($supplier->company_id)->toBe($mine->id)
        ->and($supplier->code)->toBe('BM-01')
        ->and($supplier->name)->toBe('Buildmart Supplies')
        ->and($supplier->email)->toBe('sales@buildmart.example.com')
        ->and($supplier->tin)->toBe('123456789012')
        ->and($supplier->bank_account_number)->toBe('GB29 NWBK 6016')
        ->and($supplier->trade_license_no)->toBeNull()
        ->and($supplier->bank_routing_number)->toBeNull();
});

test('a malformed field is refused', function (string $field, string $value) {
    $this->actingAs(supplierActor(Company::factory()->create(), 'projects.suppliers.create'))
        ->post(route('projects.suppliers.store'), supplierPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'category missing' => ['supplier_category_id', ''],
    'code with a space' => ['code', 'BUILD MART'],
    'TIN too short' => ['tin', '12345'],
    'TIN with a letter' => ['tin', '12345678901A'],
    'BIN with a letter' => ['bin', '000123456-01A1'],
    'routing number with a letter' => ['bank_routing_number', '12345678X'],
    'account number with a symbol' => ['bank_account_number', '1234#5678'],
]);

test('an inactive or deleted category cannot be chosen', function (SupplierCategory $category) {
    $this->actingAs(supplierActor(Company::factory()->create(), 'projects.suppliers.create'))
        ->post(route('projects.suppliers.store'), supplierPayload(['supplier_category_id' => $category->id]))
        ->assertSessionHasErrors(['supplier_category_id' => 'Choose an active supplier category.']);
})->with([
    'inactive' => fn () => SupplierCategory::factory()->inactive()->create(),
    'deleted' => fn () => tap(SupplierCategory::factory()->create())->delete(),
]);

test('a supplier keeps its category on edit after the category is deactivated', function () {
    $company = Company::factory()->create();
    $category = SupplierCategory::factory()->create();
    $supplier = Supplier::factory()->for($company)->for($category, 'category')->create(['code' => 'BUILDMART', 'name' => 'Buildmart Supplies Ltd.']);
    $category->update(['status' => RecordStatus::Inactive]);

    $this->actingAs(supplierActor($company, 'projects.suppliers.update'))
        ->put(route('projects.suppliers.update', $supplier), supplierPayload(['supplier_category_id' => $category->id, 'phone' => '+880 1911 000000']))
        ->assertSessionHasNoErrors();

    expect($supplier->refresh()->phone)->toBe('+880 1911 000000');
});

test('a code or name is unique within the company only, deleted suppliers included, and tax numbers may repeat', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    Supplier::factory()->for($theirs)->create(['code' => 'BUILDMART', 'name' => 'Buildmart Supplies Ltd.']);
    Supplier::factory()->for($mine)->create(['code' => 'SISTER', 'tin' => '123456789012', 'bin' => '000123456-0101']);
    Supplier::factory()->for($mine)->create(['code' => 'GONE', 'name' => 'Gone Ltd.'])->delete();

    $this->actingAs(supplierActor($mine, 'projects.suppliers.create'));

    $this->post(route('projects.suppliers.store'), supplierPayload())->assertSessionHasNoErrors();

    $this->post(route('projects.suppliers.store'), supplierPayload(['code' => 'gone', 'name' => 'Gone Ltd.']))
        ->assertSessionHasErrors([
            'code' => 'The code "GONE" belongs to a deleted supplier in this company. Restore it from the Deleted tab instead.',
            'name' => 'A deleted supplier in this company is named "Gone Ltd.". Restore it from the Deleted tab instead.',
        ]);
});

test('a supplier is soft deleted, restored and permanently deleted, every change audited', function () {
    $company = Company::factory()->create();

    $this->actingAs(supplierActor($company, 'projects.suppliers.create', 'projects.suppliers.delete', 'projects.suppliers.restore', 'projects.suppliers.force-delete'));
    $payload = supplierPayload();
    config(['audit.console' => true]);

    $this->post(route('projects.suppliers.store'), $payload);
    $supplier = Supplier::withoutGlobalScopes()->sole();

    expect((int) AuditLog::where('event', 'created')->where('auditable_type', 'supplier')->sole()->new_values['company_id'])->toBe($company->id);

    assertToast($this->delete(route('projects.suppliers.destroy', $supplier)), 'success');
    expect($supplier->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.suppliers.restore', $supplier)), 'success');
    expect($supplier->refresh()->trashed())->toBeFalse();

    $supplier->delete();
    assertToast($this->delete(route('projects.suppliers.force-delete', $supplier)), 'success');

    expect(Supplier::withoutGlobalScopes()->withTrashed()->find($supplier->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'supplier')->exists())->toBeTrue();
});

/*
| Bulk delete
*/

test('bulk delete moves the ticked suppliers to Deleted, never another company\'s', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    [$first, $second] = Supplier::factory()->for($mine)->count(2)->create();
    $foreign = Supplier::factory()->for($theirs)->create();

    $this->actingAs(supplierActor($mine, 'projects.suppliers.delete'));

    assertToast($this->delete(route('projects.suppliers.bulk-destroy'), ['ids' => [$first->id, $second->id, $foreign->id]]), 'success')
        ->assertSessionHas('inertia.flash_data.toast.message', '2 suppliers moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($foreign->refresh()->trashed())->toBeFalse();
});

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();

    $this->actingAs(supplierActor($company, 'projects.suppliers.view'))
        ->delete(route($route), ['ids' => [$supplier->id]])
        ->assertForbidden();
})->with(['projects.suppliers.bulk-destroy', 'projects.suppliers.bulk-force-delete']);

test('bulk permanent delete removes the ticked deleted suppliers', function () {
    $company = Company::factory()->create();
    [$first, $second] = Supplier::factory()->for($company)->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(supplierActor($company, 'projects.suppliers.force-delete'));

    assertToast($this->delete(route('projects.suppliers.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(Supplier::withoutGlobalScopes()->withTrashed()->count())->toBe(0);
});
