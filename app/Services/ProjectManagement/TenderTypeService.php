<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\TenderType;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a tender type, as `Hrm\DesignationService`. Tender types are
 * group-wide: no company is stamped.
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing
 * references a tender type until projects carry a `tender_type_id`, and a
 * blocker that can only return `null` would read as a guard while guarding
 * nothing. That column adds it, with the controller's refusal and the bulk
 * methods' per-row check alongside — as `ClientService` will.
 */
class TenderTypeService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  array{code: string, name: string, status: string}  $attributes
     */
    public function create(array $attributes): TenderType
    {
        return TenderType::create($attributes);
    }

    /**
     * @param  array{code?: string, name?: string, status?: string}  $attributes
     */
    public function update(TenderType $tenderType, array $attributes): void
    {
        $tenderType->update($attributes);
    }

    /**
     * Soft delete: the tender type moves to Deleted and keeps its code and
     * name reserved.
     */
    public function delete(TenderType $tenderType): void
    {
        $tenderType->delete();
    }

    public function restore(TenderType $tenderType): void
    {
        $tenderType->restore();
    }

    /**
     * Delete for good, recording what the tender type was first.
     */
    public function forceDelete(TenderType $tenderType): void
    {
        $this->audits->record($tenderType, AuditEvent::ForceDeleted, [
            'code' => $tenderType->code,
            'name' => $tenderType->name,
        ]);

        $tenderType->forceDelete();
    }

    /**
     * Soft delete each tender type, one at a time so each is audited.
     *
     * @param  iterable<TenderType>  $tenderTypes
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $tenderTypes): int
    {
        return $this->each($tenderTypes, $this->delete(...));
    }

    /**
     * Delete each soft-deleted tender type for good, recording each.
     *
     * @param  iterable<TenderType>  $tenderTypes
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $tenderTypes): int
    {
        return $this->each($tenderTypes, $this->forceDelete(...));
    }

    /**
     * @param  iterable<TenderType>  $tenderTypes
     * @param  callable(TenderType): void  $write
     */
    private function each(iterable $tenderTypes, callable $write): int
    {
        $done = 0;

        foreach ($tenderTypes as $tenderType) {
            $write($tenderType);
            $done++;
        }

        return $done;
    }
}
