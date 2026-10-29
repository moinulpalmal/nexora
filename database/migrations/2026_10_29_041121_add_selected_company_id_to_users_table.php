<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The company a user is working in, remembered between sessions and devices
 * (ARCHITECTURE.md §9.10). A preference, not a grant: `SelectedCompany` checks
 * it against the user's usable companies on every request and falls back when
 * it no longer is one.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('selected_company_id')->nullable()->after('theme')->constrained('companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selected_company_id');
        });
    }
};
