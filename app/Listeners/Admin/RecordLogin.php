<?php

namespace App\Listeners\Admin;

use App\Enums\Admin\AuditEvent;
use App\Models\User;
use App\Services\Admin\AuditRecorder;
use Illuminate\Auth\Events\Login;

/**
 * Audits every sign-in, including one restored from a remember-me cookie
 * (`remember` is then true).
 */
class RecordLogin
{
    public function __construct(private readonly AuditRecorder $audits) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audits->record($event->user, AuditEvent::LoggedIn, [], [
            'username' => $event->user->username,
            'guard' => $event->guard,
            'remember' => $event->remember,
        ]);
    }
}
