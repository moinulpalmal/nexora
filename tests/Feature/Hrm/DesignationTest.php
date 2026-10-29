<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Hrm\Designation;

/*
|--------------------------------------------------------------------------
| HRM → Designations
|--------------------------------------------------------------------------
|
| A group-wide master table (ARCHITECTURE.md §9.10, owner 2026-10-29): one
| list every company shares, so no company is selected, stamped or scoped.
| Code and name are unique across the table — ignoring case, deleted
| designations included.
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function designationPayload(array $overrides = []): array
{
    return [
        'code' => 'SR-OP',
        'name' => 'Senior Operator',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('hrm.designations.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(userWithPermissions())
        ->get(route('hrm.designations.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $designation = Designation::factory()->create();

    if ($trashed) {
        $designation->delete();
    }

    $this->actingAs(userWithPermissions('hrm.designations.view'))
        ->{$method}(route($route, $designation), designationPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'hrm.designations.update', false],
    'delete' => ['delete', 'hrm.designations.destroy', false],
    'restore' => ['put', 'hrm.designations.restore', true],
    'force delete' => ['delete', 'hrm.designations.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('hrm.designations.view'))
        ->post(route('hrm.designations.store'), designationPayload())
        ->assertForbidden();
});

/*
| Group-wide
*/

test('the list works with no company selected and shows every designation', function () {
    Designation::factory()->create(['code' => 'OP']);
    Designation::factory()->create(['code' => 'SUP'])->delete();

    $this->actingAs(userWithPermissions('hrm.designations.view'))
        ->get(route('hrm.designations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hrm/designations/index')
            ->missing('company')
            ->has('designations.data', 1)
            ->where('designations.data.0.code', 'OP')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('the list filters by code prefix and sorts by status', function () {
    Designation::factory()->create(['code' => 'OP-1', 'status' => RecordStatus::Inactive]);
    Designation::factory()->create(['code' => 'OP-2']);
    Designation::factory()->create(['code' => 'SUP']);

    $this->actingAs(userWithPermissions('hrm.designations.view'))
        ->get(route('hrm.designations.index', ['filter' => ['code' => 'OP'], 'sort' => 'status', 'direction' => 'asc']))
        ->assertInertia(fn ($page) => $page
            ->has('designations.data', 2)
            ->where('designations.data.0.code', 'OP-2'),
        );
});

test('a designation is created with no company, its code uppercased and its name tidied', function () {
    $this->actingAs(userWithPermissions('hrm.designations.create'));

    assertToast($this->post(route('hrm.designations.store'), designationPayload(['code' => ' sr-op ', 'name' => ' Senior   Operator '])), 'success');

    $designation = Designation::sole();

    expect($designation->code)->toBe('SR-OP')
        ->and($designation->name)->toBe('Senior Operator');
});

/*
| Duplicates
*/

test('a code or name already used is refused whatever its case', function (string $field, string $value) {
    Designation::factory()->create(['code' => 'SR-OP', 'name' => 'Senior Operator']);

    $this->actingAs(userWithPermissions('hrm.designations.create'))
        ->post(route('hrm.designations.store'), designationPayload(['code' => 'OTHER', 'name' => 'Other', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code, lower case' => ['code', 'sr-op'],
    'name, other case' => ['name', 'SENIOR OPERATOR'],
    'name, extra spaces' => ['name', 'Senior   Operator '],
]);

test('a deleted designation keeps its code and name reserved and says to restore it', function () {
    Designation::factory()->create(['code' => 'SR-OP', 'name' => 'Senior Operator'])->delete();

    $this->actingAs(userWithPermissions('hrm.designations.create'))
        ->post(route('hrm.designations.store'), designationPayload())
        ->assertSessionHasErrors([
            'code' => 'The code "SR-OP" belongs to a deleted designation. Restore it from the Deleted tab instead.',
            'name' => 'A deleted designation is named "Senior Operator". Restore it from the Deleted tab instead.',
        ]);
});

/*
| Writes
*/

test('a designation can be edited, keeping its own values', function () {
    $designation = Designation::factory()->create(['code' => 'SR-OP', 'name' => 'Senior Operator']);

    $this->actingAs(userWithPermissions('hrm.designations.update'))
        ->put(route('hrm.designations.update', $designation), designationPayload(['status' => RecordStatus::Inactive->value]))
        ->assertSessionHasNoErrors();

    expect($designation->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a designation is soft deleted, restored and permanently deleted', function () {
    $designation = Designation::factory()->create();

    $this->actingAs(userWithPermissions('hrm.designations.delete', 'hrm.designations.restore', 'hrm.designations.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('hrm.designations.destroy', $designation)), 'success');
    expect($designation->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('hrm.designations.restore', $designation)), 'success');
    expect($designation->refresh()->trashed())->toBeFalse();

    $designation->delete();
    assertToast($this->delete(route('hrm.designations.force-delete', $designation)), 'success');

    expect(Designation::withTrashed()->find($designation->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'designation')->exists())->toBeTrue();
});

test('creating a designation is audited', function () {
    $this->actingAs(userWithPermissions('hrm.designations.create'));
    config(['audit.console' => true]);

    $this->post(route('hrm.designations.store'), designationPayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'designation')->sole();

    expect($audit->new_values['code'])->toBe('SR-OP');
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $designation = Designation::factory()->create();

    $this->actingAs(userWithPermissions('hrm.designations.view'))
        ->delete(route($route), ['ids' => [$designation->id]])
        ->assertForbidden();
})->with(['hrm.designations.bulk-destroy', 'hrm.designations.bulk-force-delete']);

test('bulk delete moves the ticked designations to Deleted', function () {
    [$first, $second, $untouched] = Designation::factory()->count(3)->create();

    $this->actingAs(userWithPermissions('hrm.designations.delete'));

    $response = $this->delete(route('hrm.designations.bulk-destroy'), ['ids' => [$first->id, $second->id]]);

    assertToast($response, 'success')->assertSessionHas('inertia.flash_data.toast.message', '2 designations moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($untouched->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes the ticked deleted designations and records each', function () {
    [$first, $second] = Designation::factory()->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(userWithPermissions('hrm.designations.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('hrm.designations.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(Designation::withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'designation')->count())->toBe(2);
});
