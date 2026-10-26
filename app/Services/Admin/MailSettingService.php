<?php

namespace App\Services\Admin;

use App\Enums\Admin\AuditEvent;
use App\Enums\Admin\MailEncryption;
use App\Enums\Admin\MailTransport;
use App\Exceptions\Admin\MailNotConfiguredException;
use App\Mail\Admin\MailSettingTestMail;
use App\Models\Admin\MailSetting;
use App\Models\User;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Throwable;

/**
 * The mail server stored in the database, and the `database` mailer built
 * from it (registered in `AppServiceProvider::configureMailer()`).
 *
 * The mailer is built lazily, on the first mail sent in a process, and dropped
 * after every save — so a change applies to the next mail without a restart.
 * A long-running queue worker builds it once; `forgetMailer()` is what makes a
 * save reach it too.
 */
class MailSettingService
{
    /** The mailer name in `config/mail.php`. */
    public const string MAILER = 'database';

    /** Where "Log only" writes (`config/logging.php`). */
    public const string LOG_CHANNEL = 'mail';

    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Save the server. A blank password keeps the stored one; a changed one is
     * recorded as its own event (the values never are).
     *
     * @param  array{transport: string, host: ?string, port: ?int, encryption: ?string, username: ?string, password: ?string, from_address: string, from_name: string}  $values
     */
    public function update(array $values): MailSetting
    {
        $setting = MailSetting::query()->firstOrNew(['id' => MailSetting::SINGLETON_ID]);

        // A password encrypted under another APP_KEY cannot be compared or kept.
        if ($setting->hasUnreadablePassword()) {
            $setting->setRawAttributes(['password' => null] + $setting->getAttributes(), true);
        }

        $password = $values['password'];
        $passwordChanged = filled($password) && $password !== $setting->readablePassword();

        $setting->fill([
            'transport' => $values['transport'],
            'host' => $values['host'],
            'port' => $values['port'],
            'encryption' => $values['encryption'] ?? MailEncryption::Starttls->value,
            'username' => $values['username'],
            'from_address' => $values['from_address'],
            'from_name' => $values['from_name'],
        ]);

        if ($passwordChanged) {
            $setting->password = $password;
        }

        $setting->save();

        if ($passwordChanged) {
            $this->audits->record($setting, AuditEvent::MailPasswordChanged);
        }

        $this->forgetMailer();

        return $setting;
    }

    /**
     * The Symfony transport for the saved server — the `database` mailer's body.
     *
     * @throws MailNotConfiguredException when nothing usable is saved.
     */
    public function transport(MailManager $manager): TransportInterface
    {
        $setting = MailSetting::current();

        if (! $setting->isComplete()) {
            throw new MailNotConfiguredException;
        }

        if ($setting->hasUnreadablePassword()) {
            throw new MailNotConfiguredException(__('The saved mail password cannot be read: it was saved under a different APP_KEY. Enter it again on Admin → Application Settings → Mail Server.'));
        }

        config(['mail.from' => ['address' => $setting->from_address, 'name' => $setting->from_name]]);

        if ($setting->transport === MailTransport::Log) {
            return $manager->createSymfonyTransport(['transport' => 'log', 'channel' => self::LOG_CHANNEL]);
        }

        return $manager->createSymfonyTransport([
            'transport' => 'smtp',
            'scheme' => $setting->encryption === MailEncryption::Ssl ? 'smtps' : 'smtp',
            'host' => $setting->host,
            'port' => $setting->port,
            'username' => $setting->username,
            'password' => $setting->readablePassword(),
            'auto_tls' => $setting->encryption !== MailEncryption::None,
            'timeout' => config('mail.mailers.smtp.timeout'),
            'local_domain' => config('mail.mailers.smtp.local_domain'),
        ]);
    }

    /**
     * Whether mail is only written to the log, so a success message must not
     * claim anyone received it.
     */
    public function isLogOnly(): bool
    {
        return config('mail.default') === self::MAILER
            && MailSetting::current()->transport === MailTransport::Log;
    }

    public function forgetMailer(): void
    {
        Mail::purge(self::MAILER);
    }

    /**
     * Send the test mail to the actor, synchronously, and audit the attempt.
     *
     * @return string|null the failure message, or null when it went out
     */
    public function sendTest(User $actor): ?string
    {
        $this->forgetMailer();

        try {
            Mail::to($actor->email)->send(new MailSettingTestMail($actor));
            $error = null;
        } catch (Throwable $exception) {
            report($exception);
            $error = $exception->getMessage();
        }

        $this->audits->record(MailSetting::current(), AuditEvent::MailTestSent, [], [
            'to' => $actor->email,
            'sent' => $error === null,
            'logged' => $error === null && $this->isLogOnly(),
            'error' => $error,
        ]);

        return $error;
    }
}
