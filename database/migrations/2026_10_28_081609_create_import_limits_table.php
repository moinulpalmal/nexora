<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The spreadsheet-import engine's runtime caps (Admin → Application Settings →
 * Import Limits, ARCHITECTURE.md §9.13). A single row, **not seeded**: until
 * the first save, `ImportLimit::current()` falls back to the defaults in
 * `config/sheet-import.php`, as `quick_login_settings` does for its switch.
 *
 * `max_file_size_kb` is deliberately not a column — PHP's own upload limits
 * bound it, so it stays in config where it can be changed together with them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_limits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('max_rows');
            $table->unsignedSmallInteger('max_columns');
            $table->unsignedSmallInteger('max_sheets');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_limits');
    }
};
