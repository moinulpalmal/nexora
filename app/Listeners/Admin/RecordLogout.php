<?php

namespace App\Listeners\Admin;

use App\Enums\Admin\AuditEvent;
use App\Models\User;
use App\Services\Admin\AuditRecorder;
use Illuminate\Auth\Events\Logout;

/**
 * Audits every sign-out, including the one `EnsureUserIsActive` forces on a
 * deactivated user.
 */
class RecordLogout
{
    public function __construct(private readonly AuditRecorder $audits) {}

    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audits->record($event->user, AuditEvent::LoggedOut, [], [
            'username' => $event->user->username,
            'guard' => $event->guard,
        ]);
    }
}
