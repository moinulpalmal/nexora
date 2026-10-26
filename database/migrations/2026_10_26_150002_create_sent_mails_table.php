<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every mail the application handed to its mailer, written by
 * `Listeners\Admin\RecordSentMail` on `MessageSent`.
 *
 * `html` is the rendered body with secrets (a `password` in the mail's data)
 * replaced — the log is for checking what went out, never for reading a
 * credential back. `user_id` is the account the mail concerns, when the
 * mailable names one; no foreign key, so the log outlives the account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_mails', function (Blueprint $table): void {
            $table->id();
            $table->string('mailable')->nullable();
            $table->string('to_address');
            $table->string('subject')->nullable();
            $table->longText('html')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('mailer', 30)->nullable();
            $table->timestamp('sent_at')->index();
            $table->timestamps();

            $table->index('to_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_mails');
    }
};
