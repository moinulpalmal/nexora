<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\Unit;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a unit of measure, as `TenderTypeService`. Units are
 * group-wide: no company is stamped.
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing
 * references a unit until a quantity line carries a `unit_id`, and a blocker
 * that can only return `null` would read as a guard while guarding nothing.
 * That column adds it, with the controller's refusal and the bulk methods'
 * per-row check alongside — as `SupplierCategoryService` has.
 */
class UnitService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  array{code: string, name: string, symbol: string|null, decimal_places: int, description: string|null, status: string}  $attributes
     */
    public function create(array $attributes): Unit
    {
        return Unit::create($attributes);
    }

    /**
     * @param  array{code?: string, name?: string, symbol?: string|null, decimal_places?: int, description?: string|null, status?: string}  $attributes
     */
    public function update(Unit $unit, array $attributes): void
    {
        $unit->update($attributes);
    }

    /**
     * Soft delete: the unit moves to Deleted and keeps its code and name
     * reserved.
     */
    public function delete(Unit $unit): void
    {
        $unit->delete();
    }

    public function restore(Unit $unit): void
    {
        $unit->restore();
    }

    /**
     * Delete for good, recording what the unit was first.
     */
    public function forceDelete(Unit $unit): void
    {
        $this->audits->record($unit, AuditEvent::ForceDeleted, [
            'code' => $unit->code,
            'name' => $unit->name,
        ]);

        $unit->forceDelete();
    }

    /**
     * Soft delete each unit, one at a time so each is audited.
     *
     * @param  iterable<Unit>  $units
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $units): int
    {
        return $this->each($units, $this->delete(...));
    }

    /**
     * Delete each soft-deleted unit for good, recording each.
     *
     * @param  iterable<Unit>  $units
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $units): int
    {
        return $this->each($units, $this->forceDelete(...));
    }

    /**
     * @param  iterable<Unit>  $units
     * @param  callable(Unit): void  $write
     */
    private function each(iterable $units, callable $write): int
    {
        $done = 0;

        foreach ($units as $unit) {
            $write($unit);
            $done++;
        }

        return $done;
    }
}
