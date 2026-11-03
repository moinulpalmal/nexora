<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects products (owner, 2026-11-03) — kept per company and laid out as
 * `create_suppliers_table`: a required category, a code, a name and a
 * description. The first table to point at a product category.
 *
 * `company_id` restricts on delete, and `CompanyService::deletionBlocker()`
 * says so first. `product_category_id` restricts too (§9.14): a category in
 * use cannot be deleted, and `ProductCategoryService::deletionBlocker()` names
 * the products holding it. `code` and `name` are unique *within a company*,
 * deleted rows included; both composite indexes lead with `company_id`, so
 * there is no separate one.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
