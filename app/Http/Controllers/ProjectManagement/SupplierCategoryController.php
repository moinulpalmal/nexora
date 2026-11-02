<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\SupplierCategoryIndexRequest;
use App\Http\Requests\ProjectManagement\SupplierCategoryRequest;
use App\Models\ProjectManagement\SupplierCategory;
use App\Services\ProjectManagement\SupplierCategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Supplier Categories: a group-wide list (ARCHITECTURE.md §9.10),
 * shaped as {@see TenderTypeController} with the deletion guard of
 * `Hrm\DesignationController` — suppliers point here. The page works whether
 * or not a company is selected.
 *
 * No spreadsheet import (owner, 2026-11-02): the list is a handful of rows,
 * entered by hand.
 */
class SupplierCategoryController extends Controller
{
    public function __construct(protected SupplierCategoryService $categories) {}

    public function index(SupplierCategoryIndexRequest $request): Response
    {
        $filters = $request->filters();

        $categories = SupplierCategory::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (SupplierCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
                'status' => $category->status->value,
                'created_at' => $category->created_at?->toDateString(),
                'deleted_at' => $category->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/supplier-categories/index', [
            'supplierCategories' => $categories,
            'statuses' => RecordStatus::options(),
            'sortable' => SupplierCategory::SORTABLE,
            'filterable' => SupplierCategory::FILTERABLE,
            'perPageOptions' => SupplierCategoryIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => SupplierCategory::query()->count(),
                'trashed' => SupplierCategory::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(SupplierCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->supplierCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier category created.')]);

        return back();
    }

    public function update(SupplierCategoryRequest $request, SupplierCategory $supplierCategory): RedirectResponse
    {
        $this->categories->update($supplierCategory, $request->supplierCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier category updated.')]);

        return back();
    }

    public function destroy(SupplierCategory $supplierCategory): RedirectResponse
    {
        $blocker = $this->categories->deletionBlocker($supplierCategory);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->categories->delete($supplierCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier category moved to Deleted.')]);

        return back();
    }

    public function restore(SupplierCategory $supplierCategory): RedirectResponse
    {
        $this->categories->restore($supplierCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier category restored.')]);

        return back();
    }

    public function forceDelete(SupplierCategory $supplierCategory): RedirectResponse
    {
        $blocker = $this->categories->deletionBlocker($supplierCategory);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->categories->forceDelete($supplierCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier category permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked categories (Active tab).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $result = $this->categories->bulkDelete(SupplierCategory::query()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count supplier category moved to Deleted.|:count supplier categories moved to Deleted.');
    }

    /**
     * Permanently delete the ticked categories (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $result = $this->categories->bulkForceDelete(SupplierCategory::onlyTrashed()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count supplier category permanently deleted.|:count supplier categories permanently deleted.');
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
