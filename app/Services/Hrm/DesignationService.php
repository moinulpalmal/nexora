<?php

namespace App\Services\Hrm;

use App\Enums\Admin\AuditEvent;
use App\Models\Hrm\Designation;
use App\Models\Hrm\Employee;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a designation — from the form and, row by row, from the
 * spreadsheet import ({@see DesignationImportBlueprint}), so both are audited
 * the same way. Designations are group-wide: no company is stamped.
 *
 * {@see self::deletionBlocker()} refuses while employees hold the designation
 * (ARCHITECTURE.md §9.14), for one row and per row in bulk, as
 * `Admin\CompanyService`.
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
     * Soft delete each designation its guard allows, one at a time so each is
     * audited; the rest are reported, not deleted.
     *
     * @param  iterable<Designation>  $designations
     * @return array{done: int, refused: list<string>}
     */
    public function bulkDelete(iterable $designations): array
    {
        return $this->eachUnblocked($designations, $this->delete(...));
    }

    /**
     * Delete each soft-deleted designation for good, recording each.
     *
     * @param  iterable<Designation>  $designations
     * @return array{done: int, refused: list<string>}
     */
    public function bulkForceDelete(iterable $designations): array
    {
        return $this->eachUnblocked($designations, $this->forceDelete(...));
    }

    /**
     * Refused while any employee, in any company, holds the designation.
     *
     * Counted with every scope removed and deleted employees included
     * (§9.14): a designation is shared by the whole group, and a deleted
     * employee still points here — the foreign key would refuse the permanent
     * delete either way.
     */
    public function deletionBlocker(Designation $designation): ?string
    {
        $employees = Employee::withoutGlobalScopes()->where('designation_id', $designation->id)->count();

        if ($employees > 0) {
            return trans_choice(
                '{1} Used by one employee. Move them to another designation, or deactivate this one instead.'
                .'|[2,*] Used by :count employees. Move them to another designation, or deactivate this one instead.',
                $employees,
                ['count' => $employees],
            );
        }

        return null;
    }

    /**
     * @param  iterable<Designation>  $designations
     * @param  callable(Designation): void  $write
     * @return array{done: int, refused: list<string>}
     */
    private function eachUnblocked(iterable $designations, callable $write): array
    {
        $done = 0;
        $refused = [];

        foreach ($designations as $designation) {
            $blocker = $this->deletionBlocker($designation);

            if ($blocker !== null) {
                $refused[] = "{$designation->code}: {$blocker}";

                continue;
            }

            $write($designation);
            $done++;
        }

        return ['done' => $done, 'refused' => $refused];
    }
}
