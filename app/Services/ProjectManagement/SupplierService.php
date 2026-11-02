<?php

namespace App\Services\ProjectManagement;

use App\Enums\Admin\AuditEvent;
use App\Models\ProjectManagement\Supplier;
use App\Services\Admin\AuditRecorder;

/**
 * Every write to a supplier, as {@see ClientService}.
 *
 * **There is no `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing
 * references a supplier until Accounting's bills (or Projects' purchases) are
 * built, and a blocker that can only return `null` would read as a guard while
 * guarding nothing. The first table with a `supplier_id` adds it, with the
 * controller's refusal and the bulk methods' per-row check alongside.
 */
class SupplierService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Create a supplier in the given company — the caller's selected company,
     * never a value the browser sent (§9.10).
     *
     * @param  array{supplier_category_id: int, code: string, name: string, address: string|null, description: string|null, contact_person: string|null, phone: string|null, email: string|null, tin: string|null, bin: string|null, trade_license_no: string|null, bank_name: string|null, bank_branch: string|null, bank_account_name: string|null, bank_account_number: string|null, bank_routing_number: string|null, status: string}  $attributes
     */
    public function create(int $companyId, array $attributes): Supplier
    {
        $supplier = new Supplier($attributes);
        $supplier->company_id = $companyId;
        $supplier->save();

        return $supplier;
    }

    /**
     * @param  array<string, int|string|null>  $attributes  as {@see self::create()}
     */
    public function update(Supplier $supplier, array $attributes): void
    {
        $supplier->update($attributes);
    }

    /**
     * Soft delete: the supplier moves to Deleted and keeps its code and name
     * reserved in its company.
     */
    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    public function restore(Supplier $supplier): void
    {
        $supplier->restore();
    }

    /**
     * Delete for good, recording what the supplier was first.
     */
    public function forceDelete(Supplier $supplier): void
    {
        $this->audits->record($supplier, AuditEvent::ForceDeleted, [
            'code' => $supplier->code,
            'name' => $supplier->name,
            'company_id' => $supplier->company_id,
        ]);

        $supplier->forceDelete();
    }

    /**
     * Soft delete each supplier, one at a time so each is audited.
     *
     * @param  iterable<Supplier>  $suppliers
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $suppliers): int
    {
        return $this->each($suppliers, $this->delete(...));
    }

    /**
     * Delete each soft-deleted supplier for good, recording each.
     *
     * @param  iterable<Supplier>  $suppliers
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $suppliers): int
    {
        return $this->each($suppliers, $this->forceDelete(...));
    }

    /**
     * @param  iterable<Supplier>  $suppliers
     * @param  callable(Supplier): void  $write
     */
    private function each(iterable $suppliers, callable $write): int
    {
        $done = 0;

        foreach ($suppliers as $supplier) {
            $write($supplier);
            $done++;
        }

        return $done;
    }
}
