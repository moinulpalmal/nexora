<?php

use App\Enums\RecordStatus;
use App\Mail\Admin\UserPasswordResetMail;
use App\Mail\Admin\UserWelcomeMail;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| HRM → Employees
|--------------------------------------------------------------------------
|
| Company-scoped and restorable like departments (ARCHITECTURE.md §9.10),
| with a group-wide employee ID and email, and an optional sign-in account
| the form opens, keeps in step and closes (owner, 2026-10-29).
|
*/

/**
 * A user holding the permissions *and* working in the company.
 */
function employeeActor(Company $company, string ...$permissions): User
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
function employeePayload(Company $company, array $overrides = []): array
{
    return [
        'employee_code' => 'E1001',
        'name' => 'Rahima Akter',
        'email' => 'rahima@example.com',
        'department_id' => Department::factory()->for($company)->create()->id,
        'designation_id' => Designation::factory()->create()->id,
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accountPayload(Company $company, array $overrides = []): array
{
    return employeePayload($company, [
        'is_application_user' => '1',
        'password' => compliantPassword(),
        'password_confirmation' => compliantPassword(),
        ...$overrides,
    ]);
}

/**
 * The form values of an existing employee, as the edit dialog posts them.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function editPayload(Employee $employee, array $overrides = []): array
{
    return [
        'employee_code' => $employee->employee_code,
        'name' => $employee->name,
        'email' => $employee->email,
        'department_id' => $employee->department_id,
        'designation_id' => $employee->designation_id,
        'official_mobile_no' => $employee->official_mobile_no,
        'status' => $employee->status->value,
        ...$overrides,
    ];
}

const EMPLOYEE_MANAGER = ['hrm.employees.view', 'hrm.employees.create', 'hrm.employees.update', 'hrm.employees.delete', 'hrm.employees.restore', 'hrm.employees.force-delete'];

const ACCOUNT_MANAGER = [...EMPLOYEE_MANAGER, 'hrm.employees.create-account'];

beforeEach(function () {
    Mail::fake();
});

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('hrm.employees.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(employeeActor(Company::factory()->create()))
        ->get(route('hrm.employees.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->create();

    if ($trashed) {
        $employee->delete();
    }

    $this->actingAs(employeeActor($company, 'hrm.employees.view'))
        ->{$method}(route($route, $employee), editPayload($employee))
        ->assertForbidden();
})->with([
    'update' => ['put', 'hrm.employees.update', false],
    'delete' => ['delete', 'hrm.employees.destroy', false],
    'restore' => ['put', 'hrm.employees.restore', true],
    'force delete' => ['delete', 'hrm.employees.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $company = Company::factory()->create();

    $this->actingAs(employeeActor($company, 'hrm.employees.view'))
        ->post(route('hrm.employees.store'), employeePayload($company))
        ->assertForbidden();
});

/*
| The list
*/

test('the list shows only the selected company\'s employees, without salary unless allowed', function (array $extra, bool $seesSalary) {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    Employee::factory()->for($mine)->create(['employee_code' => 'MINE', 'monthly_salary' => '50000.00']);
    Employee::factory()->for($theirs)->create(['employee_code' => 'THEIRS']);

    $this->actingAs(employeeActor($mine, 'hrm.employees.view', ...$extra))
        ->get(route('hrm.employees.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hrm/employees/index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employee_code', 'MINE')
            ->where('canSeeCompensation', $seesSalary)
            ->when(
                $seesSalary,
                fn ($page) => $page->where('employees.data.0.monthly_salary', '50000.00'),
                fn ($page) => $page->missing('employees.data.0.monthly_salary'),
            )
            ->etc(),
        );
})->with([
    'without view-compensation' => [[], false],
    'with view-compensation' => [['hrm.employees.view-compensation'], true],
]);

test('the list filters by department', function () {
    $company = Company::factory()->create();
    $kept = Employee::factory()->for($company)->create();
    Employee::factory()->for($company)->create();

    $this->actingAs(employeeActor($company, 'hrm.employees.view'))
        ->get(route('hrm.employees.index', ['filter' => ['department_id' => $kept->department_id]]))
        ->assertInertia(fn ($page) => $page
            ->has('employees.data', 1)
            ->where('employees.data.0.id', $kept->id),
        );
});

test('another company\'s employee is a 404', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = Employee::factory()->for($theirs)->create();

    $this->actingAs(employeeActor($mine, ...EMPLOYEE_MANAGER))
        ->put(route('hrm.employees.update', $foreign), editPayload($foreign))
        ->assertNotFound();
});

/*
| Creating and validation
*/

test('an employee is created in the selected company, ignoring a posted company', function () {
    [$company, $other] = Company::factory()->count(2)->create();

    $response = $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->post(route('hrm.employees.store'), employeePayload($company, [
            'employee_code' => ' e-1001 ',
            'email' => ' Rahima@Example.COM ',
            'official_mobile_no' => '+880 1712-345678',
            'company_id' => $other->id,
        ]));

    assertToast($response, 'success');

    $employee = Employee::withoutGlobalScopes()->sole();

    expect($employee->company_id)->toBe($company->id)
        ->and($employee->employee_code)->toBe('E-1001')
        ->and($employee->email)->toBe('rahima@example.com')
        ->and($employee->official_mobile_no)->toBe('01712345678')
        ->and($employee->user_id)->toBeNull();
});

test('the employee ID and email are unique across every company, deleted employees included', function (string $field, string $value) {
    [$company, $other] = Company::factory()->count(2)->create();
    Employee::factory()->for($other)->create(['employee_code' => 'E1001', 'email' => 'taken@example.com'])->delete();

    $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->post(route('hrm.employees.store'), employeePayload($company, [$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'employee ID' => ['employee_code', 'e1001'],
    'email' => ['email', 'TAKEN@example.com'],
]);

test('a mobile number must be Bangladeshi', function () {
    $company = Company::factory()->create();

    $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->post(route('hrm.employees.store'), employeePayload($company, ['personal_mobile_no' => '01212345678']))
        ->assertSessionHasErrors('personal_mobile_no');
});

test('the department must be an active one of the selected company', function (string $case) {
    [$company, $other] = Company::factory()->count(2)->create();

    $department = match ($case) {
        'another company' => Department::factory()->for($other)->create(),
        'inactive' => Department::factory()->for($company)->inactive()->create(),
    };

    $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->post(route('hrm.employees.store'), employeePayload($company, ['department_id' => $department->id]))
        ->assertSessionHasErrors('department_id');
})->with(['another company', 'inactive']);

test('an employee keeps a department that was deactivated after they joined it', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->create();
    $employee->department->update(['status' => RecordStatus::Inactive]);

    $response = $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->put(route('hrm.employees.update', $employee), editPayload($employee, ['name' => 'Renamed']));

    assertToast($response, 'success');
    expect($employee->fresh()->name)->toBe('Renamed');
});

test('the salary is saved only by an actor allowed to see it', function (array $extra, ?string $stored) {
    $company = Company::factory()->create();

    $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER, ...$extra))
        ->post(route('hrm.employees.store'), employeePayload($company, ['monthly_salary' => '45000.50']));

    expect(Employee::withoutGlobalScopes()->sole()->monthly_salary)->toBe($stored);
})->with([
    'without view-compensation' => [[], null],
    'with view-compensation' => [['hrm.employees.view-compensation'], '45000.50'],
]);

/*
| The sign-in account
*/

test('ticking Application user is refused without the create-account permission', function () {
    $company = Company::factory()->create();

    $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->post(route('hrm.employees.store'), accountPayload($company))
        ->assertSessionHasErrors('is_application_user');

    expect(User::query()->where('username', 'e1001')->exists())->toBeFalse();
});

test('an application user gets an account: username from the code, the company, no role, a forced change and a welcome mail', function () {
    $company = Company::factory()->create();

    $response = $this->actingAs(employeeActor($company, ...ACCOUNT_MANAGER))
        ->post(route('hrm.employees.store'), accountPayload($company, ['official_mobile_no' => '01712345678']));

    assertToast($response, 'success');

    $employee = Employee::withoutGlobalScopes()->sole();
    $user = $employee->user;

    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('e1001')
        ->and($user->email)->toBe('rahima@example.com')
        ->and($user->employee_code)->toBe('E1001')
        ->and($user->phone)->toBe('01712345678')
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->roles)->toBeEmpty()
        ->and($user->companies->pluck('id')->all())->toBe([$company->id]);

    Mail::assertQueued(UserWelcomeMail::class, fn (UserWelcomeMail $mail) => $mail->hasTo('rahima@example.com'));
});

test('opening an account needs a temporary password', function () {
    $company = Company::factory()->create();

    $this->actingAs(employeeActor($company, ...ACCOUNT_MANAGER))
        ->post(route('hrm.employees.store'), accountPayload($company, ['password' => '', 'password_confirmation' => '']))
        ->assertSessionHasErrors('password');
});

test('an account is refused when its username or email belongs to another account', function (array $existing, array $overrides, string $field) {
    $company = Company::factory()->create();
    User::factory()->create($existing);

    $this->actingAs(employeeActor($company, ...ACCOUNT_MANAGER))
        ->post(route('hrm.employees.store'), accountPayload($company, $overrides))
        ->assertSessionHasErrors($field);

    expect(Employee::withoutGlobalScopes()->exists())->toBeFalse();
})->with([
    'username' => [['username' => 'e1001'], [], 'employee_code'],
    'email' => [['email' => 'rahima@example.com'], [], 'email'],
    'too short to be a username' => [[], ['employee_code' => 'E1'], 'employee_code'],
]);

test('every save copies the employee\'s details onto the account', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->withAccount()->create();

    $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->put(route('hrm.employees.update', $employee), editPayload($employee, [
            'employee_code' => 'NEW-01',
            'name' => 'New Name',
            'email' => 'new@example.com',
            'official_mobile_no' => '01812345678',
        ]));

    $user = $employee->fresh()->user;

    expect($user->username)->toBe('new-01')
        ->and($user->employee_code)->toBe('NEW-01')
        ->and($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->phone)->toBe('01812345678')
        ->and($user->isActive())->toBeTrue();
});

test('unticking closes the account and keeps it; re-ticking re-opens it with a new temporary password', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->withAccount()->create();
    $actor = employeeActor($company, ...ACCOUNT_MANAGER);

    $this->actingAs($actor)
        ->put(route('hrm.employees.update', $employee), editPayload($employee, ['is_application_user' => '0']));

    $user = $employee->fresh()->user;
    expect($user->isActive())->toBeFalse();

    $this->actingAs($actor)
        ->put(route('hrm.employees.update', $employee), editPayload($employee, ['is_application_user' => '1']))
        ->assertSessionHasErrors('password');

    $this->actingAs($actor)
        ->put(route('hrm.employees.update', $employee), editPayload($employee, [
            'is_application_user' => '1',
            'password' => compliantPassword(),
            'password_confirmation' => compliantPassword(),
        ]));

    $user->refresh();

    expect($user->isActive())->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($employee->fresh()->user_id)->toBe($user->id);

    Mail::assertQueued(UserPasswordResetMail::class);
});

test('deactivating or deleting the employee closes the account; restoring does not re-open it', function () {
    $company = Company::factory()->create();
    $actor = employeeActor($company, ...EMPLOYEE_MANAGER);

    $inactive = Employee::factory()->for($company)->withAccount()->create();
    $this->actingAs($actor)->put(route('hrm.employees.update', $inactive), editPayload($inactive, ['status' => RecordStatus::Inactive->value]));
    expect($inactive->fresh()->user->isActive())->toBeFalse();

    $deleted = Employee::factory()->for($company)->withAccount()->create();
    $this->actingAs($actor)->delete(route('hrm.employees.destroy', $deleted));
    expect($deleted->fresh()->user->isActive())->toBeFalse();

    $this->actingAs($actor)->put(route('hrm.employees.restore', $deleted));
    expect($deleted->fresh()->trashed())->toBeFalse()
        ->and($deleted->fresh()->user->isActive())->toBeFalse();
});

test('the account of the last active super admin cannot be closed from HRM', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->create();
    $admin = superAdmin();
    $employee->forceFill(['user_id' => $admin->id])->saveQuietly();

    $response = $this->actingAs(employeeActor($company, ...ACCOUNT_MANAGER))
        ->delete(route('hrm.employees.destroy', $employee));

    assertToast($response, 'warning');
    expect($employee->fresh()->trashed())->toBeFalse()
        ->and($admin->fresh()->isActive())->toBeTrue();
});

test('force deleting keeps the account in Admin, inactive', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->withAccount()->create();
    $userId = $employee->user_id;
    $employee->delete();

    $this->actingAs(employeeActor($company, ...EMPLOYEE_MANAGER))
        ->delete(route('hrm.employees.force-delete', $employee));

    expect(Employee::withoutGlobalScopes()->withTrashed()->exists())->toBeFalse()
        ->and(User::find($userId)->isActive())->toBeFalse();
});

/*
| Deletion guards the employee brings to its masters (§9.14)
*/

test('a department, designation or company in use by an employee cannot be deleted', function (string $route, Closure $target, array $permissions) {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->create();
    $employee->delete();

    $response = $this->actingAs(employeeActor($company, ...$permissions))
        ->delete(route($route, $target($employee)));

    assertToast($response, 'warning');
})->with([
    'department' => ['hrm.departments.destroy', fn (Employee $employee) => $employee->department_id, ['hrm.departments.delete']],
    'designation' => ['hrm.designations.destroy', fn (Employee $employee) => $employee->designation_id, ['hrm.designations.delete']],
]);

test('the department blocker also holds in bulk', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->create();

    $response = $this->actingAs(employeeActor($company, 'hrm.departments.delete'))
        ->delete(route('hrm.departments.bulk-destroy'), ['ids' => [$employee->department_id]]);

    assertToast($response, 'warning');
    expect(Department::query()->withoutGlobalScopes()->find($employee->department_id)->trashed())->toBeFalse();
});

/*
| Audit
*/

test('creating an employee is audited', function () {
    $company = Company::factory()->create();
    $actor = employeeActor($company, ...EMPLOYEE_MANAGER);
    $payload = employeePayload($company);

    config(['audit.console' => true]);

    $this->actingAs($actor)->post(route('hrm.employees.store'), $payload);

    expect(AuditLog::query()->where('auditable_type', 'employee')->where('event', 'created')->exists())->toBeTrue();
});
