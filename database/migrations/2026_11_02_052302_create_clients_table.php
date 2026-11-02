<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects clients — the ERP's one customer list (owner, 2026-11-02), and the
 * Projects module's first company-scoped master table (ARCHITECTURE.md §9.10,
 * §9.14). Laid out as `create_departments_table`.
 *
 * `company_id` restricts on delete: a client is a fact about its company, and
 * `CompanyService::deletionBlocker()` says so before the database has to.
 * `code` and `name` are each unique *within a company*, soft-deleted rows
 * included, so a deleted client keeps both reserved. Both composite indexes
 * lead with `company_id`, the column every scoped query filters on, so they
 * double as its index — there is deliberately no separate one. The contact
 * columns are filtered but get no index: a company's client list is short, and
 * an index there would be paid for on every write.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 150);
            $table->string('address', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
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
        Schema::dropIfExists('clients');
    }
};
