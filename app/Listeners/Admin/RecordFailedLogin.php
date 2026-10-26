<?php

namespace App\Listeners\Admin;

use App\Enums\Admin\AuditEvent;
use App\Models\User;
use App\Services\Admin\AuditRecorder;
use Illuminate\Auth\Events\Failed;
use Laravel\Fortify\Fortify;

/**
 * Audits a rejected sign-in: the login that was typed, never the password.
 *
 * Fortify fires `Failed` when `authenticateUsing` returns null — an unknown
 * login or a wrong password — and always with a null user, because the custom
 * callback never hands one back. The account is therefore resolved here from
 * the typed login (already lowercased by Fortify), so repeated failures against
 * one account show up in that account's history.
 */
class RecordFailedLogin
{
    public function __construct(private readonly AuditRecorder $audits) {}

    public function handle(Failed $event): void
    {
        $login = $event->credentials[Fortify::username()] ?? null;
        $login = is_scalar($login) ? (string) $login : null;

        $user = $event->user instanceof User ? $event->user : $this->accountFor($login);

        $this->audits->record($user, AuditEvent::LoginFailed, [], [
            'login' => $login,
            'guard' => $event->guard,
            'user_exists' => $user instanceof User,
        ]);
    }

    private function accountFor(?string $login): ?User
    {
        if ($login === null || $login === '') {
            return null;
        }

        return User::query()
            ->where(fn ($query) => $query->where('email', $login)->orWhere('username', $login))
            ->first();
    }
}
