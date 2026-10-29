<?php

namespace App\Http\Controllers\Hrm;

use App\Enums\Imports\DuplicateAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\Hrm\EmployeeIndexRequest;
use App\Http\Requests\Hrm\EmployeeRequest;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\Hrm\Employee;
use App\Services\Admin\SelectedCompany;
use App\Services\Hrm\EmployeeService;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportRegistry;
use App\Services\Imports\ImportRunner;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HRM → Employees, laid out as {@see DepartmentController}: one company-scoped
 * index page with Active/Deleted tabs, every write from a dialog on it,
 * answering `back()` with a toast (ARCHITECTURE.md §9.10).
 *
 * Two things the department list does not have. **The sign-in account**
 * (owner, 2026-10-29): a save that would close an account `UserService` will
 * not let go of is refused with `EmployeeService::accountBlocker()`'s sentence,
 * as a `warning` toast. **The salary** is projected only for an actor holding
 * `hrm.employees.view-compensation` — the column is absent from the props
 * otherwise, not merely hidden by the page.
 */
class EmployeeController extends Controller
{
    private const string IMPORT_KEY = 'hrm.employees';

    public function __construct(
        protected EmployeeService $employees,
        protected SelectedCompany $selectedCompany,
        protected ImportRegistry $importRegistry,
        protected ImportRunner $imports,
    ) {}

    public function index(EmployeeIndexRequest $request): Response
    {
        $filters = $request->filters();
        $company = $this->selectedCompany->current();
        $canSeeCompensation = (bool) $request->user()?->can('hrm.employees.view-compensation');

        $employees = Employee::query()
            ->with(['department:id,code,name', 'designation:id,code,name', 'user:id,username,status,deleted_at'])
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Employee $employee): array => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'email' => $employee->email,
                'department_id' => $employee->department_id,
                'department_name' => $employee->department->name,
                'designation_id' => $employee->designation_id,
                'designation_name' => $employee->designation->name,
                'official_mobile_no' => $employee->official_mobile_no,
                'personal_mobile_no' => $employee->personal_mobile_no,
                'joining_date' => $employee->joining_date?->toDateString(),
                ...($canSeeCompensation ? ['monthly_salary' => $employee->monthly_salary] : []),
                'status' => $employee->status->value,
                'is_application_user' => $employee->isApplicationUser(),
                'username' => $employee->user?->username,
                'created_at' => $employee->created_at?->toDateString(),
                'deleted_at' => $employee->deleted_at?->toDateString(),
            ]);

        // Built only for someone who can import, so a read-only role pays for
        // none of it.
        $canImport = $company !== null && (bool) $request->user()?->can('hrm.employees.import');

        return Inertia::render('hrm/employees/index', [
            'employees' => $employees,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
            ],
            'statuses' => RecordStatus::options(),
            'departments' => $company === null ? [] : $this->departmentOptions(),
            'designations' => $company === null ? [] : $this->designationOptions(),
            'canSeeCompensation' => $canSeeCompensation,
            'sortable' => Employee::SORTABLE,
            'filterable' => Employee::FILTERABLE,
            'perPageOptions' => EmployeeIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Employee::query()->count(),
                'trashed' => Employee::onlyTrashed()->count(),
            ],
            'importFields' => $canImport
                ? array_map(fn (ImportField $field): array => $field->toArray(), $this->importRegistry->resolve(self::IMPORT_KEY)->fields())
                : [],
            'duplicateActions' => $canImport ? DuplicateAction::options() : [],
            'maxFileSizeKb' => (int) config('sheet-import.limits.max_file_size_kb'),
            'pendingImport' => $canImport ? $this->imports->pendingFor($request->user(), self::IMPORT_KEY) : null,
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $companyId = $this->selectedCompany->id();

        if ($companyId === null) {
            return $this->refuse(__('Select a company in the header before adding an employee.'));
        }

        $mailedTo = $this->employees->create(
            $companyId,
            $request->employeeAttributes(),
            $request->wantsApplicationUser(),
            $request->temporaryPassword(),
        );

        return $this->saved(__('Employee created.'), $mailedTo);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $keepsAccountOpen = $request->wantsApplicationUser() && $request->string('status')->value() === RecordStatus::Active->value;
        $blocker = $this->employees->accountBlocker($employee, $keepsAccountOpen);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $mailedTo = $this->employees->update(
            $employee,
            $request->employeeAttributes(),
            $request->wantsApplicationUser(),
            $request->temporaryPassword(),
        );

        return $this->saved(__('Employee updated.'), $mailedTo);
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $blocker = $this->employees->accountBlocker($employee, false);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->employees->delete($employee);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee moved to Deleted.')]);

        return back();
    }

    public function restore(Employee $employee): RedirectResponse
    {
        $this->employees->restore($employee);

        Inertia::flash('toast', ['type' => 'success', 'message' => $employee->user_id === null
            ? __('Employee restored.')
            : __('Employee restored. Their sign-in account stays inactive until you tick Application user again.')]);

        return back();
    }

    public function forceDelete(Employee $employee): RedirectResponse
    {
        $blocker = $this->employees->accountBlocker($employee, false);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->employees->forceDelete($employee);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked employees (Active tab). The model's scope confines the
     * query to the selected company (ARCHITECTURE.md §9.10); an employee whose
     * account may not be closed is skipped by the service.
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $count = $this->employees->bulkDelete(Employee::query()->with('user')->whereKey($request->ids())->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(
            ':count employee moved to Deleted.|:count employees moved to Deleted.',
            $count,
            ['count' => $count],
        )]);

        return back();
    }

    /**
     * Permanently delete the ticked employees (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $count = $this->employees->bulkForceDelete(Employee::onlyTrashed()->with('user')->whereKey($request->ids())->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(
            ':count employee permanently deleted.|:count employees permanently deleted.',
            $count,
            ['count' => $count],
        )]);

        return back();
    }

    /**
     * The selected company's departments for the form and the filter —
     * inactive ones included and marked, so an employee already in one shows
     * it; the dialog offers only active ones for a new choice.
     *
     * @return list<array{value: int, label: string, hint: string, status: string}>
     */
    private function departmentOptions(): array
    {
        return Department::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->map(fn (Department $department): array => [
                'value' => $department->id,
                'label' => $department->name,
                'hint' => $department->code,
                'status' => $department->status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: int, label: string, hint: string, status: string}>
     */
    private function designationOptions(): array
    {
        return Designation::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->map(fn (Designation $designation): array => [
                'value' => $designation->id,
                'label' => $designation->name,
                'hint' => $designation->code,
                'status' => $designation->status->value,
            ])
            ->values()
            ->all();
    }

    private function saved(string $message, ?string $mailedTo): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $mailedTo === null
            ? $message
            : $message.' '.__('Their sign-in details are being emailed to :address.', ['address' => $mailedTo])]);

        return back();
    }

    protected function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'warning', 'message' => $message]);

        return back();
    }
}
