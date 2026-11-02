<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\ClientIndexRequest;
use App\Http\Requests\ProjectManagement\ClientRequest;
use App\Models\ProjectManagement\Client;
use App\Services\Admin\SelectedCompany;
use App\Services\ProjectManagement\ClientService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Clients: the Projects module's first real screen, laid out as
 * `Hrm\DepartmentController` — one index page with Active/Deleted tabs, every
 * write arriving from a dialog on it and answering `back()` with a toast.
 *
 * Every query here is the selected company's (ARCHITECTURE.md §9.10):
 * `Client`'s scope sees to the list and to route-model binding (another
 * company's client is a 404), and the service stamps the company on create.
 * With no company selected the page gets `company: null` and draws a panel
 * instead of the table.
 *
 * No spreadsheet import (owner, 2026-11-02): clients are entered by hand.
 */
class ClientController extends Controller
{
    public function __construct(
        protected ClientService $clients,
        protected SelectedCompany $selectedCompany,
    ) {}

    public function index(ClientIndexRequest $request): Response
    {
        $filters = $request->filters();
        $company = $this->selectedCompany->current();

        $clients = Client::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Client $client): array => [
                'id' => $client->id,
                'code' => $client->code,
                'name' => $client->name,
                'address' => $client->address,
                'description' => $client->description,
                'contact_person' => $client->contact_person,
                'phone' => $client->phone,
                'email' => $client->email,
                'status' => $client->status->value,
                'created_at' => $client->created_at?->toDateString(),
                'deleted_at' => $client->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/clients/index', [
            'clients' => $clients,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'short_code' => $company->short_code,
            ],
            'statuses' => RecordStatus::options(),
            'sortable' => Client::SORTABLE,
            'filterable' => Client::FILTERABLE,
            'perPageOptions' => ClientIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Client::query()->count(),
                'trashed' => Client::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $companyId = $this->selectedCompany->id();

        if ($companyId === null) {
            return $this->refuse(__('Select a company in the header before adding a client.'));
        }

        $this->clients->create($companyId, $request->clientAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client created.')]);

        return back();
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->clients->update($client, $request->clientAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client updated.')]);

        return back();
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->clients->delete($client);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client moved to Deleted.')]);

        return back();
    }

    public function restore(Client $client): RedirectResponse
    {
        $this->clients->restore($client);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client restored.')]);

        return back();
    }

    public function forceDelete(Client $client): RedirectResponse
    {
        $this->clients->forceDelete($client);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked clients (Active tab). The model's scope confines the
     * query to the selected company, so an id of another company's client is
     * not found and not counted (ARCHITECTURE.md §9.10).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->clients->bulkDelete(Client::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count client moved to Deleted.|:count clients moved to Deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Permanently delete the ticked clients (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->clients->bulkForceDelete(Client::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count client permanently deleted.|:count clients permanently deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    protected function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'warning', 'message' => $message]);

        return back();
    }
}
