<?php

namespace App\Listeners;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Resets sidebar navigation groups to collapsed on every sign-in.
 *
 * The session flash is picked up by {@see HandleInertiaRequests}
 * on the very next request (the post-login redirect) and forwarded to the
 * frontend as a sentinel value, which the `useNavGroups` hook interprets as
 * "collapse every group".
 */
class CollapseNavGroupsOnLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        session()->flash('collapse_nav_groups', true);
    }
}
