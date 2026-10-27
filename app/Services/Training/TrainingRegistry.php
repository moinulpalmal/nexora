<?php

namespace App\Services\Training;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The training registry (`config/training.php`), read for the manual, the help
 * dialog and the gaps check — see documentation/training.md.
 *
 * Every method is an array walk over config: no query, no model, so the shared
 * `training` prop costs nothing on any screen.
 */
class TrainingRegistry
{
    /**
     * Every registered page, keyed by its page key, with the key copied in.
     *
     * Each field is read with a typed accessor, so a malformed entry in
     * config/training.php fails loudly here rather than reaching a page.
     *
     * @return array<string, array{key: string, route: string, guide: string, section: string, sort: int, title_en: string, title_bn: string, status: string, shots: array<string, string>}>
     */
    public static function all(): array
    {
        $pages = [];

        foreach (Config::array('training.pages', []) as $key => $page) {
            $page = (array) $page;

            $pages[(string) $key] = [
                'key' => (string) $key,
                'route' => Arr::string($page, 'route'),
                'guide' => Arr::string($page, 'guide'),
                'section' => Arr::string($page, 'section'),
                'sort' => Arr::integer($page, 'sort'),
                'title_en' => Arr::string($page, 'title_en'),
                'title_bn' => Arr::string($page, 'title_bn'),
                'status' => Arr::string($page, 'status'),
                'shots' => array_map(strval(...), Arr::array($page, 'shots')),
            ];
        }

        return $pages;
    }

    /**
     * One page, or null when the key is not registered.
     *
     * @return array{key: string, route: string, guide: string, section: string, sort: int, title_en: string, title_bn: string, status: string, shots: array<string, string>}|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * The page key describing a route, or null for a route no guide covers.
     */
    public static function keyForRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::all() as $key => $page) {
            if ($page['route'] === $routeName) {
                return $key;
            }
        }

        return null;
    }

    public static function isWritten(string $key): bool
    {
        return (self::get($key)['status'] ?? null) === 'written';
    }

    /**
     * The shared `training` prop: what the header's help button needs to know
     * about the screen in front of the user, or null when no guide covers it.
     *
     * @return array{key: string, title_en: string, title_bn: string, status: string}|null
     */
    public static function forRoute(?string $routeName): ?array
    {
        $key = self::keyForRoute($routeName);

        if ($key === null) {
            return null;
        }

        $page = self::all()[$key];

        return [
            'key' => $key,
            'title_en' => $page['title_en'],
            'title_bn' => $page['title_bn'],
            'status' => $page['status'],
        ];
    }

    /**
     * The manual's table of contents: sections in order, each with its pages
     * in order.
     *
     * @return list<array{key: string, en: string, bn: string, pages: list<array{key: string, title_en: string, title_bn: string, status: string}>}>
     */
    public static function grouped(): array
    {
        $sections = (array) config('training.sections', []);
        uasort($sections, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        $pages = self::all();
        uasort($pages, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        $grouped = [];

        foreach ($sections as $sectionKey => $section) {
            $grouped[] = [
                'key' => $sectionKey,
                'en' => $section['en'],
                'bn' => $section['bn'],
                'pages' => array_values(array_map(
                    fn (array $page): array => [
                        'key' => $page['key'],
                        'title_en' => $page['title_en'],
                        'title_bn' => $page['title_bn'],
                        'status' => $page['status'],
                    ],
                    array_filter($pages, fn (array $page): bool => $page['section'] === $sectionKey),
                )),
            ];
        }

        return $grouped;
    }

    /**
     * Everything one guide needs to render: its entry, its screenshots' URLs,
     * and a link to the live screen when that screen takes no parameters.
     *
     * Each shot URL carries the file's modification time, so a recaptured
     * image is never served from a stale browser cache.
     *
     * @return array{key: string, guide: string, title_en: string, title_bn: string, status: string, section_en: string, section_bn: string, shots: array<string, array{description: string, url: string|null}>, screen_url: string|null}|null
     */
    public static function present(string $key): ?array
    {
        $page = self::get($key);

        if ($page === null) {
            return null;
        }

        $section = config("training.sections.{$page['section']}");
        $shots = [];

        foreach ($page['shots'] as $id => $description) {
            $file = self::shotPath($key, $id);

            $shots[$id] = [
                'description' => $description,
                'url' => is_file($file) ? asset(self::shotRelativeUrl($key, $id)).'?v='.filemtime($file) : null,
            ];
        }

        $route = Route::getRoutes()->getByName($page['route']);

        return [
            'key' => $key,
            'guide' => $page['guide'],
            'title_en' => $page['title_en'],
            'title_bn' => $page['title_bn'],
            'status' => $page['status'],
            'section_en' => $section['en'] ?? '',
            'section_bn' => $section['bn'] ?? '',
            'shots' => $shots,
            'screen_url' => $route !== null && $route->parameterNames() === [] ? route($page['route'], absolute: false) : null,
        ];
    }

    /**
     * The guide's `.tsx` file on disk.
     */
    public static function guidePath(string $guide): string
    {
        return base_path(config('training.guides_path').'/'.$guide.'.tsx');
    }

    /**
     * A screenshot's PNG on disk.
     */
    public static function shotPath(string $key, string $shot): string
    {
        return base_path(config('training.screens_path')."/{$key}/{$shot}.png");
    }

    /**
     * A screenshot's path under `public/`, for `asset()`.
     */
    public static function shotRelativeUrl(string $key, string $shot): string
    {
        return Str::after(config('training.screens_path'), 'public/')."/{$key}/{$shot}.png";
    }
}
