<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects suppliers — the ERP's one supplier list (owner, 2026-11-02), kept
 * per company and laid out as `create_clients_table`, plus the supplier's
 * category, its tax and licence numbers and one bank account. Accounting's
 * bills will point at the same rows.
 *
 * `company_id` restricts on delete, as clients' does, and
 * `CompanyService::deletionBlocker()` says so first. `supplier_category_id`
 * restricts too (§9.14): a category in use cannot be deleted, and
 * `SupplierCategoryService::deletionBlocker()` names the suppliers holding it.
 * `code` and `name` are unique *within a company*, deleted rows included; both
 * composite indexes lead with `company_id`, so there is no separate one.
 *
 * TIN and BIN are filtered from their start but get no index, and are not
 * unique (owner, 2026-11-02): a company's supplier list is short, and two
 * branches of one firm may share a number. The bank columns are read in the
 * form only.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_category_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 150);
            $table->string('address', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('tin', 12)->nullable();
            $table->string('bin', 20)->nullable();
            $table->string('trade_license_no', 50)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch', 150)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_routing_number', 20)->nullable();
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
        Schema::dropIfExists('suppliers');
    }
};
