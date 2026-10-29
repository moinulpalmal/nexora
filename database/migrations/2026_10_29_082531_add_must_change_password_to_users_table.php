<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A password someone else set is temporary (owner, 2026-10-29): the account
 * created from an HRM employee, a user created in Admin, an Admin password
 * reset. `EnsurePasswordIsChanged` keeps a flagged user on Settings → Security
 * until they choose their own (ARCHITECTURE.md §9.2).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
