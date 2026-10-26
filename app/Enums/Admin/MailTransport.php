<?php

namespace App\Enums\Admin;

use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;
use App\Enums\EnumRegistry;
use App\Models\Admin\MailSetting;

/**
 * How the application hands its mail over — {@see MailSetting}.
 *
 * `Log` replaces the old `MAIL_MAILER=log` trick: every mail is written to
 * `storage/logs/mail.log` (the `mail` log channel, always debug level) instead
 * of being sent, and a stored mail sent that way reads `Logged`, never `Sent`.
 *
 * Registered in {@see EnumRegistry} like every other enum under `app/Enums/`.
 */
enum MailTransport: string implements LabelledEnum
{
    use ProvidesOptions;

    /** Delivered through the mail server on the tab. */
    case Smtp = 'smtp';

    /** Written to `storage/logs/mail.log`; nobody receives anything. */
    case Log = 'log';

    public static function id(): string
    {
        return 'mail-transport';
    }

    public function label(): string
    {
        return match ($this) {
            self::Smtp => __('SMTP server'),
            self::Log => __('Log only (don\'t send)'),
        };
    }
}
