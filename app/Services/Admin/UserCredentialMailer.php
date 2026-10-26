<?php

namespace App\Services\Admin;

use App\Enums\Admin\AuditEvent;
use App\Mail\Admin\UserPasswordResetMail;
use App\Mail\Admin\UserWelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails a user the credentials an administrator just set — on creation, or
 * after a password reset — when the dialog's "Email the user" box is ticked.
 *
 * The mail is **queued** on the `mail` queue, so a slow or unreachable SMTP
 * server never holds up the admin screen; the queued payload is encrypted
 * (`ShouldBeEncrypted`). The audit row records that it was queued and to whom.
 * A queueing failure is reported and swallowed: the account change already
 * happened and must not be rolled back by a mail problem.
 */
class UserCredentialMailer
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @return bool whether the mail was queued
     */
    public function welcome(User $user, string $password): bool
    {
        return $this->queue($user, new UserWelcomeMail($user, $password), AuditEvent::UserWelcomeMailed);
    }

    /**
     * @return bool whether the mail was queued
     */
    public function passwordReset(User $user, string $password, User $actor): bool
    {
        return $this->queue($user, new UserPasswordResetMail($user, $password, $actor->name), AuditEvent::UserPasswordResetMailed);
    }

    private function queue(User $user, UserWelcomeMail|UserPasswordResetMail $mail, AuditEvent $event): bool
    {
        try {
            Mail::to($user->email)->queue($mail);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        $this->audits->record($user, $event, [], ['to' => $user->email]);

        return true;
    }
}
