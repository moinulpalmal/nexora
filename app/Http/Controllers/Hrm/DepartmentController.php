<?php

namespace App\Http\Controllers\Hrm;

use App\Enums\Imports\DuplicateAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\Hrm\DepartmentIndexRequest;
use App\Http\Requests\Hrm\DepartmentRequest;
use App\Models\Hrm\Department;
use App\Services\Admin\SelectedCompany;
use App\Services\Hrm\DepartmentService;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportRegistry;
use App\Services\Imports\ImportRunner;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HRM → Departments: the first company-scoped list (ARCHITECTURE.md §9.10).
 * One index page with Active/Deleted tabs, as `Admin\UserController`; every
 * write arrives from a dialog on it and answers `back()` with a toast.
 *
 * Every query here is the selected company's: `Department`'s scope sees to
 * the list and to route-model binding (another company's department is a 404),
 * and the service stamps the company on create. With no company selected the
 * page gets `company: null` and draws a panel instead of the table.
 */
class DepartmentController extends Controller
{
    private const string IMPORT_KEY = 'hrm.departments';

    public function __construct(
        protected DepartmentService $departments,
        protected SelectedCompany $selectedCompany,
        protected ImportRegistry $importRegistry,
        protected ImportRunner $imports,
    ) {}

    public function index(DepartmentIndexRequest $request): Response
    {
        $filters = $request->filters();
        $company = $this->selectedCompany->current();

        $departments = Department::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Department $department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'status' => $department->status->value,
                'created_at' => $department->created_at?->toDateString(),
                'deleted_at' => $department->deleted_at?->toDateString(),
            ]);

        // Built only for someone who can import, so a read-only role pays for
        // none of it.
        $canImport = $company !== null && (bool) $request->user()?->can('hrm.departments.import');

        return Inertia::render('hrm/departments/index', [
            'departments' => $departments,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
            ],
            'statuses' => RecordStatus::options(),
            'sortable' => Department::SORTABLE,
            'filterable' => Department::FILTERABLE,
            'perPageOptions' => DepartmentIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Department::query()->count(),
                'trashed' => Department::onlyTrashed()->count(),
            ],
            'importFields' => $canImport
                ? array_map(fn (ImportField $field): array => $field->toArray(), $this->importRegistry->resolve(self::IMPORT_KEY)->fields())
                : [],
            'duplicateActions' => $canImport ? DuplicateAction::options() : [],
            'maxFileSizeKb' => (int) config('sheet-import.limits.max_file_size_kb'),
            'pendingImport' => $canImport ? $this->imports->pendingFor($request->user(), self::IMPORT_KEY) : null,
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $companyId = $this->selectedCompany->id();

        if ($companyId === null) {
            return $this->refuse(__('Select a company in the header before adding a department.'));
        }

        $this->departments->create($companyId, $request->departmentAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department created.')]);

        return back();
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departments->update($department, $request->departmentAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department updated.')]);

        return back();
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->departments->delete($department);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department moved to Deleted.')]);

        return back();
    }

    public function restore(Department $department): RedirectResponse
    {
        $this->departments->restore($department);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department restored.')]);

        return back();
    }

    public function forceDelete(Department $department): RedirectResponse
    {
        $this->departments->forceDelete($department);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked departments (Active tab). The model's scope confines
     * the query to the selected company, so an id of another company's
     * department is not found and not counted (ARCHITECTURE.md §9.10).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $count = $this->departments->bulkDelete(Department::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(
            ':count department moved to Deleted.|:count departments moved to Deleted.',
            $count,
            ['count' => $count],
        )]);

        return back();
    }

    /**
     * Permanently delete the ticked departments (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $count = $this->departments->bulkForceDelete(Department::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(
            ':count department permanently deleted.|:count departments permanently deleted.',
            $count,
            ['count' => $count],
        )]);

        return back();
    }

    protected function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'warning', 'message' => $message]);

        return back();
    }
}
