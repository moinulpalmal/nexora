<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects product categories (owner, 2026-11-03) — kept per company, and laid
 * out as `create_clients_table` (ARCHITECTURE.md §9.10, §9.14).
 *
 * `company_id` restricts on delete: a category is a fact about its company, and
 * `CompanyService::deletionBlocker()` says so before the database has to.
 * `code` and `name` are each unique *within a company*, soft-deleted rows
 * included, so a deleted category keeps both reserved. Both composite indexes
 * lead with `company_id`, the column every scoped query filters on, so they
 * double as its index — there is deliberately no separate one.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
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
        Schema::dropIfExists('product_categories');
    }
};
