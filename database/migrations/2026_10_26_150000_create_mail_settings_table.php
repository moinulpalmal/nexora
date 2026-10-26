<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The outgoing mail server, edited on Admin → Application Settings → Mail
 * Server and read by the `database` mailer (`MailSettingService::transport()`).
 *
 * A single row (id 1). It is seeded from the `MAIL_*` environment values once,
 * so an install that already mailed keeps mailing; after that the database is
 * the source of truth and the `.env` values are ignored. The password is
 * encrypted with APP_KEY (the model's `encrypted` cast).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('transport', 10)->default('smtp');
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('encryption', 10)->default('starttls');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('mail_settings')->insert([
            'id' => 1,
            ...$this->copiedFromEnvironment(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }

    /**
     * The starting row, from `config('mail.legacy_mailer')` and the `smtp`
     * mailer's settings. Anything but `smtp` or `log` starts as an empty SMTP
     * form — there is nothing meaningful to copy.
     *
     * @return array<string, mixed>
     */
    private function copiedFromEnvironment(): array
    {
        $mailer = config('mail.legacy_mailer');

        if (! in_array($mailer, ['smtp', 'log'], true)) {
            return ['transport' => 'smtp', 'encryption' => 'starttls'];
        }

        $smtp = (array) config('mail.mailers.smtp', []);
        $port = filled($smtp['port'] ?? null) ? (int) $smtp['port'] : null;
        $password = filled($smtp['password'] ?? null) ? (string) $smtp['password'] : null;

        return [
            'transport' => $mailer,
            'host' => filled($smtp['host'] ?? null) ? (string) $smtp['host'] : null,
            'port' => $port,
            'encryption' => ($smtp['scheme'] ?? null) === 'smtps' || $port === 465 ? 'ssl' : 'starttls',
            'username' => filled($smtp['username'] ?? null) ? (string) $smtp['username'] : null,
            'password' => $password === null ? null : Crypt::encryptString($password),
            'from_address' => filled(config('mail.from.address')) ? (string) config('mail.from.address') : null,
            'from_name' => filled(config('mail.from.name')) ? (string) config('mail.from.name') : null,
        ];
    }
};
