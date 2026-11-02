<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\ProjectManagement\Certification;
use App\Models\ProjectManagement\CertificationCategory;

/*
|--------------------------------------------------------------------------
| Projects → Certification Categories
|--------------------------------------------------------------------------
|
| A group-wide list (ARCHITECTURE.md §9.10) in the Supplier Categories shape: code and
| name unique across the table, ignoring case, deleted categories included —
| guarded against deletion while certifications use it (§9.14).
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function certificationCategoryPayload(array $overrides = []): array
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
    $this->get(route('projects.certification-categories.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(userWithPermissions())
        ->get(route('projects.certification-categories.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $category = CertificationCategory::factory()->create();

    if ($trashed) {
        $category->delete();
    }

    $this->actingAs(userWithPermissions('projects.certification-categories.view'))
        ->{$method}(route($route, $category), certificationCategoryPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.certification-categories.update', false],
    'delete' => ['delete', 'projects.certification-categories.destroy', false],
    'restore' => ['put', 'projects.certification-categories.restore', true],
    'force delete' => ['delete', 'projects.certification-categories.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('projects.certification-categories.view'))
        ->post(route('projects.certification-categories.store'), certificationCategoryPayload())
        ->assertForbidden();
});

/*
| Listing
*/

test('the list shows every category with no company selected, and the deleted tab the deleted ones', function () {
    CertificationCategory::factory()->create(['code' => 'LIVE']);
    CertificationCategory::factory()->create(['code' => 'GONE'])->delete();

    $this->actingAs(userWithPermissions('projects.certification-categories.view'));

    $this->get(route('projects.certification-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/certification-categories/index')
            ->has('certificationCategories.data', 1)
            ->where('certificationCategories.data.0.code', 'LIVE')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );

    $this->get(route('projects.certification-categories.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('certificationCategories.data', 1)
            ->where('certificationCategories.data.0.code', 'GONE'),
        );
});

/*
| Validation and writes
*/

test('a category is created with its code in capitals and its name tidied', function () {
    $this->actingAs(userWithPermissions('projects.certification-categories.create'));

    assertToast($this->post(route('projects.certification-categories.store'), certificationCategoryPayload(['code' => ' mat-01 ', 'name' => ' Raw   Materials ', 'description' => ''])), 'success');

    $category = CertificationCategory::sole();

    expect($category->code)->toBe('MAT-01')
        ->and($category->name)->toBe('Raw Materials')
        ->and($category->description)->toBeNull();
});

test('a code or name already used is refused whatever its case, and a deleted one says to restore it', function () {
    CertificationCategory::factory()->create(['code' => 'MAT', 'name' => 'Materials']);
    CertificationCategory::factory()->create(['code' => 'SRV', 'name' => 'Services'])->delete();

    $this->actingAs(userWithPermissions('projects.certification-categories.create'));

    $this->post(route('projects.certification-categories.store'), certificationCategoryPayload(['code' => 'mat', 'name' => 'MATERIALS']))
        ->assertSessionHasErrors(['code', 'name']);

    $this->post(route('projects.certification-categories.store'), certificationCategoryPayload(['code' => 'SRV', 'name' => 'Services']))
        ->assertSessionHasErrors([
            'code' => 'The code "SRV" belongs to a deleted certification category. Restore it from the Deleted tab instead.',
            'name' => 'A deleted certification category is named "Services". Restore it from the Deleted tab instead.',
        ]);
});

test('a category in use can be deactivated', function () {
    $category = CertificationCategory::factory()->create(['code' => 'MAT', 'name' => 'Materials']);
    Certification::factory()->for($category, 'category')->create();

    $this->actingAs(userWithPermissions('projects.certification-categories.update'))
        ->put(route('projects.certification-categories.update', $category), certificationCategoryPayload(['status' => RecordStatus::Inactive->value]))
        ->assertSessionHasNoErrors();

    expect($category->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a category is soft deleted, restored and permanently deleted, the last recorded', function () {
    $category = CertificationCategory::factory()->create();

    $this->actingAs(userWithPermissions('projects.certification-categories.delete', 'projects.certification-categories.restore', 'projects.certification-categories.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.certification-categories.destroy', $category)), 'success');
    expect($category->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.certification-categories.restore', $category)), 'success');
    expect($category->refresh()->trashed())->toBeFalse();

    $category->delete();
    assertToast($this->delete(route('projects.certification-categories.force-delete', $category)), 'success');

    expect(CertificationCategory::withTrashed()->find($category->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'certification-category')->exists())->toBeTrue();
});

/*
| The deletion guard
*/

test('a category used by certifications cannot be deleted, deleted certifications included', function () {
    $category = CertificationCategory::factory()->create();
    Certification::factory()->for($category, 'category')->create();
    Certification::factory()->for($category, 'category')->create()->delete();

    $this->actingAs(userWithPermissions('projects.certification-categories.delete'));

    assertToast($this->delete(route('projects.certification-categories.destroy', $category)), 'warning')
        ->assertSessionHas('inertia.flash_data.toast.message', 'Used by 2 certifications. Move them to another category, or deactivate this one instead.');

    expect($category->refresh()->trashed())->toBeFalse();
});

test('a deleted category still used cannot be permanently deleted', function () {
    $category = CertificationCategory::factory()->create();
    Certification::factory()->for($category, 'category')->create();
    $category->delete();

    $this->actingAs(userWithPermissions('projects.certification-categories.force-delete'));

    assertToast($this->delete(route('projects.certification-categories.force-delete', $category)), 'warning')
        ->assertSessionHas('inertia.flash_data.toast.message', 'Used by one certification. Move it to another category, or deactivate this one instead.');

    expect(CertificationCategory::withTrashed()->find($category->id))->not->toBeNull();
});

test('bulk delete deletes the free categories and names the ones in use', function () {
    $free = CertificationCategory::factory()->create(['code' => 'FREE']);
    $used = CertificationCategory::factory()->create(['code' => 'USED']);
    Certification::factory()->for($used, 'category')->create();

    $this->actingAs(userWithPermissions('projects.certification-categories.delete'));

    assertToast($this->delete(route('projects.certification-categories.bulk-destroy'), ['ids' => [$free->id, $used->id]]), 'warning')
        ->assertSessionHas('inertia.flash_data.toast.message', '1 certification category moved to Deleted. Not deleted — USED: Used by one certification. Move it to another category, or deactivate this one instead.');

    expect($free->refresh()->trashed())->toBeTrue()
        ->and($used->refresh()->trashed())->toBeFalse();
});

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $category = CertificationCategory::factory()->create();

    $this->actingAs(userWithPermissions('projects.certification-categories.view'))
        ->delete(route($route), ['ids' => [$category->id]])
        ->assertForbidden();
})->with(['projects.certification-categories.bulk-destroy', 'projects.certification-categories.bulk-force-delete']);

test('bulk permanent delete removes the ticked deleted categories and records each', function () {
    [$first, $second] = CertificationCategory::factory()->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(userWithPermissions('projects.certification-categories.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.certification-categories.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(CertificationCategory::withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'certification-category')->count())->toBe(2);
});
