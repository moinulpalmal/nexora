<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects verification items (owner, 2026-11-03) — kept per company, and laid
 * out as `create_product_categories_table` without a code: the owner asked for
 * a name, a description and a status only (ARCHITECTURE.md §9.10, §9.14).
 *
 * `company_id` restricts on delete: an item is a fact about its company, and
 * `CompanyService::deletionBlocker()` says so before the database has to.
 * `name` is unique *within a company*, soft-deleted rows included, so a
 * deleted item keeps its name reserved. The composite index leads with
 * `company_id`, the column every scoped query filters on, so it doubles as its
 * index — there is deliberately no separate one.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verification_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_items');
    }
};
