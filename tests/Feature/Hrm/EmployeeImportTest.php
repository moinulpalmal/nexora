<?php

use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\Hrm\Employee;
use App\Models\SheetImport;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| HRM → Employees → Import
|--------------------------------------------------------------------------
|
| A company-scoped row writer like departments (ARCHITECTURE.md §9.13). What
| is specific to employees: the ID is matched across the whole group,
| department and designation are looked up by code, mobiles and dates are
| normalised from spreadsheet cells, and an import never opens an account.
|
*/

const EMPLOYEE_SHEET_HEADER = ['Employee ID', 'Name', 'Email', 'Department code', 'Designation code', 'Official mobile', 'Joining date'];

function employeeImporter(Company $company, string ...$extra): User
{
    $user = userWithPermissions('hrm.employees.view', 'hrm.employees.import', ...$extra);
    $user->companies()->attach($company);
    $user->forceFill(['selected_company_id' => $company->id])->saveQuietly();

    return $user;
}

/**
 * @param  list<list<string|int|float|null>>  $rows
 */
function stageEmployees(User $uploader, array $rows): SheetImport
{
    test()->actingAs($uploader)->post(route('hrm.employees.import.store'), [
        'file' => uploadedWorkbook(['Employees' => [EMPLOYEE_SHEET_HEADER, ...$rows]], 'employees.xlsx'),
    ]);

    return SheetImport::query()->pending()->where('inserted_by', $uploader->id)->latest('id')->firstOrFail();
}

/** @return array{map: array<int, array<string, int>>, on_duplicate: string} */
function employeeMapping(string $onDuplicate = 'update'): array
{
    return ['map' => [0 => [
        'employee_code' => 0,
        'name' => 1,
        'email' => 2,
        'department_code' => 3,
        'designation_code' => 4,
        'official_mobile_no' => 5,
        'joining_date' => 6,
    ]], 'on_duplicate' => $onDuplicate];
}

test('the import routes are refused without the import permission', function () {
    $user = userWithPermissions('hrm.employees.view', 'hrm.employees.create');

    $this->actingAs($user)->get(route('hrm.employees.import.template'))->assertForbidden();
});

test('rows are created in the selected company with codes looked up and cells normalised, and no account', function () {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['code' => 'HR']);
    Designation::factory()->create(['code' => 'MGR']);

    $sheetImport = stageEmployees(employeeImporter($company), [
        ['e1001', 'Rahima Akter', 'Rahima@Example.com', 'hr', 'mgr', 1712345678, '2026-01-15'],
    ]);

    assertToast($this->post(route('hrm.employees.import.confirm', $sheetImport), employeeMapping()), 'success');

    $employee = Employee::withoutGlobalScopes()->sole();

    expect($employee->company_id)->toBe($company->id)
        ->and($employee->employee_code)->toBe('E1001')
        ->and($employee->email)->toBe('rahima@example.com')
        ->and($employee->official_mobile_no)->toBe('01712345678')
        ->and($employee->joining_date->toDateString())->toBe('2026-01-15')
        ->and($employee->user_id)->toBeNull();
});

test('a date cell and a number-cell mobile are read as the sheet meant them', function () {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['code' => 'HR']);
    Designation::factory()->create(['code' => 'MGR']);

    // 46054 is the Excel serial of 2026-02-01; the reader hands both cells over as digits.
    $sheetImport = stageEmployees(employeeImporter($company), [
        ['E2001', 'Karim Uddin', 'karim@example.com', 'HR', 'MGR', 1712000111, 46054],
    ]);

    assertToast($this->post(route('hrm.employees.import.confirm', $sheetImport), employeeMapping()), 'success');

    $employee = Employee::withoutGlobalScopes()->sole();

    expect($employee->joining_date->toDateString())->toBe('2026-02-01')
        ->and($employee->official_mobile_no)->toBe('01712000111');
});

test('rows are rejected for an unknown department, another company\'s ID, a taken email or a bad mobile', function () {
    [$company, $other] = Company::factory()->count(2)->create();
    Department::factory()->for($company)->create(['code' => 'HR']);
    Designation::factory()->create(['code' => 'MGR']);
    Employee::factory()->for($other)->create(['employee_code' => 'THEIRS', 'email' => 'taken@example.com']);

    $sheetImport = stageEmployees(employeeImporter($company), [
        ['E1', 'Unknown Department', 'one@example.com', 'NOPE', 'MGR', null, null],
        ['THEIRS', 'Other Company', 'two@example.com', 'HR', 'MGR', null, null],
        ['E3', 'Taken Email', 'taken@example.com', 'HR', 'MGR', null, null],
        ['E4', 'Bad Mobile', 'four@example.com', 'HR', 'MGR', '12345', null],
        ['E5', 'Good Row', 'five@example.com', 'HR', 'MGR', null, null],
    ]);

    $this->postJson(route('hrm.employees.import.review', $sheetImport), employeeMapping())
        ->assertOk()
        ->assertJson(['created' => 1, 'rejected' => 4]);
});

test('an existing employee is updated and their account follows', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->withAccount()->create(['employee_code' => 'E1001']);
    $employee->department->update(['code' => 'HR']);
    $employee->designation->update(['code' => 'MGR']);

    $sheetImport = stageEmployees(employeeImporter($company), [
        ['E1001', 'Renamed Person', 'renamed@example.com', 'HR', 'MGR', null, null],
    ]);

    $this->post(route('hrm.employees.import.confirm', $sheetImport), employeeMapping());

    $employee->refresh();

    expect($employee->name)->toBe('Renamed Person')
        ->and($employee->user->email)->toBe('renamed@example.com')
        ->and($employee->user->isActive())->toBeTrue();
});
