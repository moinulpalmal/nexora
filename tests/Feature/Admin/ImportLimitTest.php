<?php

use App\Models\Admin\AuditLog;
use App\Models\Admin\Role;
use App\Models\ImportLimit;
use App\Models\SheetImport;
use Database\Seeders\Admin\RolePermissionSeeder;
use Tests\Fixtures\Imports\ImportFixtures;

/*
|--------------------------------------------------------------------------
| Application Settings → Import Limits
|--------------------------------------------------------------------------
|
| The caps the spreadsheet-import engine reads every upload under
| (ARCHITECTURE.md §9.13). One unseeded row: the config defaults apply until
| the first save.
|
*/

/** @return array{max_rows: int, max_columns: int, max_sheets: int} */
function importLimitsPayload(array $overrides = []): array
{
    return ['max_rows' => 500, 'max_columns' => 30, 'max_sheets' => 4, ...$overrides];
}

test('the hub opens on the import limits tab alone, showing the config defaults', function () {
    config(['sheet-import.limits.max_rows' => 1234, 'sheet-import.limits.max_file_size_kb' => 2048]);

    $this->actingAs(userWithPermissions('admin.import-limits.view'))
        ->get(route('admin.application-settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/application-settings')
            ->where('sections.mail', null)
            ->where('sections.import-limits.limits.max_rows', 1234)
            ->where('sections.import-limits.maxFileSizeKb', 2048)
            ->where('sections.import-limits.updatedBy', null),
        );
});

test('the tab arrives as null for someone who cannot view it', function () {
    $this->actingAs(userWithPermissions('admin.branding.view'))
        ->get(route('admin.application-settings.edit'))
        ->assertInertia(fn ($page) => $page->where('sections.import-limits', null));
});

test('saving is denied without the update permission', function () {
    $this->actingAs(userWithPermissions('admin.import-limits.view'))
        ->put(route('admin.import-limits.update'), importLimitsPayload())
        ->assertForbidden();

    expect(ImportLimit::query()->count())->toBe(0);
});

test('saving creates the one row, audits it and says who changed it', function () {
    $user = userWithPermissions('admin.import-limits.view', 'admin.import-limits.update');
    $this->actingAs($user);
    config(['audit.console' => true]);

    assertToast($this->put(route('admin.import-limits.update'), importLimitsPayload()), 'success');
    assertToast($this->put(route('admin.import-limits.update'), importLimitsPayload(['max_sheets' => 6])), 'success');

    expect(ImportLimit::query()->sole()->only(['id', 'max_rows', 'max_columns', 'max_sheets']))
        ->toBe(['id' => ImportLimit::SINGLETON_ID, 'max_rows' => 500, 'max_columns' => 30, 'max_sheets' => 6])
        ->and(AuditLog::query()->where('auditable_type', 'import-limit')->pluck('event')->all())->toBe(['created', 'updated']);

    $this->get(route('admin.application-settings.edit'))
        ->assertInertia(fn ($page) => $page->where('sections.import-limits.updatedBy', $user->name));
});

test('a cap must be a positive number within its ceiling', function (array $payload, string $field) {
    $this->actingAs(userWithPermissions('admin.import-limits.update'))
        ->put(route('admin.import-limits.update'), importLimitsPayload($payload))
        ->assertSessionHasErrors($field);
})->with([
    'no rows' => [['max_rows' => 0], 'max_rows'],
    'too many rows' => [['max_rows' => 100001], 'max_rows'],
    'too many columns' => [['max_columns' => 1001], 'max_columns'],
    'no sheets' => [['max_sheets' => 0], 'max_sheets'],
    'not a number' => [['max_sheets' => 'many'], 'max_sheets'],
]);

test('a saved cap is the one an upload is read under', function () {
    ImportFixtures::install();
    $this->actingAs(userWithPermissions('admin.import-limits.update', 'tests.widgets.import'));

    $this->put(route('admin.import-limits.update'), importLimitsPayload(['max_rows' => 1]));

    $response = $this->post(route('tests.widgets.import.store'), [
        'file' => uploadedWorkbook(['Widgets' => [['Code', 'Name'], ['w-001', 'First'], ['w-002', 'Second']]]),
    ]);

    assertToast($response, 'error');
    expect(session('inertia.flash_data.toast.message'))->toContain('past 1 rows')
        ->and(SheetImport::query()->count())->toBe(0);
});

test('the import limits are kept to the super admin by rule', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::findByName('admin')->hasPermissionTo('admin.import-limits.update'))->toBeFalse()
        ->and(Role::findByName('viewer')->hasPermissionTo('admin.import-limits.view'))->toBeFalse();
});
