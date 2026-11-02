<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\ProjectManagement\TenderType;

/*
|--------------------------------------------------------------------------
| Projects → Tender Types
|--------------------------------------------------------------------------
|
| A group-wide master table (ARCHITECTURE.md §9.10, owner 2026-11-02): one
| list every company shares, so no company is selected, stamped or scoped.
| Code and name are unique across the table — ignoring case, deleted tender
| types included. Nothing references a tender type yet, so no delete is
| refused.
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tenderTypePayload(array $overrides = []): array
{
    return [
        'code' => 'OTM',
        'name' => 'Open Tendering Method',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.tender-types.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(userWithPermissions())
        ->get(route('projects.tender-types.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $tenderType = TenderType::factory()->create();

    if ($trashed) {
        $tenderType->delete();
    }

    $this->actingAs(userWithPermissions('projects.tender-types.view'))
        ->{$method}(route($route, $tenderType), tenderTypePayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.tender-types.update', false],
    'delete' => ['delete', 'projects.tender-types.destroy', false],
    'restore' => ['put', 'projects.tender-types.restore', true],
    'force delete' => ['delete', 'projects.tender-types.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('projects.tender-types.view'))
        ->post(route('projects.tender-types.store'), tenderTypePayload())
        ->assertForbidden();
});

/*
| Group-wide
*/

test('the list works with no company selected and shows every tender type', function () {
    TenderType::factory()->create(['code' => 'OTM']);
    TenderType::factory()->create(['code' => 'RFQ'])->delete();

    $this->actingAs(userWithPermissions('projects.tender-types.view'))
        ->get(route('projects.tender-types.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/tender-types/index')
            ->missing('company')
            ->has('tenderTypes.data', 1)
            ->where('tenderTypes.data.0.code', 'OTM')
            ->where('sortable', TenderType::SORTABLE)
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('the deleted tab lists only deleted tender types', function () {
    TenderType::factory()->create(['code' => 'OTM']);
    TenderType::factory()->create(['code' => 'RFQ'])->delete();

    $this->actingAs(userWithPermissions('projects.tender-types.view'))
        ->get(route('projects.tender-types.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('tenderTypes.data', 1)
            ->where('tenderTypes.data.0.code', 'RFQ'),
        );
});

test('the list filters by code prefix and name, and sorts by status', function () {
    TenderType::factory()->create(['code' => 'LTM-1', 'name' => 'Limited Tendering', 'status' => RecordStatus::Inactive]);
    TenderType::factory()->create(['code' => 'LTM-2', 'name' => 'Limited Tendering (Two Stage)']);
    TenderType::factory()->create(['code' => 'OTM', 'name' => 'Open Tendering']);

    $this->actingAs(userWithPermissions('projects.tender-types.view'))
        ->get(route('projects.tender-types.index', ['filter' => ['code' => 'LTM', 'name' => 'tendering'], 'sort' => 'status', 'direction' => 'asc']))
        ->assertInertia(fn ($page) => $page
            ->has('tenderTypes.data', 2)
            ->where('tenderTypes.data.0.code', 'LTM-2'),
        );
});

test('a status filter outside the enum is an error, not an empty list', function () {
    $this->actingAs(userWithPermissions('projects.tender-types.view'))
        ->get(route('projects.tender-types.index', ['filter' => ['status' => 'X']]))
        ->assertSessionHasErrors('filter.status');
});

test('a tender type is created with no company, its code uppercased and its name tidied', function () {
    $this->actingAs(userWithPermissions('projects.tender-types.create'));

    assertToast($this->post(route('projects.tender-types.store'), tenderTypePayload(['code' => ' otm ', 'name' => ' Open   Tendering Method '])), 'success');

    $tenderType = TenderType::sole();

    expect($tenderType->code)->toBe('OTM')
        ->and($tenderType->name)->toBe('Open Tendering Method');
});

test('code, name and status are required', function () {
    $this->actingAs(userWithPermissions('projects.tender-types.create'))
        ->post(route('projects.tender-types.store'), ['code' => '', 'name' => '', 'status' => ''])
        ->assertSessionHasErrors(['code', 'name', 'status']);
});

test('a code with spaces or symbols is refused', function () {
    $this->actingAs(userWithPermissions('projects.tender-types.create'))
        ->post(route('projects.tender-types.store'), tenderTypePayload(['code' => 'O T/M']))
        ->assertSessionHasErrors('code');
});

/*
| Duplicates
*/

test('a code or name already used is refused whatever its case', function (string $field, string $value) {
    TenderType::factory()->create(['code' => 'OTM', 'name' => 'Open Tendering Method']);

    $this->actingAs(userWithPermissions('projects.tender-types.create'))
        ->post(route('projects.tender-types.store'), tenderTypePayload(['code' => 'OTHER', 'name' => 'Other', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code, lower case' => ['code', 'otm'],
    'name, other case' => ['name', 'OPEN TENDERING METHOD'],
    'name, extra spaces' => ['name', 'Open   Tendering Method '],
]);

test('a deleted tender type keeps its code and name reserved and says to restore it', function () {
    TenderType::factory()->create(['code' => 'OTM', 'name' => 'Open Tendering Method'])->delete();

    $this->actingAs(userWithPermissions('projects.tender-types.create'))
        ->post(route('projects.tender-types.store'), tenderTypePayload())
        ->assertSessionHasErrors([
            'code' => 'The code "OTM" belongs to a deleted tender type. Restore it from the Deleted tab instead.',
            'name' => 'A deleted tender type is named "Open Tendering Method". Restore it from the Deleted tab instead.',
        ]);
});

/*
| Writes
*/

test('a tender type can be edited, keeping its own values', function () {
    $tenderType = TenderType::factory()->create(['code' => 'OTM', 'name' => 'Open Tendering Method']);

    $this->actingAs(userWithPermissions('projects.tender-types.update'));

    assertToast($this->put(route('projects.tender-types.update', $tenderType), tenderTypePayload(['status' => RecordStatus::Inactive->value])), 'success');

    expect($tenderType->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a tender type is soft deleted, restored and permanently deleted', function () {
    $tenderType = TenderType::factory()->create();

    $this->actingAs(userWithPermissions('projects.tender-types.delete', 'projects.tender-types.restore', 'projects.tender-types.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.tender-types.destroy', $tenderType)), 'success');
    expect($tenderType->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.tender-types.restore', $tenderType)), 'success');
    expect($tenderType->refresh()->trashed())->toBeFalse();

    $tenderType->delete();
    assertToast($this->delete(route('projects.tender-types.force-delete', $tenderType)), 'success');

    expect(TenderType::withTrashed()->find($tenderType->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'tender-type')->exists())->toBeTrue();
});

test('creating a tender type is audited', function () {
    $this->actingAs(userWithPermissions('projects.tender-types.create'));
    config(['audit.console' => true]);

    $this->post(route('projects.tender-types.store'), tenderTypePayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'tender-type')->sole();

    expect($audit->new_values['code'])->toBe('OTM');
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $tenderType = TenderType::factory()->create();

    $this->actingAs(userWithPermissions('projects.tender-types.view'))
        ->delete(route($route), ['ids' => [$tenderType->id]])
        ->assertForbidden();
})->with(['projects.tender-types.bulk-destroy', 'projects.tender-types.bulk-force-delete']);

test('bulk delete moves the ticked tender types to Deleted', function () {
    [$first, $second, $untouched] = TenderType::factory()->count(3)->create();

    $this->actingAs(userWithPermissions('projects.tender-types.delete'));

    assertToast($this->delete(route('projects.tender-types.bulk-destroy'), ['ids' => [$first->id, $second->id]]), 'success');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($untouched->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete destroys only deleted tender types', function () {
    $deleted = TenderType::factory()->create();
    $deleted->delete();
    $live = TenderType::factory()->create();

    $this->actingAs(userWithPermissions('projects.tender-types.force-delete'));

    assertToast($this->delete(route('projects.tender-types.bulk-force-delete'), ['ids' => [$deleted->id, $live->id]]), 'success');

    expect(TenderType::withTrashed()->find($deleted->id))->toBeNull()
        ->and($live->refresh()->exists)->toBeTrue();
});
