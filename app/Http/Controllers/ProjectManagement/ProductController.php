<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\ProductIndexRequest;
use App\Http\Requests\ProjectManagement\ProductRequest;
use App\Models\ProjectManagement\Product;
use App\Models\ProjectManagement\ProductCategory;
use App\Services\Admin\SelectedCompany;
use App\Services\ProjectManagement\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Settings → Products, laid out as {@see SupplierController} — one index page
 * with Active/Deleted tabs, every write arriving from a dialog on it and
 * answering `back()` with a toast.
 *
 * Every query here is the selected company's (ARCHITECTURE.md §9.10), the
 * categories offered included: unlike suppliers', product categories are
 * kept per company. With no company selected the page gets `company: null`
 * and draws a panel instead of the table.
 *
 * No spreadsheet import (owner, 2026-11-03): products are entered by hand.
 */
class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected SelectedCompany $selectedCompany,
    ) {}

    public function index(ProductIndexRequest $request): Response
    {
        $filters = $request->filters();
        $company = $this->selectedCompany->current();

        $products = Product::query()
            ->with('category:id,code,name')
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'product_category_id' => $product->product_category_id,
                'category_name' => $product->category->name,
                'code' => $product->code,
                'name' => $product->name,
                'description' => $product->description,
                'status' => $product->status->value,
                'created_at' => $product->created_at?->toDateString(),
                'deleted_at' => $product->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/settings/products/index', [
            'products' => $products,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
            ],
            'statuses' => RecordStatus::options(),
            'categories' => $company === null ? [] : $this->categoryOptions(),
            'sortable' => Product::SORTABLE,
            'filterable' => Product::FILTERABLE,
            'perPageOptions' => ProductIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Product::query()->count(),
                'trashed' => Product::onlyTrashed()->count(),
            ],
        ]);
    }

    /**
     * With no company selected the request already fails — no category belongs
     * to "no company" — so the refusal below is the guard behind it, not the
     * message a person normally sees.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $companyId = $this->selectedCompany->id();

        if ($companyId === null) {
            return $this->refuse(__('Select a company in the header before adding a product.'));
        }

        $this->products->create($companyId, $request->productAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return back();
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->productAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product moved to Deleted.')]);

        return back();
    }

    public function restore(Product $product): RedirectResponse
    {
        $this->products->restore($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product restored.')]);

        return back();
    }

    public function forceDelete(Product $product): RedirectResponse
    {
        $this->products->forceDelete($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked products (Active tab). The model's scope confines the
     * query to the selected company, so an id of another company's product is
     * not found and not counted (ARCHITECTURE.md §9.10).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->products->bulkDelete(Product::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count product moved to Deleted.|:count products moved to Deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Permanently delete the ticked products (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->products->bulkForceDelete(Product::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count product permanently deleted.|:count products permanently deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Every live category of the selected company, inactive ones marked: the
     * form offers the active ones plus the product's own, the list filter all
     * of them.
     *
     * @return list<array{value: int, label: string, hint: string, status: string}>
     */
    private function categoryOptions(): array
    {
        return array_values(ProductCategory::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->map(fn (ProductCategory $category): array => [
                'value' => $category->id,
                'label' => $category->name,
                'hint' => $category->code,
                'status' => $category->status->value,
            ])
            ->all());
    }

    protected function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'warning', 'message' => $message]);

        return back();
    }
}
