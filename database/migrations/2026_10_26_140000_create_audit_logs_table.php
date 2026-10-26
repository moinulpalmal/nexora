<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The application-wide audit trail — ARCHITECTURE.md §9.3.
 *
 * Written by `owen-it/laravel-auditing` through `App\Models\Admin\AuditLog`, and
 * named `audit_logs` rather than the package's `audits`.
 *
 * Three columns depart from the package's published stub, each deliberately:
 *
 * 1. **`actor_name` / `actor_username` are denormalised.** The package stores
 *    only `user_id`, and resolving the name through the relation would render a
 *    *soft-deleted* user's whole history as "System". Stamping the name at write
 *    time records the state as it was.
 * 2. **`auditable` is nullable.** A failed sign-in for a login that matches no
 *    user has no subject to point at, and refusing to record it is the opposite
 *    of what an audit trail is for.
 * 3. **`old_values` / `new_values` are `longText`, not `text`,** which caps at
 *    64 KB on MySQL and truncates silently on a non-strict connection.
 *
 * **There are no foreign keys, and that is not an oversight.** `user_id` is a
 * bare column so the trail outlives the account that made it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            // Who — a morph pair as the package expects, plus the denormalised name.
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_username', 30)->nullable();

            $table->string('event');

            // What — nullable, see the class docblock.
            $table->nullableMorphs('auditable');

            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();

            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();

            $table->timestamps();

            // The viewer filters by actor and event and sorts newest first.
            $table->index(['user_id', 'user_type']);
            $table->index(['event', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
