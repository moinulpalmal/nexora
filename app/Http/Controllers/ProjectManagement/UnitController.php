<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\UnitIndexRequest;
use App\Http\Requests\ProjectManagement\UnitRequest;
use App\Models\ProjectManagement\Unit;
use App\Services\ProjectManagement\UnitService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Settings → Units of Measure: a group-wide list (ARCHITECTURE.md §9.10),
 * shaped as `TenderTypeController` — the page works whether or not a company
 * is selected. Every write arrives from a dialog on it and answers `back()`
 * with a toast.
 *
 * No spreadsheet import (owner, 2026-11-02): the list is a few dozen rows,
 * seeded by `UnitSeeder` and added to by hand.
 */
class UnitController extends Controller
{
    public function __construct(protected UnitService $units) {}

    public function index(UnitIndexRequest $request): Response
    {
        $filters = $request->filters();

        $units = Unit::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Unit $unit): array => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'decimal_places' => $unit->decimal_places,
                'description' => $unit->description,
                'status' => $unit->status->value,
                'created_at' => $unit->created_at?->toDateString(),
                'deleted_at' => $unit->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/settings/units/index', [
            'units' => $units,
            'statuses' => RecordStatus::options(),
            'sortable' => Unit::SORTABLE,
            'filterable' => Unit::FILTERABLE,
            'perPageOptions' => UnitIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Unit::query()->count(),
                'trashed' => Unit::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $this->units->create($request->unitAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit created.')]);

        return back();
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->units->update($unit, $request->unitAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit updated.')]);

        return back();
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->units->delete($unit);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit moved to Deleted.')]);

        return back();
    }

    public function restore(Unit $unit): RedirectResponse
    {
        $this->units->restore($unit);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit restored.')]);

        return back();
    }

    public function forceDelete(Unit $unit): RedirectResponse
    {
        $this->units->forceDelete($unit);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked units (Active tab).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->units->bulkDelete(Unit::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count unit moved to Deleted.|:count units moved to Deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Permanently delete the ticked units (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->units->bulkForceDelete(Unit::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count unit permanently deleted.|:count units permanently deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }
}
