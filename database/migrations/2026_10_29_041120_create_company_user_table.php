<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which companies each user may work in (ARCHITECTURE.md §9.10).
 *
 * A grant is part of both rows, not a fact about either, so it cascades from
 * each side. The composite primary key leads with `user_id` — the direction
 * every request reads it — and `company_id` gets its own index for the other.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->index()->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
