<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a user whose password someone else set on Settings → Security until
 * they choose their own (ARCHITECTURE.md §9.2; owner, 2026-10-29).
 *
 * `users.must_change_password` is set by `UserService` when an administrator
 * creates the account or resets its password, and by an HRM employee's
 * account being opened; `SecurityController::update()` clears it.
 *
 * The security page sits behind `RequirePassword`. A flagged user signed in
 * with the temporary password moments ago, so their session is marked as
 * confirmed here rather than asking for it twice — changing it still needs
 * the current password.
 */
class EnsurePasswordIsChanged
{
    /**
     * What a flagged user may still reach: the page and the form that clear
     * the flag, signing out, the password confirmation screens, and the
     * header's bell and help panel that the page's layout loads.
     *
     * @var list<string>
     */
    private const array ALLOWED_ROUTES = [
        'security.edit',
        'user-password.update',
        'logout',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'training.panel',
        'notifications.index',
        'notifications.read',
        'notifications.read-all',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            if ($request->routeIs('security.edit')) {
                $request->session()->passwordConfirmed();
            }

            return $next($request);
        }

        return redirect()->route('security.edit');
    }
}
