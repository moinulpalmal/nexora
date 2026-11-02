<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects units of measure — what a quantity is counted in (KG, SQM, HR…).
 * A **group-wide** master table with no `company_id`: a kilogram is the same
 * for every company (owner, 2026-11-02; ARCHITECTURE.md §9.10, §9.14), laid
 * out as `create_tender_types_table`.
 *
 * `code` and `name` are each unique across the whole table, soft-deleted rows
 * included, so a deleted unit keeps both reserved. `symbol` is the printed
 * form and may repeat. `decimal_places` is how many decimals a quantity in the
 * unit takes (PCS 0, KG 3), there for the quantity fields that will point at
 * it. Each unique is its column's index; there is deliberately no other — the
 * list is a few dozen rows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100)->unique();
            $table->string('symbol', 10)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->string('description', 255)->nullable();
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
        Schema::dropIfExists('units');
    }
};
