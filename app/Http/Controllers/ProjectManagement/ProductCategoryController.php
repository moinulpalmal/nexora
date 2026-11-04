<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\ProductCategoryIndexRequest;
use App\Http\Requests\ProjectManagement\ProductCategoryRequest;
use App\Models\ProjectManagement\ProductCategory;
use App\Services\Admin\SelectedCompany;
use App\Services\ProjectManagement\ProductCategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Settings → Product Categories, laid out as `ClientController` — one index
 * page with Active/Deleted tabs, every write arriving from a dialog on it and
 * answering `back()` with a toast.
 *
 * Every query here is the selected company's (ARCHITECTURE.md §9.10): the
 * model's scope sees to the list and to route-model binding (another company's
 * category is a 404), and the service stamps the company on create. With no
 * company selected the page gets `company: null` and draws a panel instead of
 * the table.
 *
 * No spreadsheet import (owner, 2026-11-03): every company starts with the
 * starter set, and the rest are entered by hand.
 */
class ProductCategoryController extends Controller
{
    public function __construct(
        protected ProductCategoryService $categories,
        protected SelectedCompany $selectedCompany,
    ) {}

    public function index(ProductCategoryIndexRequest $request): Response
    {
        $filters = $request->filters();
        $company = $this->selectedCompany->current();

        $categories = ProductCategory::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (ProductCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
                'status' => $category->status->value,
                'created_at' => $category->created_at?->toDateString(),
                'deleted_at' => $category->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/settings/product-categories/index', [
            'categories' => $categories,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
            ],
            'statuses' => RecordStatus::options(),
            'sortable' => ProductCategory::SORTABLE,
            'filterable' => ProductCategory::FILTERABLE,
            'perPageOptions' => ProductCategoryIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => ProductCategory::query()->count(),
                'trashed' => ProductCategory::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(ProductCategoryRequest $request): RedirectResponse
    {
        $companyId = $this->selectedCompany->id();

        if ($companyId === null) {
            return $this->refuse(__('Select a company in the header before adding a product category.'));
        }

        $this->categories->create($companyId, $request->productCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category created.')]);

        return back();
    }

    public function update(ProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $this->categories->update($productCategory, $request->productCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category updated.')]);

        return back();
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $blocker = $this->categories->deletionBlocker($productCategory);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->categories->delete($productCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category moved to Deleted.')]);

        return back();
    }

    public function restore(ProductCategory $productCategory): RedirectResponse
    {
        $this->categories->restore($productCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category restored.')]);

        return back();
    }

    public function forceDelete(ProductCategory $productCategory): RedirectResponse
    {
        $blocker = $this->categories->deletionBlocker($productCategory);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->categories->forceDelete($productCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked categories (Active tab). The model's scope confines the
     * query to the selected company, so an id of another company's category is
     * not found and not counted (ARCHITECTURE.md §9.10).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $result = $this->categories->bulkDelete(ProductCategory::query()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count product category moved to Deleted.|:count product categories moved to Deleted.');
    }

    /**
     * Permanently delete the ticked categories (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $result = $this->categories->bulkForceDelete(ProductCategory::onlyTrashed()->whereKey($request->ids())->get());

        return $this->bulkToast($result, ':count product category permanently deleted.|:count product categories permanently deleted.');
    }

    /**
     * `success` when every ticked category went, `warning` naming each one a
     * guard kept, as `SupplierCategoryController::bulkToast()`.
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
