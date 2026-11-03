<?php

namespace App\Services\Admin;

use App\Enums\Admin\AuditEvent;
use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\Hrm\Employee;
use App\Models\ProjectManagement\Client;
use App\Models\ProjectManagement\Product;
use App\Models\ProjectManagement\ProductCategory;
use App\Models\ProjectManagement\Supplier;
use App\Models\ProjectManagement\VerificationItem;
use App\Services\ProjectManagement\ProductCategoryService;
use Illuminate\Support\Facades\DB;

/**
 * Every write to the Company master, and the guard that may refuse a delete
 * (ARCHITECTURE.md §9.10, §9.14).
 *
 * Deactivating needs no guard: an inactive company simply stops being
 * selectable, and `SelectedCompany` moves anyone working in it on. Deleting is
 * different — a company's records would point at a row nobody can see — so it
 * is refused while anything still belongs to it.
 */
class CompanyService
{
    public function __construct(
        private readonly AuditRecorder $audits,
        private readonly ProductCategoryService $productCategories,
    ) {}

    /**
     * Create the company with the Projects product-category starter set in it
     * (owner, 2026-11-03), in one transaction: a company never exists without
     * the set. Each category is an ordinary, audited create.
     *
     * @param  array{name: string, short_code: string, status: string}  $attributes
     */
    public function create(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes): Company {
            $company = Company::query()->create($attributes);

            $this->productCategories->addStarterSet($company);

            return $company;
        });
    }

    /**
     * @param  array{name: string, short_code: string, status: string}  $attributes
     */
    public function update(Company $company, array $attributes): void
    {
        $company->update($attributes);
    }

    /**
     * Soft delete: the company moves to Deleted, keeps its name and short code
     * reserved, and can be restored.
     */
    public function delete(Company $company): void
    {
        $company->delete();
    }

    public function restore(Company $company): void
    {
        $company->restore();
    }

    /**
     * Delete for good, recording what the company was first, as
     * `UserService::forceDelete()` does — afterwards the trail has only its id.
     */
    public function forceDelete(Company $company): void
    {
        $this->audits->record($company, AuditEvent::ForceDeleted, [
            'name' => $company->name,
            'short_code' => $company->short_code,
        ]);

        $company->forceDelete();
    }

    /**
     * Soft delete each company that may be deleted; the rest are reported, not
     * deleted. Each row goes through {@see self::delete()}, so each is audited.
     *
     * @param  iterable<Company>  $companies
     * @return array{done: int, refused: list<string>}
     */
    public function bulkDelete(iterable $companies): array
    {
        return $this->eachUnblocked($companies, $this->delete(...));
    }

    /**
     * @param  iterable<Company>  $companies  soft-deleted companies
     * @return array{done: int, refused: list<string>}
     */
    public function bulkForceDelete(iterable $companies): array
    {
        return $this->eachUnblocked($companies, $this->forceDelete(...));
    }

    /**
     * Apply `$write` to every company its deletion guard allows, collecting a
     * "short code: reason" line for each it refuses — the guard holds per row
     * in bulk exactly as it does for one (§9.14).
     *
     * @param  iterable<Company>  $companies
     * @param  callable(Company): void  $write
     * @return array{done: int, refused: list<string>}
     */
    private function eachUnblocked(iterable $companies, callable $write): array
    {
        $done = 0;
        $refused = [];

        foreach ($companies as $company) {
            $blocker = $this->deletionBlocker($company);

            if ($blocker !== null) {
                $refused[] = "{$company->short_code}: {$blocker}";

                continue;
            }

            $write($company);
            $done++;
        }

        return ['done' => $done, 'refused' => $refused];
    }

    /*
    |--------------------------------------------------------------------------
    | Guards — each returns the refusal message, or null to allow
    |--------------------------------------------------------------------------
    */

    /**
     * Refused while any department, employee, client, supplier, product,
     * product category or verification item belongs to the company.
     *
     * Counted with the company scope removed and deleted rows included
     * (§9.14): whether a row is in use is a question about the database, not
     * about what the actor can see, and a deleted row still points here — the
     * foreign key would refuse the permanent delete either way.
     */
    public function deletionBlocker(Company $company): ?string
    {
        $departments = Department::withoutGlobalScopes()->where('company_id', $company->id)->count();

        if ($departments > 0) {
            return trans_choice(
                '{1} One department belongs to this company. Deactivate the company instead, or delete that department for good first.'
                .'|[2,*] :count departments belong to this company. Deactivate the company instead, or delete them for good first.',
                $departments,
                ['count' => $departments],
            );
        }

        $employees = Employee::withoutGlobalScopes()->where('company_id', $company->id)->count();

        if ($employees > 0) {
            return trans_choice(
                '{1} One employee belongs to this company. Deactivate the company instead, or delete that employee for good first.'
                .'|[2,*] :count employees belong to this company. Deactivate the company instead, or delete them for good first.',
                $employees,
                ['count' => $employees],
            );
        }

        $clients = Client::withoutGlobalScopes()->where('company_id', $company->id)->count();

        if ($clients > 0) {
            return trans_choice(
                '{1} One client belongs to this company. Deactivate the company instead, or delete that client for good first.'
                .'|[2,*] :count clients belong to this company. Deactivate the company instead, or delete them for good first.',
                $clients,
                ['count' => $clients],
            );
        }

        $suppliers = Supplier::withoutGlobalScopes()->where('company_id', $company->id)->count();

        if ($suppliers > 0) {
            return trans_choice(
                '{1} One supplier belongs to this company. Deactivate the company instead, or delete that supplier for good first.'
                .'|[2,*] :count suppliers belong to this company. Deactivate the company instead, or delete them for good first.',
                $suppliers,
                ['count' => $suppliers],
            );
        }

        // Before the categories: a product holds its category, so the
        // products are what must go first.
        $products = Product::withoutGlobalScopes()->where('company_id', $company->id)->count();

        if ($products > 0) {
            return trans_choice(
                '{1} One product belongs to this company. Deactivate the company instead, or delete that product for good first.'
                .'|[2,*] :count products belong to this company. Deactivate the company instead, or delete them for good first.',
                $products,
                ['count' => $products],
            );
        }

        // The starter set counts too (owner, 2026-11-03): every company made on
        // screen has it, so its categories must be deleted for good first.
        $productCategories = ProductCategory::withoutGlobalScopes()->where('company_id', $company->id)->count();

        if ($productCategories > 0) {
            return trans_choice(
                '{1} One product category belongs to this company. Deactivate the company instead, or delete that product category for good first.'
                .'|[2,*] :count product categories belong to this company. Deactivate the company instead, or delete them for good first.',
                $productCategories,
                ['count' => $productCategories],
            );
        }

        $verificationItems = VerificationItem::withoutGlobalScopes()->where('company_id', $company->id)->count();

        if ($verificationItems > 0) {
            return trans_choice(
                '{1} One verification item belongs to this company. Deactivate the company instead, or delete that verification item for good first.'
                .'|[2,*] :count verification items belong to this company. Deactivate the company instead, or delete them for good first.',
                $verificationItems,
                ['count' => $verificationItems],
            );
        }

        return null;
    }

    public function forceDeletionBlocker(Company $company): ?string
    {
        return $this->deletionBlocker($company);
    }
}
