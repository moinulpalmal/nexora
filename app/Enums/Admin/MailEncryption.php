<?php

namespace App\Enums\Admin;

use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;
use App\Enums\EnumRegistry;
use App\Models\Admin\MailSetting;

/**
 * How the SMTP connection is secured — {@see MailSetting}.
 *
 * **`Starttls` is what Laravel did before the move.** `config/mail.php` never read
 * `MAIL_ENCRYPTION`: port 465 meant implicit TLS and anything else meant Symfony's
 * default, upgrading with STARTTLS whenever the server offers it. The one-time
 * copy maps onto these cases so a server that worked keeps working.
 *
 * Registered in {@see EnumRegistry} like every other enum under `app/Enums/`.
 */
enum MailEncryption: string implements LabelledEnum
{
    use ProvidesOptions;

    /** Implicit TLS from the first byte — `smtps://`, usually port 465. */
    case Ssl = 'ssl';

    /** Plain connection upgraded with STARTTLS when the server offers it, usually port 587. */
    case Starttls = 'starttls';

    /** Never encrypted, even when the server offers STARTTLS. */
    case None = 'none';

    public static function id(): string
    {
        return 'mail-encryption';
    }

    public function label(): string
    {
        return match ($this) {
            self::Ssl => __('SSL/TLS (usually port 465)'),
            self::Starttls => __('STARTTLS (usually port 587)'),
            self::None => __('None'),
        };
    }
}
