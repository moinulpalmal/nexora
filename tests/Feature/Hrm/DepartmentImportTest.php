<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\SheetImport;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| HRM → Departments → Import
|--------------------------------------------------------------------------
|
| The first shipped importer (ARCHITECTURE.md §9.13): company-scoped, and a
| row writer whose rule is the owner's — a row is an existing department when
| its code OR its name matches. The engine itself is proved in
| tests/Feature/Imports/; this covers what is specific to departments.
|
*/

/**
 * A user who may import and works in the company.
 */
function departmentImporter(Company $company): User
{
    $user = userWithPermissions('hrm.departments.view', 'hrm.departments.import');
    $user->companies()->attach($company);
    $user->forceFill(['selected_company_id' => $company->id])->saveQuietly();

    return $user;
}

/**
 * Upload a one-tab workbook as `$uploader` and return the staged import.
 *
 * @param  list<list<string|null>>  $rows  data rows under the Code / Name / Status header
 */
function stageDepartments(User $uploader, array $rows): SheetImport
{
    test()->actingAs($uploader)->post(route('hrm.departments.import.store'), [
        'file' => uploadedWorkbook(['Departments' => [['Code', 'Name', 'Status'], ...$rows]], 'departments.xlsx'),
    ]);

    return SheetImport::query()->pending()->where('inserted_by', $uploader->id)->latest('id')->firstOrFail();
}

/** @return array{map: array<int, array<string, int>>, on_duplicate: string} */
function departmentMapping(string $onDuplicate = 'update'): array
{
    return ['map' => [0 => ['code' => 0, 'name' => 1, 'status' => 2]], 'on_duplicate' => $onDuplicate];
}

test('every import route is refused without the import permission', function (string $method, string $route) {
    $company = Company::factory()->create();
    $sheetImport = stageDepartments(departmentImporter($company), [['HR', 'Human Resources', null]]);

    $user = userWithPermissions('hrm.departments.view', 'hrm.departments.create');
    $user->companies()->attach($company);

    $this->actingAs($user)
        ->{$method}(route($route, $sheetImport), departmentMapping())
        ->assertForbidden();
})->with([
    'template' => ['get', 'hrm.departments.import.template'],
    'upload' => ['post', 'hrm.departments.import.store'],
    'review' => ['post', 'hrm.departments.import.review'],
    'confirm' => ['post', 'hrm.departments.import.confirm'],
    'cancel' => ['delete', 'hrm.departments.import.cancel'],
]);

test('a workbook is staged for the selected company with its columns mapped', function () {
    $company = Company::factory()->create();

    $sheetImport = stageDepartments(departmentImporter($company), [['HR', 'Human Resources', 'Active']]);

    expect($sheetImport->company_id)->toBe($company->id)
        ->and($sheetImport->payload['sheets'][0]['suggested_map'])->toMatchArray(['code' => 0, 'name' => 1, 'status' => 2]);
});

test('uploading without a selected company is refused', function () {
    $user = userWithPermissions('hrm.departments.import');

    $response = $this->actingAs($user)->post(route('hrm.departments.import.store'), [
        'file' => uploadedWorkbook(['Departments' => [['Code', 'Name'], ['HR', 'Human Resources']]]),
    ]);

    assertToast($response, 'error');
    expect(SheetImport::query()->count())->toBe(0);
});

test('confirming creates departments in the company, normalised like the form, and audits each', function () {
    $company = Company::factory()->create();
    $sheetImport = stageDepartments(departmentImporter($company), [
        [' hr ', ' Human   Resources ', null],
        ['prod-1', 'Production', 'inactive'],
    ]);

    config(['audit.console' => true]);

    assertToast($this->post(route('hrm.departments.import.confirm', $sheetImport), departmentMapping()), 'success');

    $departments = Department::withoutGlobalScopes()->orderBy('code')->get();

    expect($departments->pluck('code')->all())->toBe(['HR', 'PROD-1'])
        ->and($departments->pluck('name')->all())->toBe(['Human Resources', 'Production'])
        ->and($departments->pluck('status')->all())->toBe([RecordStatus::Active, RecordStatus::Inactive])
        ->and($departments->pluck('company_id')->unique()->all())->toBe([$company->id])
        ->and(AuditLog::where('event', 'created')->where('auditable_type', 'department')->count())->toBe(2);
});

test('a row matching an existing department by code or by name updates that department', function () {
    $company = Company::factory()->create();
    $byCode = Department::factory()->for($company)->create(['code' => 'HR', 'name' => 'Personnel']);
    $byName = Department::factory()->for($company)->create(['code' => 'OLD', 'name' => 'Finance']);

    $sheetImport = stageDepartments(departmentImporter($company), [
        ['HR', 'Human Resources', null],
        ['FIN', 'finance', null],
    ]);

    $this->post(route('hrm.departments.import.confirm', $sheetImport), departmentMapping('update'));

    expect($byCode->refresh()->name)->toBe('Human Resources')
        ->and($byName->refresh()->code)->toBe('FIN')
        ->and(Department::withoutGlobalScopes()->count())->toBe(2);
});

test('a matching row is skipped when the uploader chose skip', function () {
    $company = Company::factory()->create();
    $existing = Department::factory()->for($company)->create(['code' => 'HR', 'name' => 'Personnel']);

    $sheetImport = stageDepartments(departmentImporter($company), [['XX', 'personnel', null]]);

    $this->postJson(route('hrm.departments.import.review', $sheetImport), departmentMapping('skip'))
        ->assertOk()
        ->assertJson(['created' => 0, 'updated' => 0, 'skipped' => 1, 'rejected' => 0]);

    $this->post(route('hrm.departments.import.confirm', $sheetImport), departmentMapping('skip'));

    expect($existing->refresh()->code)->toBe('HR');
});

test('a row whose code and name belong to two different departments is rejected', function () {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['code' => 'HR', 'name' => 'Human Resources']);
    Department::factory()->for($company)->create(['code' => 'FIN', 'name' => 'Finance']);

    $sheetImport = stageDepartments(departmentImporter($company), [
        ['HR', 'Finance', null],
        ['NEW', 'New Department', null],
    ]);

    $this->postJson(route('hrm.departments.import.review', $sheetImport), departmentMapping())
        ->assertOk()
        ->assertJson([
            'created' => 1,
            'rejected' => 1,
            'rejects' => [['sheet' => 'Departments', 'line' => 2]],
        ]);

    assertToast($this->post(route('hrm.departments.import.confirm', $sheetImport), departmentMapping()), 'warning');

    expect(Department::withoutGlobalScopes()->orderBy('code')->pluck('code')->all())->toBe(['FIN', 'HR', 'NEW'])
        ->and(Department::withoutGlobalScopes()->where('code', 'HR')->sole()->name)->toBe('Human Resources');
});

test('a row matching a deleted department is rejected rather than reviving it', function () {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['code' => 'HR', 'name' => 'Human Resources'])->delete();

    $sheetImport = stageDepartments(departmentImporter($company), [['HR', 'Human Resources', null]]);

    $this->postJson(route('hrm.departments.import.review', $sheetImport), departmentMapping())
        ->assertJson(['created' => 0, 'updated' => 0, 'rejected' => 1]);
});

test('a name repeated in the file, a code repeated in the file and a bad status are rejected', function () {
    $company = Company::factory()->create();

    $sheetImport = stageDepartments(departmentImporter($company), [
        ['HR', 'Human Resources', null],
        ['HR2', 'human resources', null],
        ['hr', 'Another', null],
        ['QA', 'Quality', 'retired'],
    ]);

    $this->postJson(route('hrm.departments.import.review', $sheetImport), departmentMapping())
        ->assertOk()
        ->assertJson(['created' => 1, 'rejected' => 3]);
});

test('the same code in another company is not a duplicate', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = Department::factory()->for($theirs)->create(['code' => 'HR', 'name' => 'Human Resources']);

    $sheetImport = stageDepartments(departmentImporter($mine), [['HR', 'Human Resources', null]]);

    $this->post(route('hrm.departments.import.confirm', $sheetImport), departmentMapping());

    expect(Department::withoutGlobalScopes()->where('company_id', $mine->id)->count())->toBe(1)
        ->and($foreign->refresh()->updated_at->equalTo($foreign->created_at))->toBeTrue();
});

test('a file staged for one company is not confirmed while working in another', function () {
    [$first, $second] = Company::factory()->count(2)->create();
    $uploader = departmentImporter($first);
    $uploader->companies()->attach($second);

    $sheetImport = stageDepartments($uploader, [['HR', 'Human Resources', null]]);

    $uploader->forceFill(['selected_company_id' => $second->id])->saveQuietly();

    assertToast($this->post(route('hrm.departments.import.confirm', $sheetImport), departmentMapping()), 'warning');

    $this->postJson(route('hrm.departments.import.review', $sheetImport), departmentMapping())->assertStatus(422);

    expect(Department::withoutGlobalScopes()->count())->toBe(0);
});

test('the list offers the import only to an importer with a company', function () {
    $company = Company::factory()->create();

    $this->actingAs(departmentImporter($company))
        ->get(route('hrm.departments.index'))
        ->assertInertia(fn ($page) => $page->has('importFields', 3)->has('duplicateActions'));

    $viewer = userWithPermissions('hrm.departments.view');
    $viewer->companies()->attach($company);

    $this->actingAs($viewer)
        ->get(route('hrm.departments.index'))
        ->assertInertia(fn ($page) => $page->where('importFields', []));
});
