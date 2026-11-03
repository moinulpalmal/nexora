<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\VerificationItem;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a verification item, as `ClientService`.
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing
 * references a verification item until the owner names what will use it, and
 * a blocker that can only return `null` would read as a guard while guarding
 * nothing. The first table with a `verification_item_id` adds it, with the
 * controller's refusal and the bulk methods' per-row check alongside.
 */
class VerificationItemService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Create an item in the given company — the caller's selected company,
     * never a value the browser sent (§9.10).
     *
     * @param  array{name: string, description: string|null, status: string}  $attributes
     */
    public function create(int $companyId, array $attributes): VerificationItem
    {
        $item = new VerificationItem($attributes);
        $item->company_id = $companyId;
        $item->save();

        return $item;
    }

    /**
     * @param  array{name?: string, description?: string|null, status?: string}  $attributes
     */
    public function update(VerificationItem $item, array $attributes): void
    {
        $item->update($attributes);
    }

    /**
     * Soft delete: the item moves to Deleted and keeps its name reserved in its
     * company.
     */
    public function delete(VerificationItem $item): void
    {
        $item->delete();
    }

    public function restore(VerificationItem $item): void
    {
        $item->restore();
    }

    /**
     * Delete for good, recording what the item was first.
     */
    public function forceDelete(VerificationItem $item): void
    {
        $this->audits->record($item, AuditEvent::ForceDeleted, [
            'name' => $item->name,
            'company_id' => $item->company_id,
        ]);

        $item->forceDelete();
    }

    /**
     * Soft delete each item, one at a time so each is audited.
     *
     * @param  iterable<VerificationItem>  $items
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $items): int
    {
        return $this->each($items, $this->delete(...));
    }

    /**
     * Delete each soft-deleted item for good, recording each.
     *
     * @param  iterable<VerificationItem>  $items
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $items): int
    {
        return $this->each($items, $this->forceDelete(...));
    }

    /**
     * @param  iterable<VerificationItem>  $items
     * @param  callable(VerificationItem): void  $write
     */
    private function each(iterable $items, callable $write): int
    {
        $done = 0;

        foreach ($items as $item) {
            $write($item);
            $done++;
        }

        return $done;
    }
}
