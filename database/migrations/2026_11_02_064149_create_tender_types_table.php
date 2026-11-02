<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects tender types — how a project was procured (open tendering, RFQ…).
 * A **group-wide** master table with no `company_id`: the same list for every
 * company (owner, 2026-11-02; ARCHITECTURE.md §9.10, §9.14), laid out as
 * `create_designations_table`.
 *
 * `code` and `name` are each unique across the whole table, soft-deleted rows
 * included, so a deleted tender type keeps both reserved. Each unique is its
 * column's index; there is deliberately no other — the list is a handful of
 * rows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tender_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100)->unique();
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
        Schema::dropIfExists('tender_types');
    }
};
