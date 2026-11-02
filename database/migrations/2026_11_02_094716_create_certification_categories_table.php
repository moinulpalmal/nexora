<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects certification categories — quality management, environmental,
 * social compliance and the like. **Group-wide** (owner, 2026-11-02;
 * ARCHITECTURE.md §9.10): one list every certification is classified by, so
 * there is no `company_id`. Laid out as `create_supplier_categories_table`.
 *
 * `code` and `name` are each unique across the table, soft-deleted rows
 * included, so a deleted category keeps both reserved. The uniques are the
 * only indexes: the list is a handful of rows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certification_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certification_categories');
    }
};
