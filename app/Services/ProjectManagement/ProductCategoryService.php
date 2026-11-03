<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\ProductCategory;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a product category, as `ClientService`, plus the starter set
 * every company begins with (owner, 2026-11-03).
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): the owner
 * has not yet named what will point at a product category, and a blocker that
 * can only return `null` would read as a guard while guarding nothing. The
 * first table with a `product_category_id` adds it, with the controller's
 * refusal and the bulk methods' per-row check alongside.
 */
class ProductCategoryService
{
    /**
     * The starter set: `[code, name]`. Industry-neutral on purpose (§1) —
     * each company renames, deactivates or deletes what it does not use.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const array STARTER = [
        ['RAW', 'Raw Materials'],
        ['CONS', 'Consumables'],
        ['SPARE', 'Spare Parts'],
        ['EQUIP', 'Equipment & Machinery'],
        ['TOOL', 'Tools'],
        ['ELEC', 'Electrical'],
        ['IT', 'IT & Electronics'],
        ['FURN', 'Furniture & Fixtures'],
        ['STAT', 'Stationery & Office Supplies'],
        ['PACK', 'Packaging'],
        ['FIN', 'Finished Goods'],
        ['SERV', 'Services'],
        ['OTHER', 'Other'],
    ];

    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Create a category in the given company — the caller's selected company,
     * never a value the browser sent (§9.10).
     *
     * @param  array{code: string, name: string, description: string|null, status: string}  $attributes
     */
    public function create(int $companyId, array $attributes): ProductCategory
    {
        $category = new ProductCategory($attributes);
        $category->company_id = $companyId;
        $category->save();

        return $category;
    }

    /**
     * Add every starter category whose code and name are still free in the
     * company — compared ignoring case, deleted rows included — so it is safe
     * to run twice and never overwrites an edit. Called for each new company
     * by `CompanyService::create()` and for existing ones by
     * `ProductCategorySeeder`. Each row is an ordinary create, so it is
     * audited when a person caused it (§9.3).
     *
     * @return int how many were added
     */
    public function addStarterSet(Company $company): int
    {
        $taken = ProductCategory::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get(['code', 'name']);

        $codes = $taken->mapWithKeys(fn (ProductCategory $category): array => [mb_strtoupper($category->code) => true])->all();
        $names = $taken->mapWithKeys(fn (ProductCategory $category): array => [mb_strtolower($category->name) => true])->all();

        $added = 0;

        foreach (self::STARTER as [$code, $name]) {
            if (isset($codes[$code]) || isset($names[mb_strtolower($name)])) {
                continue;
            }

            $this->create($company->id, [
                'code' => $code,
                'name' => $name,
                'description' => null,
                'status' => RecordStatus::Active->value,
            ]);
            $added++;
        }

        return $added;
    }

    /**
     * @param  array{code?: string, name?: string, description?: string|null, status?: string}  $attributes
     */
    public function update(ProductCategory $category, array $attributes): void
    {
        $category->update($attributes);
    }

    /**
     * Soft delete: the category moves to Deleted and keeps its code and name
     * reserved in its company.
     */
    public function delete(ProductCategory $category): void
    {
        $category->delete();
    }

    public function restore(ProductCategory $category): void
    {
        $category->restore();
    }

    /**
     * Delete for good, recording what the category was first.
     */
    public function forceDelete(ProductCategory $category): void
    {
        $this->audits->record($category, AuditEvent::ForceDeleted, [
            'code' => $category->code,
            'name' => $category->name,
            'company_id' => $category->company_id,
        ]);

        $category->forceDelete();
    }

    /**
     * Soft delete each category, one at a time so each is audited.
     *
     * @param  iterable<ProductCategory>  $categories
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $categories): int
    {
        return $this->each($categories, $this->delete(...));
    }

    /**
     * Delete each soft-deleted category for good, recording each.
     *
     * @param  iterable<ProductCategory>  $categories
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $categories): int
    {
        return $this->each($categories, $this->forceDelete(...));
    }

    /**
     * @param  iterable<ProductCategory>  $categories
     * @param  callable(ProductCategory): void  $write
     */
    private function each(iterable $categories, callable $write): int
    {
        $done = 0;

        foreach ($categories as $category) {
            $write($category);
            $done++;
        }

        return $done;
    }
}
