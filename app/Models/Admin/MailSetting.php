<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Contracts\Auditable;
use App\Enums\Admin\MailEncryption;
use App\Enums\Admin\MailTransport;
use App\Models\User;
use App\Observers\ActorObserver;
use App\Services\Admin\MailSettingService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * The outgoing mail server — a single row, read by {@see MailSettingService}.
 *
 * The password is encrypted with APP_KEY and never leaves this model: it is
 * hidden, excluded from the audit trail (a change is its own event), and the
 * settings page is only told whether one is set.
 *
 * @property int $id
 * @property MailTransport $transport
 * @property string|null $host
 * @property int|null $port
 * @property MailEncryption $encryption
 * @property string|null $username
 * @property string|null $password
 * @property string|null $from_address
 * @property string|null $from_name
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $lastUpdatedBy
 */
#[ObservedBy(ActorObserver::class)]
#[Fillable(['transport', 'host', 'port', 'encryption', 'username', 'password', 'from_address', 'from_name'])]
#[Hidden(['password'])]
class MailSetting extends Model implements Auditable
{
    use Audited;

    public const int SINGLETON_ID = 1;

    /**
     * @var list<string>
     */
    protected array $auditExclude = ['password'];

    /**
     * The row, or an unsaved default when it is missing (a fresh test database).
     */
    public static function current(): self
    {
        return static::query()->find(self::SINGLETON_ID)
            ?? new self(['transport' => MailTransport::Smtp, 'encryption' => MailEncryption::Starttls]);
    }

    /**
     * Whether enough is set to send: a sender, and a server unless logging only.
     */
    public function isComplete(): bool
    {
        if (blank($this->from_address) || blank($this->from_name)) {
            return false;
        }

        return $this->transport === MailTransport::Log
            || (filled($this->host) && $this->port !== null);
    }

    /**
     * The decrypted password, or null when none is set or it was encrypted
     * under another APP_KEY and can no longer be read.
     *
     * Decrypts the raw column explicitly (what the `encrypted` cast does on
     * read), so the failure is caught here instead of escaping a property read.
     */
    public function readablePassword(): ?string
    {
        $raw = $this->getAttributes()['password'] ?? null;

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasUnreadablePassword(): bool
    {
        return filled($this->getAttributes()['password'] ?? null)
            && $this->readablePassword() === null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transport' => MailTransport::class,
            'encryption' => MailEncryption::class,
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }
}
