<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\Supplier;
use App\Models\ProjectManagement\SupplierCategory;

/*
|--------------------------------------------------------------------------
| Projects → Supplier Categories
|--------------------------------------------------------------------------
|
| A group-wide list (ARCHITECTURE.md §9.10) in the Tender Types shape: code and
| name unique across the table, ignoring case, deleted categories included —
| and, unlike tender types, guarded against deletion while suppliers in any
| company use it (§9.14).
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function supplierCategoryPayload(array $overrides = []): array
{
    return [
        'code' => 'MAT',
        'name' => 'Materials',
        'description' => 'Goods bought for projects.',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.supplier-categories.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(userWithPermissions())
        ->get(route('projects.supplier-categories.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $category = SupplierCategory::factory()->create();

    if ($trashed) {
        $category->delete();
    }

    $this->actingAs(userWithPermissions('projects.supplier-categories.view'))
        ->{$method}(route($route, $category), supplierCategoryPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.supplier-categories.update', false],
    'delete' => ['delete', 'projects.supplier-categories.destroy', false],
    'restore' => ['put', 'projects.supplier-categories.restore', true],
    'force delete' => ['delete', 'projects.supplier-categories.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('projects.supplier-categories.view'))
        ->post(route('projects.supplier-categories.store'), supplierCategoryPayload())
        ->assertForbidden();
});

/*
| Listing
*/

test('the list shows every category with no company selected, and the deleted tab the deleted ones', function () {
    SupplierCategory::factory()->create(['code' => 'LIVE']);
    SupplierCategory::factory()->create(['code' => 'GONE'])->delete();

    $this->actingAs(userWithPermissions('projects.supplier-categories.view'));

    $this->get(route('projects.supplier-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/supplier-categories/index')
            ->has('supplierCategories.data', 1)
            ->where('supplierCategories.data.0.code', 'LIVE')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );

    $this->get(route('projects.supplier-categories.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('supplierCategories.data', 1)
            ->where('supplierCategories.data.0.code', 'GONE'),
        );
});

/*
| Validation and writes
*/

test('a category is created with its code in capitals and its name tidied', function () {
    $this->actingAs(userWithPermissions('projects.supplier-categories.create'));

    assertToast($this->post(route('projects.supplier-categories.store'), supplierCategoryPayload(['code' => ' mat-01 ', 'name' => ' Raw   Materials ', 'description' => ''])), 'success');

    $category = SupplierCategory::sole();

    expect($category->code)->toBe('MAT-01')
        ->and($category->name)->toBe('Raw Materials')
        ->and($category->description)->toBeNull();
});

test('a code or name already used is refused whatever its case, and a deleted one says to restore it', function () {
    SupplierCategory::factory()->create(['code' => 'MAT', 'name' => 'Materials']);
    SupplierCategory::factory()->create(['code' => 'SRV', 'name' => 'Services'])->delete();

    $this->actingAs(userWithPermissions('projects.supplier-categories.create'));

    $this->post(route('projects.supplier-categories.store'), supplierCategoryPayload(['code' => 'mat', 'name' => 'MATERIALS']))
        ->assertSessionHasErrors(['code', 'name']);

    $this->post(route('projects.supplier-categories.store'), supplierCategoryPayload(['code' => 'SRV', 'name' => 'Services']))
        ->assertSessionHasErrors([
            'code' => 'The code "SRV" belongs to a deleted supplier category. Restore it from the Deleted tab instead.',
            'name' => 'A deleted supplier category is named "Services". Restore it from the Deleted tab instead.',
        ]);
});

test('a category in use can be deactivated', function () {
    $category = SupplierCategory::factory()->create(['code' => 'MAT', 'name' => 'Materials']);
    Supplier::factory()->for($category, 'category')->create();

    $this->actingAs(userWithPermissions('projects.supplier-categories.update'))
        ->put(route('projects.supplier-categories.update', $category), supplierCategoryPayload(['status' => RecordStatus::Inactive->value]))
        ->assertSessionHasNoErrors();

    expect($category->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a category is soft deleted, restored and permanently deleted, the last recorded', function () {
    $category = SupplierCategory::factory()->create();

    $this->actingAs(userWithPermissions('projects.supplier-categories.delete', 'projects.supplier-categories.restore', 'projects.supplier-categories.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.supplier-categories.destroy', $category)), 'success');
    expect($category->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.supplier-categories.restore', $category)), 'success');
    expect($category->refresh()->trashed())->toBeFalse();

    $category->delete();
    assertToast($this->delete(route('projects.supplier-categories.force-delete', $category)), 'success');

    expect(SupplierCategory::withTrashed()->find($category->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'supplier-category')->exists())->toBeTrue();
});

/*
| The deletion guard
*/

test('a category used by suppliers in any company cannot be deleted, deleted suppliers included', function () {
    $category = SupplierCategory::factory()->create();
    Supplier::factory()->for($category, 'category')->for(Company::factory())->create();
    Supplier::factory()->for($category, 'category')->for(Company::factory())->create()->delete();

    $this->actingAs(userWithPermissions('projects.supplier-categories.delete'));

    assertToast($this->delete(route('projects.supplier-categories.destroy', $category)), 'warning')
        ->assertSessionHas('inertia.flash_data.toast.message', 'Used by 2 suppliers. Move them to another category, or deactivate this one instead.');

    expect($category->refresh()->trashed())->toBeFalse();
});

test('a deleted category still used cannot be permanently deleted', function () {
    $category = SupplierCategory::factory()->create();
    Supplier::factory()->for($category, 'category')->create();
    $category->delete();

    $this->actingAs(userWithPermissions('projects.supplier-categories.force-delete'));

    assertToast($this->delete(route('projects.supplier-categories.force-delete', $category)), 'warning')
        ->assertSessionHas('inertia.flash_data.toast.message', 'Used by one supplier. Move it to another category, or deactivate this one instead.');

    expect(SupplierCategory::withTrashed()->find($category->id))->not->toBeNull();
});

test('bulk delete deletes the free categories and names the ones in use', function () {
    $free = SupplierCategory::factory()->create(['code' => 'FREE']);
    $used = SupplierCategory::factory()->create(['code' => 'USED']);
    Supplier::factory()->for($used, 'category')->create();

    $this->actingAs(userWithPermissions('projects.supplier-categories.delete'));

    assertToast($this->delete(route('projects.supplier-categories.bulk-destroy'), ['ids' => [$free->id, $used->id]]), 'warning')
        ->assertSessionHas('inertia.flash_data.toast.message', '1 supplier category moved to Deleted. Not deleted — USED: Used by one supplier. Move it to another category, or deactivate this one instead.');

    expect($free->refresh()->trashed())->toBeTrue()
        ->and($used->refresh()->trashed())->toBeFalse();
});

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $category = SupplierCategory::factory()->create();

    $this->actingAs(userWithPermissions('projects.supplier-categories.view'))
        ->delete(route($route), ['ids' => [$category->id]])
        ->assertForbidden();
})->with(['projects.supplier-categories.bulk-destroy', 'projects.supplier-categories.bulk-force-delete']);

test('bulk permanent delete removes the ticked deleted categories and records each', function () {
    [$first, $second] = SupplierCategory::factory()->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(userWithPermissions('projects.supplier-categories.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.supplier-categories.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(SupplierCategory::withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'supplier-category')->count())->toBe(2);
});
