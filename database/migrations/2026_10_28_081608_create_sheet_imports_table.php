<?php

use App\Services\Imports\ImportBlueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per uploaded spreadsheet, for every model the mapped-import engine
 * ever serves (ARCHITECTURE.md §9.13): the file as it arrived, the mapping the
 * uploader confirmed, and what it did. Ported from compozit in its final,
 * multi-worksheet shape rather than replaying the five migrations that grew it.
 *
 * `import_key` names the {@see ImportBlueprint} this upload was staged against
 * — **not a morph**. It identifies a *definition*, not a model instance, so it
 * stays correct even if the model behind it is renamed.
 *
 * **`company_id` is pre-wired, with no foreign key** (ARCHITECTURE.md §9.10).
 * There is no `companies` table yet; the Company master's own migration adds
 * the constraint. An index now, because the first company-scoped importer will
 * filter pending imports by it.
 *
 * **No idempotency-by-hash.** Re-uploading the same bytes under a different
 * column mapping is a legitimate second import — the file has no meaning until
 * it is mapped — so `source_hash` is kept for reference with no uniqueness.
 *
 * **No other index.** The only lookup is `ImportRunner::pendingFor()` —
 * `inserted_by`, `import_key`, `status`, newest first — and the `inserted_by`
 * foreign key is already indexed; the rest are residual filters on one
 * person's handful of rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sheet_imports', function (Blueprint $table): void {
            $table->id();

            $table->string('import_key', 100);
            $table->unsignedBigInteger('company_id')->nullable()->index();

            /* The blueprint's optional second scope (`ImportBlueprint::scopeField()`). */
            $table->unsignedBigInteger('scope_id')->nullable();

            $table->string('source_file_name', 255);

            /* Null when `sheet-import.storage.retain_original` is off. */
            $table->string('stored_path', 500)->nullable();

            $table->string('detected_file_type', 10);
            $table->unsignedSmallInteger('sheet_count')->default(1);
            $table->string('source_hash', 64);

            /* `ImportStatus` — pending, confirmed or cancelled; there is no failed state. */
            $table->string('status', 20)->default('pending');

            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);

            /* Only an `ImportRowWriter`'s partial import fills this. */
            $table->json('rejected_rows')->nullable();

            /* The confirmed decision: worksheet index => field => column, and skip/update. */
            $table->json('column_map')->nullable();
            $table->string('on_duplicate', 20)->nullable();

            /* Per-tab headers, suggested map, sample rows and warnings — never every row. */
            $table->json('payload')->nullable();

            /* Every staged row of every tab, so confirming never re-reads the file. */
            $table->json('staged_rows')->nullable();

            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sheet_imports');
    }
};
