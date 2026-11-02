<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\Certification;
use App\Models\ProjectManagement\CertificationCategory;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a certification category, as `SupplierCategoryService`.
 * Certification categories are group-wide: no company is stamped.
 *
 * {@see self::deletionBlocker()} refuses while certifications hold the
 * category (ARCHITECTURE.md §9.14), for one row and per row in bulk.
 * Deactivating a category in use is allowed: its certifications keep it, and
 * it is no longer offered to new ones.
 */
class CertificationCategoryService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  array{code: string, name: string, description: string|null, status: string}  $attributes
     */
    public function create(array $attributes): CertificationCategory
    {
        return CertificationCategory::create($attributes);
    }

    /**
     * @param  array{code?: string, name?: string, description?: string|null, status?: string}  $attributes
     */
    public function update(CertificationCategory $category, array $attributes): void
    {
        $category->update($attributes);
    }

    /**
     * Soft delete: the category moves to Deleted and keeps its code and name
     * reserved.
     */
    public function delete(CertificationCategory $category): void
    {
        $category->delete();
    }

    public function restore(CertificationCategory $category): void
    {
        $category->restore();
    }

    /**
     * Delete for good, recording what the category was first.
     */
    public function forceDelete(CertificationCategory $category): void
    {
        $this->audits->record($category, AuditEvent::ForceDeleted, [
            'code' => $category->code,
            'name' => $category->name,
        ]);

        $category->forceDelete();
    }

    /**
     * Soft delete each category its guard allows, one at a time so each is
     * audited; the rest are reported, not deleted.
     *
     * @param  iterable<CertificationCategory>  $categories
     * @return array{done: int, refused: list<string>}
     */
    public function bulkDelete(iterable $categories): array
    {
        return $this->eachUnblocked($categories, $this->delete(...));
    }

    /**
     * Delete each soft-deleted category for good, recording each.
     *
     * @param  iterable<CertificationCategory>  $categories
     * @return array{done: int, refused: list<string>}
     */
    public function bulkForceDelete(iterable $categories): array
    {
        return $this->eachUnblocked($categories, $this->forceDelete(...));
    }

    /**
     * Refused while any certification holds the category.
     *
     * Deleted certifications are counted (§9.14): a deleted certification
     * still points here — the foreign key would refuse the permanent delete
     * either way.
     */
    public function deletionBlocker(CertificationCategory $category): ?string
    {
        $certifications = Certification::withTrashed()->where('certification_category_id', $category->id)->count();

        if ($certifications > 0) {
            return trans_choice(
                '{1} Used by one certification. Move it to another category, or deactivate this one instead.'
                .'|[2,*] Used by :count certifications. Move them to another category, or deactivate this one instead.',
                $certifications,
                ['count' => $certifications],
            );
        }

        return null;
    }

    /**
     * @param  iterable<CertificationCategory>  $categories
     * @param  callable(CertificationCategory): void  $write
     * @return array{done: int, refused: list<string>}
     */
    private function eachUnblocked(iterable $categories, callable $write): array
    {
        $done = 0;
        $refused = [];

        foreach ($categories as $category) {
            $blocker = $this->deletionBlocker($category);

            if ($blocker !== null) {
                $refused[] = "{$category->code}: {$blocker}";

                continue;
            }

            $write($category);
            $done++;
        }

        return ['done' => $done, 'refused' => $refused];
    }
}
