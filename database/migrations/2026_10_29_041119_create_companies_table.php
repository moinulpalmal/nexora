<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Company master (ARCHITECTURE.md §9.10): every Projects, Accounting and
 * HRM record belongs to one of these.
 *
 * Both `name` and `short_code` are unique across the whole table, soft-deleted
 * rows included: a deleted company keeps its values reserved, so restoring it
 * can never collide. The unique indexes are the only indexes — each already is
 * one, and the list filters on nothing else but `status`, which has two values
 * and would not be worth an index of its own.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('short_code', 10)->unique();
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
        Schema::dropIfExists('companies');
    }
};
