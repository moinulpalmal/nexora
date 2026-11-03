<?php

namespace Database\Seeders\ProjectManagement;

use App\Models\Admin\Company;
use App\Services\ProjectManagement\ProductCategoryService;
use Illuminate\Database\Seeder;

/**
 * Back-fills the product-category starter set into every company that exists
 * (owner, 2026-11-03; ARCHITECTURE.md §9.14) — the first seeded master that is
 * kept per company. A company created later gets the set from
 * `CompanyService::create()` instead, so on a fresh install, where no company
 * exists yet, this adds nothing.
 *
 * **Idempotent and additive**, as `UnitSeeder`: a starter category whose code
 * or name is already taken in the company — a deleted one included, ignoring
 * case — is skipped ({@see ProductCategoryService::addStarterSet()}). Deleted
 * companies are left alone. Seeder rows are console writes, so they are not
 * audited (§9.3).
 */
class ProductCategorySeeder extends Seeder
{
    public function run(ProductCategoryService $categories): void
    {
        Company::query()->each(fn (Company $company) => $categories->addStarterSet($company));
    }
}
