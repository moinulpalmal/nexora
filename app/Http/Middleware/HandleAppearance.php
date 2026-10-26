<?php

namespace App\Http\Middleware;

use App\Enums\Theme;
use App\Http\Controllers\Settings\AppearanceController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $theme = Theme::forRequest($request);

        View::share('theme', $theme->value);
        // `system` cannot be resolved server side; the inline script corrects it before paint.
        View::share('resolvedTheme', $theme->resolve()->value);
        View::share('themeIsDark', $theme->isDark());
        View::share('darkThemes', array_map(
            fn (Theme $dark): string => $dark->value,
            Theme::darkThemes(),
        ));

        $this->mirrorStoredThemeToCookie($request);

        return $next($request);
    }

    /**
     * Copy the signed-in user's stored theme onto this host's `theme` cookie.
     *
     * `users.theme` is authoritative, and the cookie exists for one job: letting
     * the root template pick a theme on surfaces with no authenticated user —
     * login, 419, 500, password confirm. Those surfaces are the only readers.
     *
     * It is re-planted here rather than written once by
     * {@see AppearanceController::update()} because
     * a cookie belongs to **one host**, and the application may answer on several
     * (a LAN IP and localhost, more after deployment). A bare-IP host cannot carry a
     * `Domain` attribute, so the jars can never be joined; doing it on every request
     * means a host the user has never visited is correct from their first
     * authenticated page load there. See ARCHITECTURE.md §9.5.
     *
     * `Cookie::forever()` takes its path, domain, secure and same-site attributes
     * from `config/session.php`, so the cookie follows the environment unchanged.
     */
    private function mirrorStoredThemeToCookie(Request $request): void
    {
        $stored = $request->user()?->theme;

        // No stored theme is not a preference to mirror; the user has never chosen.
        if (! $stored instanceof Theme) {
            return;
        }

        if ($request->cookie('theme') === $stored->value) {
            return;
        }

        // `theme` is exempt from encryptCookies (bootstrap/app.php) so the root
        // template can read it back. Nothing in JavaScript reads it, which is why
        // the default `httpOnly` is left on.
        Cookie::queue(Cookie::forever('theme', $stored->value));
    }
}
