<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Records when and from where a user last signed in.
 *
 * Written quietly: a sign-in is not an edit of the account, so it must not
 * stamp `last_updated_by` (ActorObserver) or write an `updated` audit row —
 * sign-ins are audited on their own by the login listener.
 */
class StampLastLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->saveQuietly();
    }
}
