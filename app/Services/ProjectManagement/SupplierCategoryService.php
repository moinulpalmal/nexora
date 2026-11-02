<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\Supplier;
use App\Models\ProjectManagement\SupplierCategory;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a supplier category, as `Hrm\DesignationService`. Supplier
 * categories are group-wide: no company is stamped.
 *
 * {@see self::deletionBlocker()} refuses while suppliers hold the category
 * (ARCHITECTURE.md §9.14), for one row and per row in bulk. Deactivating a
 * category in use is allowed (owner, 2026-11-02): its suppliers keep it, and
 * it is no longer offered to new ones.
 */
class SupplierCategoryService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  array{code: string, name: string, description: string|null, status: string}  $attributes
     */
    public function create(array $attributes): SupplierCategory
    {
        return SupplierCategory::create($attributes);
    }

    /**
     * @param  array{code?: string, name?: string, description?: string|null, status?: string}  $attributes
     */
    public function update(SupplierCategory $category, array $attributes): void
    {
        $category->update($attributes);
    }

    /**
     * Soft delete: the category moves to Deleted and keeps its code and name
     * reserved.
     */
    public function delete(SupplierCategory $category): void
    {
        $category->delete();
    }

    public function restore(SupplierCategory $category): void
    {
        $category->restore();
    }

    /**
     * Delete for good, recording what the category was first.
     */
    public function forceDelete(SupplierCategory $category): void
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
     * @param  iterable<SupplierCategory>  $categories
     * @return array{done: int, refused: list<string>}
     */
    public function bulkDelete(iterable $categories): array
    {
        return $this->eachUnblocked($categories, $this->delete(...));
    }

    /**
     * Delete each soft-deleted category for good, recording each.
     *
     * @param  iterable<SupplierCategory>  $categories
     * @return array{done: int, refused: list<string>}
     */
    public function bulkForceDelete(iterable $categories): array
    {
        return $this->eachUnblocked($categories, $this->forceDelete(...));
    }

    /**
     * Refused while any supplier, in any company, holds the category.
     *
     * Counted with every scope removed and deleted suppliers included
     * (§9.14): a category is shared by the whole group, and a deleted
     * supplier still points here — the foreign key would refuse the permanent
     * delete either way.
     */
    public function deletionBlocker(SupplierCategory $category): ?string
    {
        $suppliers = Supplier::withoutGlobalScopes()->where('supplier_category_id', $category->id)->count();

        if ($suppliers > 0) {
            return trans_choice(
                '{1} Used by one supplier. Move it to another category, or deactivate this one instead.'
                .'|[2,*] Used by :count suppliers. Move them to another category, or deactivate this one instead.',
                $suppliers,
                ['count' => $suppliers],
            );
        }

        return null;
    }

    /**
     * @param  iterable<SupplierCategory>  $categories
     * @param  callable(SupplierCategory): void  $write
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
