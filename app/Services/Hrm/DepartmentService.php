<?php

namespace App\Services\Hrm;

use App\Enums\Admin\AuditEvent;
use App\Models\Hrm\Department;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a department — from the form and, row by row, from the
 * spreadsheet import ({@see DepartmentImportBlueprint}), so both are audited
 * the same way.
 *
 * **No `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing references a
 * department. A blocker that could only return `null` would read as a guard
 * while guarding nothing; Employees will bring the first clause.
 */
class DepartmentService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Create a department in the given company — the caller's selected company
     * from the form, the staged import's company from a sheet. Never a value
     * the browser sent (§9.10).
     *
     * @param  array{code: string, name: string, status: string}  $attributes
     */
    public function create(int $companyId, array $attributes): Department
    {
        $department = new Department($attributes);
        $department->company_id = $companyId;
        $department->save();

        return $department;
    }

    /**
     * @param  array{code?: string, name?: string, status?: string}  $attributes
     */
    public function update(Department $department, array $attributes): void
    {
        $department->update($attributes);
    }

    /**
     * Soft delete: the department moves to Deleted and keeps its code and name
     * reserved in its company.
     */
    public function delete(Department $department): void
    {
        $department->delete();
    }

    public function restore(Department $department): void
    {
        $department->restore();
    }

    /**
     * Delete for good, recording what the department was first.
     */
    public function forceDelete(Department $department): void
    {
        $this->audits->record($department, AuditEvent::ForceDeleted, [
            'code' => $department->code,
            'name' => $department->name,
            'company_id' => $department->company_id,
        ]);

        $department->forceDelete();
    }

    /**
     * Soft delete each department, one at a time so each is audited.
     *
     * @param  iterable<Department>  $departments
     */
    public function bulkDelete(iterable $departments): int
    {
        $count = 0;

        foreach ($departments as $department) {
            $this->delete($department);
            $count++;
        }

        return $count;
    }

    /**
     * Delete each soft-deleted department for good, recording each.
     *
     * @param  iterable<Department>  $departments
     */
    public function bulkForceDelete(iterable $departments): int
    {
        $count = 0;

        foreach ($departments as $department) {
            $this->forceDelete($department);
            $count++;
        }

        return $count;
    }
}
