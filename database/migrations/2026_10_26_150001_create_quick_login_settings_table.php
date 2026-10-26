<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The development quick-login switch (Admin → Application Settings → Quick
 * Login). A single row; off unless an administrator turns it on, and even then
 * the buttons only render in the `local` and `testing` environments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_login_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_login_settings');
    }
};
