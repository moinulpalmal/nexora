<?php

namespace App\Http\Controllers\Hrm;

use App\Enums\Imports\DuplicateAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\Hrm\DesignationIndexRequest;
use App\Http\Requests\Hrm\DesignationRequest;
use App\Models\Hrm\Designation;
use App\Services\Hrm\DesignationService;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportRegistry;
use App\Services\Imports\ImportRunner;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HRM → Designations: a group-wide list (ARCHITECTURE.md §9.10), shaped as
 * {@see DepartmentController} without the company — the page works whether
 * or not a company is selected. Every write arrives from a dialog on it and
 * answers `back()` with a toast.
 */
class DesignationController extends Controller
{
    private const string IMPORT_KEY = 'hrm.designations';

    public function __construct(
        protected DesignationService $designations,
        protected ImportRegistry $importRegistry,
        protected ImportRunner $imports,
    ) {}

    public function index(DesignationIndexRequest $request): Response
    {
        $filters = $request->filters();

        $designations = Designation::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Designation $designation): array => [
                'id' => $designation->id,
                'code' => $designation->code,
                'name' => $designation->name,
                'status' => $designation->status->value,
                'created_at' => $designation->created_at?->toDateString(),
                'deleted_at' => $designation->deleted_at?->toDateString(),
            ]);

        // Built only for someone who can import, so a read-only role pays for
        // none of it.
        $canImport = (bool) $request->user()?->can('hrm.designations.import');

        return Inertia::render('hrm/designations/index', [
            'designations' => $designations,
            'statuses' => RecordStatus::options(),
            'sortable' => Designation::SORTABLE,
            'filterable' => Designation::FILTERABLE,
            'perPageOptions' => DesignationIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Designation::query()->count(),
                'trashed' => Designation::onlyTrashed()->count(),
            ],
            'importFields' => $canImport
                ? array_map(fn (ImportField $field): array => $field->toArray(), $this->importRegistry->resolve(self::IMPORT_KEY)->fields())
                : [],
            'duplicateActions' => $canImport ? DuplicateAction::options() : [],
            'maxFileSizeKb' => (int) config('sheet-import.limits.max_file_size_kb'),
            'pendingImport' => $canImport ? $this->imports->pendingFor($request->user(), self::IMPORT_KEY) : null,
        ]);
    }

    public function store(DesignationRequest $request): RedirectResponse
    {
        $this->designations->create($request->designationAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation created.')]);

        return back();
    }

    public function update(DesignationRequest $request, Designation $designation): RedirectResponse
    {
        $this->designations->update($designation, $request->designationAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation updated.')]);

        return back();
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $blocker = $this->designations->deletionBlocker($designation);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->designations->delete($designation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation moved to Deleted.')]);

        return back();
    }

    public function restore(Designation $designation): RedirectResponse
    {
        $this->designations->restore($designation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation restored.')]);

        return back();
    }

    public function forceDelete(Designation $designation): RedirectResponse
    {
        $blocker = $this->designations->deletionBlocker($designation);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->designations->forceDelete($designation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked designations (Active tab).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $result = $this->designations->bulkDelete(Designation::query()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count designation moved to Deleted.|:count designations moved to Deleted.');
    }

    /**
     * Permanently delete the ticked designations (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $result = $this->designations->bulkForceDelete(Designation::onlyTrashed()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count designation permanently deleted.|:count designations permanently deleted.');
    }

    /**
     * `success` when every ticked designation went, `warning` naming each one
     * a guard kept, as `Admin\CompanyController::bulkToast()`.
     *
     * @param  array{done: int, refused: list<string>}  $result
     */
    protected function bulkToast(array $result, string $doneMessage): RedirectResponse
    {
        $message = trans_choice($doneMessage, $result['done'], ['count' => $result['done']]);

        if ($result['refused'] === []) {
            Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => $message.' '.__('Not deleted — :reasons', ['reasons' => implode(' ', $result['refused'])]),
        ]);

        return back();
    }

    protected function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'warning', 'message' => $message]);

        return back();
    }
}
