<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\Product;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a product, as {@see SupplierService}.
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing
 * references a product, and a blocker that can only return `null` would read
 * as a guard while guarding nothing. The first table with a `product_id` adds
 * it, with the controller's refusal and the bulk methods' per-row check
 * alongside.
 */
class ProductService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Create a product in the given company — the caller's selected company,
     * never a value the browser sent (§9.10).
     *
     * @param  array{product_category_id: int, code: string, name: string, description: string|null, status: string}  $attributes
     */
    public function create(int $companyId, array $attributes): Product
    {
        $product = new Product($attributes);
        $product->company_id = $companyId;
        $product->save();

        return $product;
    }

    /**
     * @param  array{product_category_id?: int, code?: string, name?: string, description?: string|null, status?: string}  $attributes
     */
    public function update(Product $product, array $attributes): void
    {
        $product->update($attributes);
    }

    /**
     * Soft delete: the product moves to Deleted and keeps its code and name
     * reserved in its company.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): void
    {
        $product->restore();
    }

    /**
     * Delete for good, recording what the product was first.
     */
    public function forceDelete(Product $product): void
    {
        $this->audits->record($product, AuditEvent::ForceDeleted, [
            'code' => $product->code,
            'name' => $product->name,
            'company_id' => $product->company_id,
        ]);

        $product->forceDelete();
    }

    /**
     * Soft delete each product, one at a time so each is audited.
     *
     * @param  iterable<Product>  $products
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $products): int
    {
        return $this->each($products, $this->delete(...));
    }

    /**
     * Delete each soft-deleted product for good, recording each.
     *
     * @param  iterable<Product>  $products
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $products): int
    {
        return $this->each($products, $this->forceDelete(...));
    }

    /**
     * @param  iterable<Product>  $products
     * @param  callable(Product): void  $write
     */
    private function each(iterable $products, callable $write): int
    {
        $done = 0;

        foreach ($products as $product) {
            $write($product);
            $done++;
        }

        return $done;
    }
}
