<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\Client;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a client, as `Hrm\DepartmentService`.
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing
 * references a client until projects (and later Accounting's invoices) are
 * built, and a blocker that can only return `null` would read as a guard while
 * guarding nothing. The first table with a `client_id` adds it, with the
 * controller's refusal and the bulk methods' per-row check alongside.
 */
class ClientService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Create a client in the given company — the caller's selected company,
     * never a value the browser sent (§9.10).
     *
     * @param  array{code: string, name: string, address: string|null, description: string|null, contact_person: string|null, phone: string|null, email: string|null, status: string}  $attributes
     */
    public function create(int $companyId, array $attributes): Client
    {
        $client = new Client($attributes);
        $client->company_id = $companyId;
        $client->save();

        return $client;
    }

    /**
     * @param  array{code?: string, name?: string, address?: string|null, description?: string|null, contact_person?: string|null, phone?: string|null, email?: string|null, status?: string}  $attributes
     */
    public function update(Client $client, array $attributes): void
    {
        $client->update($attributes);
    }

    /**
     * Soft delete: the client moves to Deleted and keeps its code and name
     * reserved in its company.
     */
    public function delete(Client $client): void
    {
        $client->delete();
    }

    public function restore(Client $client): void
    {
        $client->restore();
    }

    /**
     * Delete for good, recording what the client was first.
     */
    public function forceDelete(Client $client): void
    {
        $this->audits->record($client, AuditEvent::ForceDeleted, [
            'code' => $client->code,
            'name' => $client->name,
            'company_id' => $client->company_id,
        ]);

        $client->forceDelete();
    }

    /**
     * Soft delete each client, one at a time so each is audited.
     *
     * @param  iterable<Client>  $clients
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $clients): int
    {
        return $this->each($clients, $this->delete(...));
    }

    /**
     * Delete each soft-deleted client for good, recording each.
     *
     * @param  iterable<Client>  $clients
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $clients): int
    {
        return $this->each($clients, $this->forceDelete(...));
    }

    /**
     * @param  iterable<Client>  $clients
     * @param  callable(Client): void  $write
     */
    private function each(iterable $clients, callable $write): int
    {
        $done = 0;

        foreach ($clients as $client) {
            $write($client);
            $done++;
        }

        return $done;
    }
}
