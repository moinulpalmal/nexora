<?php

namespace App\Http\Middleware;

use App\Enums\Theme;
use App\Listeners\CollapseNavGroupsOnLogin;
use Illuminate\Http\Request;
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
