<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\SupplierIndexRequest;
use App\Http\Requests\ProjectManagement\SupplierRequest;
use App\Models\ProjectManagement\Supplier;
use App\Models\ProjectManagement\SupplierCategory;
use App\Services\Admin\SelectedCompany;
use App\Services\ProjectManagement\SupplierService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Suppliers: the ERP's one supplier list, laid out as
 * {@see ClientController} — one index page with Active/Deleted tabs, every
 * write arriving from a dialog on it and answering `back()` with a toast.
 *
 * Every query here is the selected company's (ARCHITECTURE.md §9.10); the
 * categories offered are group-wide, as `Hrm\EmployeeController`'s
 * designations. With no company selected the page gets `company: null` and
 * draws a panel instead of the table.
 *
 * No spreadsheet import (owner, 2026-11-02): suppliers are entered by hand.
 */
class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $suppliers,
        protected SelectedCompany $selectedCompany,
    ) {}

    public function index(SupplierIndexRequest $request): Response
    {
        $filters = $request->filters();
        $company = $this->selectedCompany->current();

        $suppliers = Supplier::query()
            ->with('category:id,code,name')
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'supplier_category_id' => $supplier->supplier_category_id,
                'category_name' => $supplier->category->name,
                'code' => $supplier->code,
                'name' => $supplier->name,
                'address' => $supplier->address,
                'description' => $supplier->description,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'tin' => $supplier->tin,
                'bin' => $supplier->bin,
                'trade_license_no' => $supplier->trade_license_no,
                'bank_name' => $supplier->bank_name,
                'bank_branch' => $supplier->bank_branch,
                'bank_account_name' => $supplier->bank_account_name,
                'bank_account_number' => $supplier->bank_account_number,
                'bank_routing_number' => $supplier->bank_routing_number,
                'status' => $supplier->status->value,
                'created_at' => $supplier->created_at?->toDateString(),
                'deleted_at' => $supplier->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/suppliers/index', [
            'suppliers' => $suppliers,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
            ],
            'statuses' => RecordStatus::options(),
            'categories' => $company === null ? [] : $this->categoryOptions(),
            'sortable' => Supplier::SORTABLE,
            'filterable' => Supplier::FILTERABLE,
            'perPageOptions' => SupplierIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Supplier::query()->count(),
                'trashed' => Supplier::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $companyId = $this->selectedCompany->id();

        if ($companyId === null) {
            return $this->refuse(__('Select a company in the header before adding a supplier.'));
        }

        $this->suppliers->create($companyId, $request->supplierAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier created.')]);

        return back();
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->suppliers->update($supplier, $request->supplierAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier updated.')]);

        return back();
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->suppliers->delete($supplier);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier moved to Deleted.')]);

        return back();
    }

    public function restore(Supplier $supplier): RedirectResponse
    {
        $this->suppliers->restore($supplier);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier restored.')]);

        return back();
    }

    public function forceDelete(Supplier $supplier): RedirectResponse
    {
        $this->suppliers->forceDelete($supplier);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked suppliers (Active tab). The model's scope confines the
     * query to the selected company, so an id of another company's supplier is
     * not found and not counted (ARCHITECTURE.md §9.10).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->suppliers->bulkDelete(Supplier::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count supplier moved to Deleted.|:count suppliers moved to Deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Permanently delete the ticked suppliers (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->suppliers->bulkForceDelete(Supplier::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count supplier permanently deleted.|:count suppliers permanently deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Every live category, inactive ones marked: the form offers the active
     * ones plus the supplier's own, the list filter all of them.
     *
     * @return list<array{value: int, label: string, hint: string, status: string}>
     */
    private function categoryOptions(): array
    {
        return array_values(SupplierCategory::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->map(fn (SupplierCategory $category): array => [
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
