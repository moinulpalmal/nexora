<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Hrm\Designation;
use App\Models\SheetImport;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| HRM → Designations → Import
|--------------------------------------------------------------------------
|
| A group-wide row writer (ARCHITECTURE.md §9.10, §9.13): no company is
| needed or stored, and a row is an existing designation when its code OR its
| name matches. The engine is proved in tests/Feature/Imports/.
|
*/

function designationImporter(): User
{
    return userWithPermissions('hrm.designations.view', 'hrm.designations.import');
}

/**
 * Upload a one-tab workbook as `$uploader` and return the staged import.
 *
 * @param  list<list<string|null>>  $rows  data rows under the Code / Name / Status header
 */
function stageDesignations(User $uploader, array $rows): SheetImport
{
    test()->actingAs($uploader)->post(route('hrm.designations.import.store'), [
        'file' => uploadedWorkbook(['Designations' => [['Code', 'Name', 'Status'], ...$rows]], 'designations.xlsx'),
    ]);

    return SheetImport::query()->pending()->where('inserted_by', $uploader->id)->latest('id')->firstOrFail();
}

/** @return array{map: array<int, array<string, int>>, on_duplicate: string} */
function designationMapping(string $onDuplicate = 'update'): array
{
    return ['map' => [0 => ['code' => 0, 'name' => 1, 'status' => 2]], 'on_duplicate' => $onDuplicate];
}

test('every import route is refused without the import permission', function (string $method, string $route) {
    $sheetImport = stageDesignations(designationImporter(), [['OP', 'Operator', null]]);

    $this->actingAs(userWithPermissions('hrm.designations.view', 'hrm.designations.create'))
        ->{$method}(route($route, $sheetImport), designationMapping())
        ->assertForbidden();
})->with([
    'template' => ['get', 'hrm.designations.import.template'],
    'upload' => ['post', 'hrm.designations.import.store'],
    'review' => ['post', 'hrm.designations.import.review'],
    'confirm' => ['post', 'hrm.designations.import.confirm'],
    'cancel' => ['delete', 'hrm.designations.import.cancel'],
]);

test('a workbook is staged with no company selected and no company stored', function () {
    $sheetImport = stageDesignations(designationImporter(), [['OP', 'Operator', 'Active']]);

    expect($sheetImport->company_id)->toBeNull()
        ->and($sheetImport->payload['sheets'][0]['suggested_map'])->toMatchArray(['code' => 0, 'name' => 1, 'status' => 2]);
});

test('confirming creates designations, normalised like the form, and audits each', function () {
    $sheetImport = stageDesignations(designationImporter(), [
        [' op ', ' Machine   Operator ', null],
        ['sup-1', 'Supervisor', 'inactive'],
    ]);

    config(['audit.console' => true]);

    assertToast($this->post(route('hrm.designations.import.confirm', $sheetImport), designationMapping()), 'success');

    $designations = Designation::orderBy('code')->get();

    expect($designations->pluck('code')->all())->toBe(['OP', 'SUP-1'])
        ->and($designations->pluck('name')->all())->toBe(['Machine Operator', 'Supervisor'])
        ->and($designations->pluck('status')->all())->toBe([RecordStatus::Active, RecordStatus::Inactive])
        ->and(AuditLog::where('event', 'created')->where('auditable_type', 'designation')->count())->toBe(2);
});

test('a row matching an existing designation by code or by name updates it', function () {
    $byCode = Designation::factory()->create(['code' => 'OP', 'name' => 'Operator']);
    $byName = Designation::factory()->create(['code' => 'OLD', 'name' => 'Supervisor']);

    $sheetImport = stageDesignations(designationImporter(), [
        ['OP', 'Machine Operator', null],
        ['SUP', 'supervisor', null],
    ]);

    $this->post(route('hrm.designations.import.confirm', $sheetImport), designationMapping('update'));

    expect($byCode->refresh()->name)->toBe('Machine Operator')
        ->and($byName->refresh()->code)->toBe('SUP')
        ->and(Designation::count())->toBe(2);
});

test('a matching row is skipped when the uploader chose skip', function () {
    $existing = Designation::factory()->create(['code' => 'OP', 'name' => 'Operator']);

    $sheetImport = stageDesignations(designationImporter(), [['OP', 'Renamed', null]]);

    $this->postJson(route('hrm.designations.import.review', $sheetImport), designationMapping('skip'))
        ->assertJson(['created' => 0, 'updated' => 0, 'skipped' => 1]);

    expect($existing->refresh()->name)->toBe('Operator');
});

test('conflicting keys, a deleted match, repeats in the file and a bad status are rejected', function () {
    Designation::factory()->create(['code' => 'OP', 'name' => 'Operator']);
    Designation::factory()->create(['code' => 'SUP', 'name' => 'Supervisor']);
    Designation::factory()->create(['code' => 'MGR', 'name' => 'Manager'])->delete();

    $sheetImport = stageDesignations(designationImporter(), [
        ['OP', 'Supervisor', null],
        ['MGR', 'Manager', null],
        ['QA', 'Quality Inspector', null],
        ['QA2', 'quality inspector', null],
        ['HLP', 'Helper', 'retired'],
    ]);

    $this->postJson(route('hrm.designations.import.review', $sheetImport), designationMapping())
        ->assertOk()
        ->assertJson(['created' => 1, 'rejected' => 4]);
});

test('the list offers the import only to an importer', function () {
    $this->actingAs(designationImporter())
        ->get(route('hrm.designations.index'))
        ->assertInertia(fn ($page) => $page->has('importFields', 3)->has('duplicateActions'));

    $this->actingAs(userWithPermissions('hrm.designations.view'))
        ->get(route('hrm.designations.index'))
        ->assertInertia(fn ($page) => $page->where('importFields', []));
});
