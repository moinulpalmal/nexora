<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\CertificationCategoryIndexRequest;
use App\Http\Requests\ProjectManagement\CertificationCategoryRequest;
use App\Models\ProjectManagement\CertificationCategory;
use App\Services\ProjectManagement\CertificationCategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Settings → Certification Categories: a group-wide list (ARCHITECTURE.md §9.10),
 * a copy of {@see SupplierCategoryController} — certifications point here as
 * suppliers point there. The page works whether or not a company is selected.
 *
 * No spreadsheet import (owner, 2026-11-02); the standard categories are
 * seeded by `CertificationCategorySeeder`: the list is a handful of rows,
 * entered by hand.
 */
class CertificationCategoryController extends Controller
{
    public function __construct(protected CertificationCategoryService $categories) {}

    public function index(CertificationCategoryIndexRequest $request): Response
    {
        $filters = $request->filters();

        $categories = CertificationCategory::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (CertificationCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
                'status' => $category->status->value,
                'created_at' => $category->created_at?->toDateString(),
                'deleted_at' => $category->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/settings/certification-categories/index', [
            'certificationCategories' => $categories,
            'statuses' => RecordStatus::options(),
            'sortable' => CertificationCategory::SORTABLE,
            'filterable' => CertificationCategory::FILTERABLE,
            'perPageOptions' => CertificationCategoryIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => CertificationCategory::query()->count(),
                'trashed' => CertificationCategory::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(CertificationCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->certificationCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification category created.')]);

        return back();
    }

    public function update(CertificationCategoryRequest $request, CertificationCategory $certificationCategory): RedirectResponse
    {
        $this->categories->update($certificationCategory, $request->certificationCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification category updated.')]);

        return back();
    }

    public function destroy(CertificationCategory $certificationCategory): RedirectResponse
    {
        $blocker = $this->categories->deletionBlocker($certificationCategory);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->categories->delete($certificationCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification category moved to Deleted.')]);

        return back();
    }

    public function restore(CertificationCategory $certificationCategory): RedirectResponse
    {
        $this->categories->restore($certificationCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification category restored.')]);

        return back();
    }

    public function forceDelete(CertificationCategory $certificationCategory): RedirectResponse
    {
        $blocker = $this->categories->deletionBlocker($certificationCategory);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->categories->forceDelete($certificationCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification category permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked categories (Active tab).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $result = $this->categories->bulkDelete(CertificationCategory::query()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count certification category moved to Deleted.|:count certification categories moved to Deleted.');
    }

    /**
     * Permanently delete the ticked categories (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $result = $this->categories->bulkForceDelete(CertificationCategory::onlyTrashed()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count certification category permanently deleted.|:count certification categories permanently deleted.');
    }

    /**
     * `success` when every ticked category went, `warning` naming each one a
     * guard kept, as `Hrm\DesignationController::bulkToast()`.
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
