<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin countries — a group-wide master table with no `company_id`, like
 * `designations`: one list shared by every company (owner, 2026-11-02;
 * ARCHITECTURE.md §9.14). It ships filled from the ISO 3166-1 list by
 * `CountrySeeder`.
 *
 * `name`, `iso2` and `iso3` are each unique across the whole table,
 * soft-deleted rows included, so a deleted country keeps all three reserved.
 * The numeric, dial and currency codes are not unique — territories share a
 * dial code (`+1`) and a currency (`EUR`) — and carry no index: the list
 * filters them by prefix over a table of a few hundred rows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->char('iso2', 2)->unique();
            $table->char('iso3', 3)->unique();
            $table->char('numeric_code', 3)->nullable();
            $table->string('dial_code', 10)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('nationality', 100)->nullable();
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
        Schema::dropIfExists('countries');
    }
};
