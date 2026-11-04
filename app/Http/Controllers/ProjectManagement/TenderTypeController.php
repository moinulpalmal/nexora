<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\TenderTypeIndexRequest;
use App\Http\Requests\ProjectManagement\TenderTypeRequest;
use App\Models\ProjectManagement\TenderType;
use App\Services\ProjectManagement\TenderTypeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Settings → Tender Types: a group-wide list (ARCHITECTURE.md §9.10), shaped
 * as `Hrm\DesignationController` — the page works whether or not a company is
 * selected. Every write arrives from a dialog on it and answers `back()` with
 * a toast.
 *
 * No spreadsheet import (owner, 2026-11-02): the list is a handful of rows,
 * entered by hand.
 */
class TenderTypeController extends Controller
{
    public function __construct(protected TenderTypeService $tenderTypes) {}

    public function index(TenderTypeIndexRequest $request): Response
    {
        $filters = $request->filters();

        $tenderTypes = TenderType::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (TenderType $tenderType): array => [
                'id' => $tenderType->id,
                'code' => $tenderType->code,
                'name' => $tenderType->name,
                'status' => $tenderType->status->value,
                'created_at' => $tenderType->created_at?->toDateString(),
                'deleted_at' => $tenderType->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/settings/tender-types/index', [
            'tenderTypes' => $tenderTypes,
            'statuses' => RecordStatus::options(),
            'sortable' => TenderType::SORTABLE,
            'filterable' => TenderType::FILTERABLE,
            'perPageOptions' => TenderTypeIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => TenderType::query()->count(),
                'trashed' => TenderType::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(TenderTypeRequest $request): RedirectResponse
    {
        $this->tenderTypes->create($request->tenderTypeAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender type created.')]);

        return back();
    }

    public function update(TenderTypeRequest $request, TenderType $tenderType): RedirectResponse
    {
        $this->tenderTypes->update($tenderType, $request->tenderTypeAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender type updated.')]);

        return back();
    }

    public function destroy(TenderType $tenderType): RedirectResponse
    {
        $this->tenderTypes->delete($tenderType);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender type moved to Deleted.')]);

        return back();
    }

    public function restore(TenderType $tenderType): RedirectResponse
    {
        $this->tenderTypes->restore($tenderType);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender type restored.')]);

        return back();
    }

    public function forceDelete(TenderType $tenderType): RedirectResponse
    {
        $this->tenderTypes->forceDelete($tenderType);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender type permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked tender types (Active tab).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->tenderTypes->bulkDelete(TenderType::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count tender type moved to Deleted.|:count tender types moved to Deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Permanently delete the ticked tender types (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->tenderTypes->bulkForceDelete(TenderType::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count tender type permanently deleted.|:count tender types permanently deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }
}
