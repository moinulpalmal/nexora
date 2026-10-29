<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyIndexRequest;
use App\Http\Requests\Admin\CompanyRequest;
use App\Http\Requests\BulkRequest;
use App\Models\Admin\Company;
use App\Services\Admin\CompanyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Companies: one index page with Active/Deleted tabs, as Users; every
 * write arrives from a dialog on it and answers `back()` with a toast
 * (ARCHITECTURE.md §8.6). The deletion guard is `CompanyService`'s, refused
 * here as a `warning` — the actor can clear it — as `RoleController::destroy()`.
 */
class CompanyController extends Controller
{
    public function __construct(protected CompanyService $companies) {}

    public function index(CompanyIndexRequest $request): Response
    {
        $filters = $request->filters();

        $companies = Company::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->withCount('users')
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Company $company): array => [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
                'status' => $company->status->value,
                'users_count' => $company->users_count,
                'created_at' => $company->created_at?->toDateString(),
                'deleted_at' => $company->deleted_at?->toDateString(),
                'deletion_blocker' => $this->companies->deletionBlocker($company),
            ]);

        return Inertia::render('admin/companies/index', [
            'companies' => $companies,
            'statuses' => RecordStatus::options(),
            'sortable' => Company::SORTABLE,
            'filterable' => Company::FILTERABLE,
            'perPageOptions' => CompanyIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Company::query()->count(),
                'trashed' => Company::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $this->companies->create($request->companyAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company created.')]);

        return back();
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->companies->update($company, $request->companyAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company updated.')]);

        return back();
    }

    public function destroy(Company $company): RedirectResponse
    {
        $blocker = $this->companies->deletionBlocker($company);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->companies->delete($company);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company moved to Deleted.')]);

        return back();
    }

    public function restore(Company $company): RedirectResponse
    {
        $this->companies->restore($company);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company restored.')]);

        return back();
    }

    public function forceDelete(Company $company): RedirectResponse
    {
        $blocker = $this->companies->forceDeletionBlocker($company);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->companies->forceDelete($company);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked companies (Active tab). Ids already deleted or gone are
     * not found and not counted.
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $result = $this->companies->bulkDelete(Company::query()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count company moved to Deleted.|:count companies moved to Deleted.');
    }

    /**
     * Permanently delete the ticked companies (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $result = $this->companies->bulkForceDelete(Company::onlyTrashed()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count company permanently deleted.|:count companies permanently deleted.');
    }

    /**
     * `success` when every ticked company went, `warning` naming each one a
     * guard kept — the actor can clear those by moving their departments.
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
