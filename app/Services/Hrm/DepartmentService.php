<?php

namespace App\Services\Hrm;

use App\Enums\Admin\AuditEvent;
use App\Models\Hrm\Department;
use App\Models\Hrm\Employee;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a department — from the form and, row by row, from the
 * spreadsheet import ({@see DepartmentImportBlueprint}), so both are audited
 * the same way.
 *
 * {@see self::deletionBlocker()} refuses while employees are in the
 * department (ARCHITECTURE.md §9.14), for one row and per row in bulk, as
 * `Admin\CompanyService`.
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
     * Soft delete each department its guard allows, one at a time so each is
     * audited; the rest are reported, not deleted.
     *
     * @param  iterable<Department>  $departments
     * @return array{done: int, refused: list<string>}
     */
    public function bulkDelete(iterable $departments): array
    {
        return $this->eachUnblocked($departments, $this->delete(...));
    }

    /**
     * Delete each soft-deleted department for good, recording each.
     *
     * @param  iterable<Department>  $departments
     * @return array{done: int, refused: list<string>}
     */
    public function bulkForceDelete(iterable $departments): array
    {
        return $this->eachUnblocked($departments, $this->forceDelete(...));
    }

    /**
     * Refused while any employee is in the department.
     *
     * Counted with the company scope removed and deleted employees included
     * (§9.14): whether a row is in use is a question about the database, not
     * about what the actor can see, and a deleted employee still points here.
     */
    public function deletionBlocker(Department $department): ?string
    {
        $employees = Employee::withoutGlobalScopes()->where('department_id', $department->id)->count();

        if ($employees > 0) {
            return trans_choice(
                '{1} Used by one employee. Move them to another department, or deactivate this one instead.'
                .'|[2,*] Used by :count employees. Move them to another department, or deactivate this one instead.',
                $employees,
                ['count' => $employees],
            );
        }

        return null;
    }

    /**
     * @param  iterable<Department>  $departments
     * @param  callable(Department): void  $write
     * @return array{done: int, refused: list<string>}
     */
    private function eachUnblocked(iterable $departments, callable $write): array
    {
        $done = 0;
        $refused = [];

        foreach ($departments as $department) {
            $blocker = $this->deletionBlocker($department);

            if ($blocker !== null) {
                $refused[] = "{$department->code}: {$blocker}";

                continue;
            }

            $write($department);
            $done++;
        }

        return ['done' => $done, 'refused' => $refused];
    }
}
