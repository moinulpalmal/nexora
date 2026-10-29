<?php

namespace App\Services\Hrm;

use App\Enums\Admin\AuditEvent;
use App\Models\Hrm\Designation;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a designation — from the form and, row by row, from the
 * spreadsheet import ({@see DesignationImportBlueprint}), so both are audited
 * the same way. Designations are group-wide: no company is stamped.
 *
 * **No `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing references a
 * designation. A blocker that could only return `null` would read as a guard
 * while guarding nothing; Employees will bring the first clause.
 */
class DesignationService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  array{code: string, name: string, status: string}  $attributes
     */
    public function create(array $attributes): Designation
    {
        return Designation::create($attributes);
    }

    /**
     * @param  array{code?: string, name?: string, status?: string}  $attributes
     */
    public function update(Designation $designation, array $attributes): void
    {
        $designation->update($attributes);
    }

    /**
     * Soft delete: the designation moves to Deleted and keeps its code and
     * name reserved.
     */
    public function delete(Designation $designation): void
    {
        $designation->delete();
    }

    public function restore(Designation $designation): void
    {
        $designation->restore();
    }

    /**
     * Delete for good, recording what the designation was first.
     */
    public function forceDelete(Designation $designation): void
    {
        $this->audits->record($designation, AuditEvent::ForceDeleted, [
            'code' => $designation->code,
            'name' => $designation->name,
        ]);

        $designation->forceDelete();
    }

    /**
     * Soft delete each designation, one at a time so each is audited.
     *
     * @param  iterable<Designation>  $designations
     */
    public function bulkDelete(iterable $designations): int
    {
        $count = 0;

        foreach ($designations as $designation) {
            $this->delete($designation);
            $count++;
        }

        return $count;
    }

    /**
     * Delete each soft-deleted designation for good, recording each.
     *
     * @param  iterable<Designation>  $designations
     */
    public function bulkForceDelete(iterable $designations): int
    {
        $count = 0;

        foreach ($designations as $designation) {
            $this->forceDelete($designation);
            $count++;
        }

        return $count;
    }
}
