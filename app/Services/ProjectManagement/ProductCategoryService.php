<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\Product;
use App\Models\ProjectManagement\ProductCategory;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a product category, as `ClientService`, plus the starter set
 * every company begins with (owner, 2026-11-03).
 *
 * {@see self::deletionBlocker()} refuses while products hold the category
 * (ARCHITECTURE.md §9.14), for one row and per row in bulk, as
 * `SupplierCategoryService`. Deactivating a category in use is allowed (owner,
 * 2026-11-03): its products keep it, and it is no longer offered to new ones.
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
     * Soft delete each category its guard allows, one at a time so each is
     * audited; the rest are reported, not deleted.
     *
     * @param  iterable<ProductCategory>  $categories
     * @return array{done: int, refused: list<string>}
     */
    public function bulkDelete(iterable $categories): array
    {
        return $this->eachUnblocked($categories, $this->delete(...));
    }

    /**
     * Delete each soft-deleted category for good, recording each.
     *
     * @param  iterable<ProductCategory>  $categories
     * @return array{done: int, refused: list<string>}
     */
    public function bulkForceDelete(iterable $categories): array
    {
        return $this->eachUnblocked($categories, $this->forceDelete(...));
    }

    /**
     * Refused while any product holds the category.
     *
     * Counted with every scope removed and deleted products included (§9.14):
     * whether a row is in use is a question about the database, not about what
     * the actor can see, and a deleted product still points here — the foreign
     * key would refuse the permanent delete either way.
     */
    public function deletionBlocker(ProductCategory $category): ?string
    {
        $products = Product::withoutGlobalScopes()->where('product_category_id', $category->id)->count();

        if ($products > 0) {
            return trans_choice(
                '{1} Used by one product. Move it to another category, or deactivate this one instead.'
                .'|[2,*] Used by :count products. Move them to another category, or deactivate this one instead.',
                $products,
                ['count' => $products],
            );
        }

        return null;
    }

    /**
     * @param  iterable<ProductCategory>  $categories
     * @param  callable(ProductCategory): void  $write
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
