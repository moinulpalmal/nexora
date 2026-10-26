<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Unique across trashed rows too, so restoring a user can never collide.
            // Both are stored lowercased (User mutators) and either one signs in.
            $table->string('username', 30)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 30)->nullable();
            $table->string('employee_code', 30)->nullable()->unique();
            // Relative path on the `public` disk (avatars/{uuid}.ext).
            $table->string('avatar_path')->nullable();
            // App\Enums\RecordStatus — 'I' blocks sign-in (FortifyServiceProvider).
            $table->char('status', 1)->default('A');
            // App\Enums\Theme; null until the user picks one, so the cookie still decides.
            $table->string('theme')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            // Stamped by App\Observers\ActorObserver.
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // The users list filters on status inside the active/trashed tabs.
            $table->index(['deleted_at', 'status']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
