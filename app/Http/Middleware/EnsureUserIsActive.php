<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of a user who was deactivated after signing in.
 *
 * Sign-in already refuses an inactive account (FortifyServiceProvider), but a
 * session opened before the deactivation would otherwise live on until it
 * expired. A soft-deleted user needs no check here: the session guard resolves
 * the user through the model's default scope, so a trashed user is simply a
 * guest on their next request.
 */
class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => __('This account has been deactivated. Contact an administrator.'),
            ]);
        }

        return $next($request);
    }
}
