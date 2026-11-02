<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\Certification;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a certification, as `TenderTypeService`. Certifications are
 * group-wide: no company is stamped.
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing
 * references a certification until the screens that use one — suppliers'
 * certificates, tender requirements; the owner will name them — carry a
 * `certification_id`, and a blocker that can only return `null` would read as
 * a guard while guarding nothing. That column adds it, with the controller's
 * refusal and the bulk methods' per-row check alongside, as
 * `SupplierCategoryService`.
 */
class CertificationService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  array{certification_category_id: int, code: string, name: string, issuing_body: string|null, version: string|null, description: string|null, status: string}  $attributes
     */
    public function create(array $attributes): Certification
    {
        return Certification::create($attributes);
    }

    /**
     * @param  array{certification_category_id?: int, code?: string, name?: string, issuing_body?: string|null, version?: string|null, description?: string|null, status?: string}  $attributes
     */
    public function update(Certification $certification, array $attributes): void
    {
        $certification->update($attributes);
    }

    /**
     * Soft delete: the certification moves to Deleted and keeps its code, and
     * its name within its version, reserved.
     */
    public function delete(Certification $certification): void
    {
        $certification->delete();
    }

    public function restore(Certification $certification): void
    {
        $certification->restore();
    }

    /**
     * Delete for good, recording what the certification was first.
     */
    public function forceDelete(Certification $certification): void
    {
        $this->audits->record($certification, AuditEvent::ForceDeleted, [
            'code' => $certification->code,
            'name' => $certification->name,
            'version' => $certification->version,
        ]);

        $certification->forceDelete();
    }

    /**
     * Soft delete each certification, one at a time so each is audited.
     *
     * @param  iterable<Certification>  $certifications
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $certifications): int
    {
        return $this->each($certifications, $this->delete(...));
    }

    /**
     * Delete each soft-deleted certification for good, recording each.
     *
     * @param  iterable<Certification>  $certifications
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $certifications): int
    {
        return $this->each($certifications, $this->forceDelete(...));
    }

    /**
     * @param  iterable<Certification>  $certifications
     * @param  callable(Certification): void  $write
     */
    private function each(iterable $certifications, callable $write): int
    {
        $done = 0;

        foreach ($certifications as $certification) {
            $write($certification);
            $done++;
        }

        return $done;
    }
}
