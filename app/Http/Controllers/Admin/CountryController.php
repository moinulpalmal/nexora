<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Hrm\DesignationController;
use App\Http\Requests\Admin\CountryIndexRequest;
use App\Http\Requests\Admin\CountryRequest;
use App\Http\Requests\BulkRequest;
use App\Models\Admin\Country;
use App\Services\Admin\CountryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Countries: a group-wide list, shaped as
 * {@see DesignationController} without the spreadsheet import — the ISO list
 * is seeded (owner, 2026-11-02). Every write arrives from a dialog on it and
 * answers `back()` with a toast.
 */
class CountryController extends Controller
{
    public function __construct(protected CountryService $countries) {}

    public function index(CountryIndexRequest $request): Response
    {
        $filters = $request->filters();

        $countries = Country::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Country $country): array => [
                'id' => $country->id,
                'name' => $country->name,
                'iso2' => $country->iso2,
                'iso3' => $country->iso3,
                'numeric_code' => $country->numeric_code,
                'dial_code' => $country->dial_code,
                'currency_code' => $country->currency_code,
                'nationality' => $country->nationality,
                'status' => $country->status->value,
                'created_at' => $country->created_at?->toDateString(),
                'deleted_at' => $country->deleted_at?->toDateString(),
            ]);

        return Inertia::render('admin/countries/index', [
            'countries' => $countries,
            'statuses' => RecordStatus::options(),
            'sortable' => Country::SORTABLE,
            'filterable' => Country::FILTERABLE,
            'perPageOptions' => CountryIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Country::query()->count(),
                'trashed' => Country::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(CountryRequest $request): RedirectResponse
    {
        $this->countries->create($request->countryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Country created.')]);

        return back();
    }

    public function update(CountryRequest $request, Country $country): RedirectResponse
    {
        $this->countries->update($country, $request->countryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Country updated.')]);

        return back();
    }

    public function destroy(Country $country): RedirectResponse
    {
        $this->countries->delete($country);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Country moved to Deleted.')]);

        return back();
    }

    public function restore(Country $country): RedirectResponse
    {
        $this->countries->restore($country);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Country restored.')]);

        return back();
    }

    public function forceDelete(Country $country): RedirectResponse
    {
        $this->countries->forceDelete($country);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Country permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked countries (Active tab).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->countries->bulkDelete(Country::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count country moved to Deleted.|:count countries moved to Deleted.', $done, ['count' => $done])]);

        return back();
    }

    /**
     * Permanently delete the ticked countries (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->countries->bulkForceDelete(Country::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count country permanently deleted.|:count countries permanently deleted.', $done, ['count' => $done])]);

        return back();
    }
}
