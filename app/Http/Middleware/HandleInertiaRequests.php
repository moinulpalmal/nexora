<?php

namespace App\Http\Middleware;

use App\Enums\Theme;
use App\Listeners\CollapseNavGroupsOnLogin;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * **A shared prop that affects rendering must be `Inertia::always()`**, or
     * it goes stale the moment anything does a partial reload: a partial reload
     * returns only the props it names, and no page author thinks to name a
     * shared prop they never passed. `auth` is wrapped for that reason. The
     * others are viewer-local (`sidebarOpen`, `collapsedNavGroups`, `theme`) or
     * static config (`name`), so nothing re-reads them after the first load.
     * See ARCHITECTURE.md §9.5.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => Inertia::always([
                'user' => $request->user(),
            ]),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'collapsedNavGroups' => $this->collapsedNavGroups($request),
            'theme' => Theme::forRequest($request)->value,
            'passwordPolicy' => $this->passwordPolicy(),
        ];
    }

    /**
     * The password policy, in the shape the checklist under each password
     * field renders from.
     *
     * Shared rather than passed per page because it is a static config read,
     * it is needed on a *guest* page (password reset) as well as authed ones,
     * and a password surface added later must not be able to ship without
     * stating the rules.
     *
     * `hint` is the machine-readable `passwordrules` attribute value Safari and
     * iOS Keychain read when generating a password; it is not what the
     * checklist displays.
     *
     * @return array<string, mixed>
     */
    private function passwordPolicy(): array
    {
        /** @var array{min_length: int, mixed_case: bool, letters: bool, numbers: bool, symbols: bool, uncompromised: bool} $policy */
        $policy = config('auth.password_policy');

        return [
            'minLength' => $policy['min_length'],
            'mixedCase' => $policy['mixed_case'],
            'letters' => $policy['letters'],
            'numbers' => $policy['numbers'],
            'symbols' => $policy['symbols'],
            'uncompromised' => $policy['uncompromised'],
            'hint' => Password::defaults()->toPasswordRulesString(),
        ];
    }

    /**
     * The sidebar groups the user has collapsed, from the `sidebar_groups` cookie.
     *
     * `['*']` straight after sign-in ({@see CollapseNavGroupsOnLogin}) means
     * "collapse every group", which `useNavGroups` expands to the full set.
     *
     * @return list<string>
     */
    private function collapsedNavGroups(Request $request): array
    {
        if ($request->session()->get('collapse_nav_groups')) {
            return ['*'];
        }

        $cookie = $request->cookie('sidebar_groups');

        if (! is_string($cookie) || $cookie === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $cookie)),
            fn (string $label): bool => $label !== '',
        ));
    }
}
