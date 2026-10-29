<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| HRM → Departments
|--------------------------------------------------------------------------
|
| The first company-scoped master table (ARCHITECTURE.md §9.10): every read
| and write is the selected company's, `company_id` never comes from the
| browser, and code and name are unique per company — ignoring case, deleted
| departments included.
|
*/

/**
 * A user holding the permissions *and* working in the company — an actor
 * missing either fails every test for the wrong reason.
 */
function departmentActor(Company $company, string ...$permissions): User
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
function departmentPayload(array $overrides = []): array
{
    return [
        'code' => 'HR',
        'name' => 'Human Resources',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('hrm.departments.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(departmentActor(Company::factory()->create()))
        ->get(route('hrm.departments.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create();

    if ($trashed) {
        $department->delete();
    }

    $this->actingAs(departmentActor($company, 'hrm.departments.view'))
        ->{$method}(route($route, $department), departmentPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'hrm.departments.update', false],
    'delete' => ['delete', 'hrm.departments.destroy', false],
    'restore' => ['put', 'hrm.departments.restore', true],
    'force delete' => ['delete', 'hrm.departments.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(departmentActor(Company::factory()->create(), 'hrm.departments.view'))
        ->post(route('hrm.departments.store'), departmentPayload())
        ->assertForbidden();
});

/*
| Scoping
*/

test('the list shows only the selected company\'s departments', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    Department::factory()->for($mine)->create(['code' => 'MINE']);
    Department::factory()->for($theirs)->create(['code' => 'THEIRS']);

    $this->actingAs(departmentActor($mine, 'hrm.departments.view'))
        ->get(route('hrm.departments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hrm/departments/index')
            ->where('company.id', $mine->id)
            ->has('departments.data', 1)
            ->where('departments.data.0.code', 'MINE')
            ->where('counts', ['active' => 1, 'trashed' => 0]),
        );
});

test('another company\'s department is a 404', function (string $method, string $route) {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = Department::factory()->for($theirs)->create();

    $this->actingAs(departmentActor($mine, 'hrm.departments.update', 'hrm.departments.delete'))
        ->{$method}(route($route, $foreign), departmentPayload())
        ->assertNotFound();
})->with([
    'update' => ['put', 'hrm.departments.update'],
    'delete' => ['delete', 'hrm.departments.destroy'],
]);

test('with no company selected the page says so and lists nothing', function () {
    Department::factory()->create();
    $user = userWithPermissions('hrm.departments.view');
    $user->companies()->attach(Company::factory()->count(2)->create());

    $this->actingAs($user)
        ->get(route('hrm.departments.index'))
        ->assertInertia(fn ($page) => $page
            ->where('company', null)
            ->has('departments.data', 0)
            ->where('importFields', []),
        );
});

test('a department lands in the selected company whatever company_id is posted', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();

    $this->actingAs(departmentActor($mine, 'hrm.departments.create'));

    assertToast($this->post(route('hrm.departments.store'), departmentPayload(['company_id' => $theirs->id])), 'success');

    expect(Department::withoutGlobalScopes()->sole()->company_id)->toBe($mine->id);
});

test('creating without a selected company is refused', function () {
    $user = userWithPermissions('hrm.departments.create');

    assertToast($this->actingAs($user)->post(route('hrm.departments.store'), departmentPayload()), 'warning');

    expect(Department::withoutGlobalScopes()->count())->toBe(0);
});

/*
| Writes and duplicates
*/

test('a department is created with its code uppercased and its name tidied', function () {
    $company = Company::factory()->create();

    $this->actingAs(departmentActor($company, 'hrm.departments.create'))
        ->post(route('hrm.departments.store'), departmentPayload(['code' => ' prod-1 ', 'name' => ' Production   Floor ']))
        ->assertSessionHasNoErrors();

    $department = Department::withoutGlobalScopes()->sole();

    expect($department->code)->toBe('PROD-1')
        ->and($department->name)->toBe('Production Floor');
});

test('a code or name already used in the company is refused whatever its case', function (string $field, string $value) {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['code' => 'HR', 'name' => 'Human Resources']);

    $this->actingAs(departmentActor($company, 'hrm.departments.create'))
        ->post(route('hrm.departments.store'), departmentPayload(['code' => 'OTHER', 'name' => 'Other', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code, lower case' => ['code', 'hr'],
    'name, other case' => ['name', 'HUMAN RESOURCES'],
    'name, extra spaces' => ['name', 'Human   Resources '],
]);

test('the same code and name are allowed in another company', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    Department::factory()->for($theirs)->create(['code' => 'HR', 'name' => 'Human Resources']);

    $this->actingAs(departmentActor($mine, 'hrm.departments.create'))
        ->post(route('hrm.departments.store'), departmentPayload())
        ->assertSessionHasNoErrors();

    expect(Department::withoutGlobalScopes()->where('code', 'HR')->count())->toBe(2);
});

test('a deleted department keeps its code and name reserved and says to restore it', function () {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['code' => 'HR', 'name' => 'Human Resources'])->delete();

    $this->actingAs(departmentActor($company, 'hrm.departments.create'))
        ->post(route('hrm.departments.store'), departmentPayload())
        ->assertSessionHasErrors([
            'code' => 'The code "HR" belongs to a deleted department in this company. Restore it from the Deleted tab instead.',
            'name' => 'A deleted department in this company is named "Human Resources". Restore it from the Deleted tab instead.',
        ]);
});

test('a department can be edited, keeping its own values', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create(['code' => 'HR', 'name' => 'Human Resources']);

    $this->actingAs(departmentActor($company, 'hrm.departments.update'))
        ->put(route('hrm.departments.update', $department), departmentPayload(['status' => RecordStatus::Inactive->value]))
        ->assertSessionHasNoErrors();

    expect($department->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a department is soft deleted, restored and permanently deleted', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create();

    $this->actingAs(departmentActor($company, 'hrm.departments.delete', 'hrm.departments.restore', 'hrm.departments.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('hrm.departments.destroy', $department)), 'success');
    expect($department->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('hrm.departments.restore', $department)), 'success');
    expect($department->refresh()->trashed())->toBeFalse();

    $department->delete();
    assertToast($this->delete(route('hrm.departments.force-delete', $department)), 'success');

    expect(Department::withoutGlobalScopes()->withTrashed()->find($department->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'department')->exists())->toBeTrue();
});

test('creating a department is audited with its company', function () {
    $company = Company::factory()->create();

    $this->actingAs(departmentActor($company, 'hrm.departments.create'));
    config(['audit.console' => true]);

    $this->post(route('hrm.departments.store'), departmentPayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'department')->sole();

    expect($audit->new_values['code'])->toBe('HR')
        ->and((int) $audit->new_values['company_id'])->toBe($company->id);
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create();

    $this->actingAs(departmentActor($company, 'hrm.departments.view'))
        ->delete(route($route), ['ids' => [$department->id]])
        ->assertForbidden();
})->with(['hrm.departments.bulk-destroy', 'hrm.departments.bulk-force-delete']);

test('bulk delete moves the ticked departments to Deleted, never another company\'s', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    [$first, $second] = Department::factory()->for($mine)->count(2)->create();
    $foreign = Department::factory()->for($theirs)->create();

    $this->actingAs(departmentActor($mine, 'hrm.departments.delete'));

    $response = $this->delete(route('hrm.departments.bulk-destroy'), ['ids' => [$first->id, $second->id, $foreign->id]]);

    assertToast($response, 'success')->assertSessionHas('inertia.flash_data.toast.message', '2 departments moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($foreign->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes the ticked deleted departments and records each', function () {
    $company = Company::factory()->create();
    [$first, $second] = Department::factory()->for($company)->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(departmentActor($company, 'hrm.departments.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('hrm.departments.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(Department::withoutGlobalScopes()->withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'department')->count())->toBe(2);
});
