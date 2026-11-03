<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\VerificationItemIndexRequest;
use App\Http\Requests\ProjectManagement\VerificationItemRequest;
use App\Models\ProjectManagement\VerificationItem;
use App\Services\Admin\SelectedCompany;
use App\Services\ProjectManagement\VerificationItemService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Verification Items, laid out as `ProductCategoryController` — one
 * index page with Active/Deleted tabs, every write arriving from a dialog on it
 * and answering `back()` with a toast.
 *
 * Every query here is the selected company's (ARCHITECTURE.md §9.10): the
 * model's scope sees to the list and to route-model binding (another company's
 * item is a 404), and the service stamps the company on create. With no
 * company selected the page gets `company: null` and draws a panel instead of
 * the table.
 *
 * Nothing references an item yet, so no delete is refused, and bulk deletes
 * answer `success` as `ClientController` does. No spreadsheet import (owner,
 * 2026-11-03): items are entered by hand.
 */
class VerificationItemController extends Controller
{
    public function __construct(
        protected VerificationItemService $items,
        protected SelectedCompany $selectedCompany,
    ) {}

    public function index(VerificationItemIndexRequest $request): Response
    {
        $filters = $request->filters();
        $company = $this->selectedCompany->current();

        $items = VerificationItem::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (VerificationItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status->value,
                'created_at' => $item->created_at?->toDateString(),
                'deleted_at' => $item->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/verification-items/index', [
            'items' => $items,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
            ],
            'statuses' => RecordStatus::options(),
            'sortable' => VerificationItem::SORTABLE,
            'filterable' => VerificationItem::FILTERABLE,
            'perPageOptions' => VerificationItemIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => VerificationItem::query()->count(),
                'trashed' => VerificationItem::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(VerificationItemRequest $request): RedirectResponse
    {
        $companyId = $this->selectedCompany->id();

        if ($companyId === null) {
            return $this->refuse(__('Select a company in the header before adding a verification item.'));
        }

        $this->items->create($companyId, $request->verificationItemAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification item created.')]);

        return back();
    }

    public function update(VerificationItemRequest $request, VerificationItem $verificationItem): RedirectResponse
    {
        $this->items->update($verificationItem, $request->verificationItemAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification item updated.')]);

        return back();
    }

    public function destroy(VerificationItem $verificationItem): RedirectResponse
    {
        $this->items->delete($verificationItem);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification item moved to Deleted.')]);

        return back();
    }

    public function restore(VerificationItem $verificationItem): RedirectResponse
    {
        $this->items->restore($verificationItem);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification item restored.')]);

        return back();
    }

    public function forceDelete(VerificationItem $verificationItem): RedirectResponse
    {
        $this->items->forceDelete($verificationItem);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification item permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked items (Active tab). The model's scope confines the
     * query to the selected company, so an id of another company's item is not
     * found and not counted (ARCHITECTURE.md §9.10).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->items->bulkDelete(VerificationItem::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count verification item moved to Deleted.|:count verification items moved to Deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Permanently delete the ticked items (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->items->bulkForceDelete(VerificationItem::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count verification item permanently deleted.|:count verification items permanently deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    protected function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'warning', 'message' => $message]);

        return back();
    }
}
