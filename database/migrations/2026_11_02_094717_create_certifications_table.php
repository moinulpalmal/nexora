<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects certifications — the standards a supplier may hold or a tender may
 * demand (ISO 9001, amfori BSCI, CE, BSTI…). A catalogue of standards, not a
 * certificate anyone holds: a held certificate, with its number and expiry,
 * will be a table of its own pointing here (owner, 2026-11-02).
 * **Group-wide** (ARCHITECTURE.md §9.10), laid out as `create_suppliers_table`
 * without the company.
 *
 * `code` is unique across the table, soft-deleted rows included. `name` is
 * unique per `version` (ISO 9001 2008 and 2015 are two rows); the composite
 * unique backs it, but a NULL version is distinct to the database, so
 * `CertificationRequest` is what refuses a second unversioned row of one name.
 * The category's foreign key gets its index from the constraint; nothing else
 * is indexed — the list is a few dozen rows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certification_category_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('issuing_body', 150)->nullable();
            $table->string('version', 20)->nullable();
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
