<?php

use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| HRM → Employees: creating a department or designation from the form
|--------------------------------------------------------------------------
|
| Owner, 2026-10-31: typing a department or designation that does not exist
| offers "Create …" to a user who may create one; the full form stacks over
| the employee form, and its save selects the new record without losing what
| was typed. The store rules themselves are feature tests
| (`tests/Feature/Hrm/DepartmentTest.php`, `DesignationTest.php`).
|
*/

function inlineLookupActor(Company $company, string ...$permissions): User
{
    $user = userWithPermissions('hrm.employees.view', 'hrm.employees.create', ...$permissions);
    $user->companies()->attach($company);
    $user->forceFill(['selected_company_id' => $company->id])->saveQuietly();

    return $user;
}

test('a new department is created from the employee form and chosen for it', function () {
    $company = Company::factory()->create();
    $this->actingAs(inlineLookupActor($company, 'hrm.departments.create'));

    $page = visit('/hrm/employees');

    $page->click('[data-test="new-employee"]')
        ->fill('input[name="name"]', 'Rahima Akter')
        ->click('[data-test="employee-department"]')
        ->type(':focus', 'Quality Control')
        ->click('[data-test="combobox-create"]')
        ->assertValue('#inline-department-name', 'Quality Control')
        ->assertValue('#inline-department-code', 'QUALITY-CONTROL')
        ->click('[data-test="save-department"]')
        ->waitForText('Department created.')
        ->assertSeeIn('[data-test="employee-department"]', 'Quality Control')
        ->assertValue('input[name="name"]', 'Rahima Akter')
        ->assertNoJavaScriptErrors();

    expect(Department::where('code', 'QUALITY-CONTROL')->value('company_id'))->toBe($company->id);
});

test('a new designation is created from the employee form and chosen for it', function () {
    $company = Company::factory()->create();
    $this->actingAs(inlineLookupActor($company, 'hrm.designations.create'));

    $page = visit('/hrm/employees');

    $page->click('[data-test="new-employee"]')
        ->click('[data-test="employee-designation"]')
        ->type(':focus', 'Line Chief')
        ->click('[data-test="combobox-create"]')
        ->click('[data-test="save-designation"]')
        ->waitForText('Designation created.')
        ->assertSeeIn('[data-test="employee-designation"]', 'Line Chief')
        ->assertNoJavaScriptErrors();

    expect(Designation::where('code', 'LINE-CHIEF')->exists())->toBeTrue();
});

test('without the create permission the combobox offers no create row', function () {
    $company = Company::factory()->create();
    $this->actingAs(inlineLookupActor($company));

    $page = visit('/hrm/employees');

    $page->click('[data-test="new-employee"]')
        ->click('[data-test="employee-department"]')
        ->assertMissing('[data-test="combobox-create"]')
        ->assertNoJavaScriptErrors();
});
