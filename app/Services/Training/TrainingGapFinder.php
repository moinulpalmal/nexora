<?php

namespace App\Services\Training;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Every way the training module can drift from the application — see
 * documentation/training.md §5. Shared by `php artisan training:gaps`, which
 * lists the gaps, and `TrainingGapsTest`, which fails the suite on any.
 *
 * All the checks are structural. None of them can tell that a guide's prose
 * still describes behaviour a screen no longer has; only reading the guide
 * against the screen finds that.
 */
class TrainingGapFinder
{
    /**
     * The fixed guide shape — the same order as `SECTION_HEADINGS` in
     * resources/js/components/training/vocabulary.tsx.
     *
     * @var list<string>
     */
    public const SECTION_ORDER = ['purpose', 'who', 'before', 'steps', 'fields', 'messages', 'mistakes', 'related'];

    /**
     * @return list<array{check: string, subject: string, message: string}>
     */
    public function find(): array
    {
        return [
            ...$this->unregisteredScreens(),
            ...$this->deadRoutes(),
            ...$this->unknownSections(),
            ...$this->guideFiles(),
            ...$this->shots(),
            ...$this->languagePairs(),
            ...$this->guideShape(),
            ...$this->orphanShots(),
        ];
    }

    /**
     * A named GET route of this application's own controllers that is neither
     * registered nor listed as a non-screen. A new screen lands here until it
     * is given an entry — `pending` is enough.
     *
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function unregisteredScreens(): array
    {
        $registered = array_column(TrainingRegistry::all(), 'route');
        $ignored = (array) config('training.ignore_routes', []);
        $gaps = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null
                || ! in_array('GET', $route->methods(), true)
                || ! str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\')
                || in_array($name, $registered, true)
                || in_array($name, $ignored, true)) {
                continue;
            }

            $gaps[] = $this->gap('unregistered-screen', $name, 'GET route has no training entry. Register it in config/training.php (status pending is enough), or list it in ignore_routes if it is not a screen.');
        }

        return $gaps;
    }

    /**
     * An entry, or an ignore line, naming a route that no longer exists. The
     * entry's guide could never be reached from the screen it describes.
     *
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function deadRoutes(): array
    {
        $routes = Route::getRoutes();
        $gaps = [];

        foreach (TrainingRegistry::all() as $key => $page) {
            if (! $routes->hasNamedRoute($page['route'])) {
                $gaps[] = $this->gap('dead-route', $key, "Names route [{$page['route']}], which does not exist.");
            }
        }

        foreach ((array) config('training.ignore_routes', []) as $name) {
            if (! $routes->hasNamedRoute($name)) {
                $gaps[] = $this->gap('dead-route', $name, 'Listed in ignore_routes, but no such route exists. Remove the line.');
            }
        }

        return $gaps;
    }

    /**
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function unknownSections(): array
    {
        $sections = array_keys((array) config('training.sections', []));
        $gaps = [];

        foreach (TrainingRegistry::all() as $key => $page) {
            if (! in_array($page['section'], $sections, true)) {
                $gaps[] = $this->gap('unknown-section', $key, "Section [{$page['section']}] is not one of config/training.php's sections.");
            }

            if (! in_array($page['status'], ['written', 'pending'], true)) {
                $gaps[] = $this->gap('unknown-status', $key, "Status [{$page['status']}] must be written or pending.");
            }
        }

        return $gaps;
    }

    /**
     * A written entry whose guide file is missing, or a pending one whose file
     * already exists — its status was never flipped, so it still says "not
     * yet documented".
     *
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function guideFiles(): array
    {
        $gaps = [];

        foreach (TrainingRegistry::all() as $key => $page) {
            $exists = is_file(TrainingRegistry::guidePath($page['guide']));

            if ($page['status'] === 'written' && ! $exists) {
                $gaps[] = $this->gap('missing-guide', $key, "Marked written, but {$page['guide']}.tsx does not exist.");
            }

            if ($page['status'] === 'pending' && $exists) {
                $gaps[] = $this->gap('unflipped-status', $key, "{$page['guide']}.tsx exists, but the entry is still pending. Set status to written.");
            }
        }

        return $gaps;
    }

    /**
     * A written guide's screenshots: it has an overview, every shot it shows
     * is declared, every declared shot is shown, and every one has its PNG.
     *
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function shots(): array
    {
        $gaps = [];

        foreach (TrainingRegistry::all() as $key => $page) {
            if ($page['status'] !== 'written') {
                continue;
            }

            $declared = array_keys($page['shots']);

            if (! in_array('overview', $declared, true)) {
                $gaps[] = $this->gap('missing-overview', $key, 'A written guide needs an overview shot.');
            }

            foreach ($declared as $shot) {
                if (! is_file(TrainingRegistry::shotPath($key, $shot))) {
                    $gaps[] = $this->gap('missing-shot', "{$key}/{$shot}", 'Declared, but the PNG has not been captured. Run composer training:screens.');
                }
            }

            $file = TrainingRegistry::guidePath($page['guide']);

            if (! is_file($file)) {
                continue;
            }

            preg_match_all('/<Shot\s+id="([a-z0-9-]+)"/', (string) file_get_contents($file), $matches);
            $used = array_unique($matches[1]);

            foreach (array_diff($used, $declared) as $shot) {
                $gaps[] = $this->gap('undeclared-shot', "{$key}/{$shot}", "{$page['guide']}.tsx shows this shot, but the entry does not declare it.");
            }

            foreach (array_diff($declared, $used) as $shot) {
                $gaps[] = $this->gap('unused-shot', "{$key}/{$shot}", "Declared, but {$page['guide']}.tsx never shows it.");
            }
        }

        return $gaps;
    }

    /**
     * A guide file whose English and Bangla do not pair up — every file under
     * the guides folder, so a fragment several guides share is held to it too.
     * TypeScript already refuses a vocabulary element missing either prop;
     * this catches what a type cannot — an explanation map written in one
     * language only.
     *
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function languagePairs(): array
    {
        $root = base_path(config('training.guides_path'));

        if (! is_dir($root)) {
            return [];
        }

        $gaps = [];

        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'tsx') {
                continue;
            }

            $source = $file->getContents();
            $english = preg_match_all('/\ben[=:]/', $source);
            $bangla = preg_match_all('/\bbn[=:]/', $source);

            if ($english !== $bangla) {
                $subject = str_replace('\\', '/', $file->getRelativePath().'/'.$file->getFilenameWithoutExtension());
                $gaps[] = $this->gap('language-pair', ltrim($subject, '/'), "{$english} English blocks against {$bangla} Bangla. Every block is written in both.");
            }
        }

        return $gaps;
    }

    /**
     * The fixed shape every guide follows (owner, 2026-10-27): sections may be
     * left out, never reordered or repeated. And every related guide it links
     * to must be registered.
     *
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function guideShape(): array
    {
        $gaps = [];
        $checked = [];

        foreach (TrainingRegistry::all() as $page) {
            $file = TrainingRegistry::guidePath($page['guide']);

            if (isset($checked[$file]) || ! is_file($file)) {
                continue;
            }

            $checked[$file] = true;
            $source = (string) file_get_contents($file);

            preg_match_all('/<GuideSection\s+kind="([a-z]+)"/', $source, $matches);
            $kinds = $matches[1];
            $positions = array_map(fn (string $kind): int|false => array_search($kind, self::SECTION_ORDER, true), $kinds);

            if (in_array(false, $positions, true)) {
                $gaps[] = $this->gap('guide-shape', $page['guide'], 'Uses a section kind that is not one of: '.implode(', ', self::SECTION_ORDER).'.');
            } elseif ($positions !== array_values(array_unique($positions)) || $positions !== $this->sorted($positions)) {
                $gaps[] = $this->gap('guide-shape', $page['guide'], 'Sections out of order or repeated. The order is: '.implode(', ', self::SECTION_ORDER).'.');
            }

            preg_match_all('/<RelatedGuide\s+to="([a-z0-9.-]+)"/', $source, $related);

            foreach ($related[1] as $key) {
                if (TrainingRegistry::get($key) === null) {
                    $gaps[] = $this->gap('dead-related', $page['guide'], "Links to guide [{$key}], which is not registered.");
                }
            }
        }

        return $gaps;
    }

    /**
     * @param  list<int|false>  $positions
     * @return list<int|false>
     */
    private function sorted(array $positions): array
    {
        sort($positions);

        return $positions;
    }

    /**
     * A PNG no written entry declares — left behind by a renamed key or shot.
     *
     * @return list<array{check: string, subject: string, message: string}>
     */
    private function orphanShots(): array
    {
        $root = base_path(config('training.screens_path'));

        if (! is_dir($root)) {
            return [];
        }

        $gaps = [];

        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'png') {
                continue;
            }

            $key = str_replace('\\', '/', $file->getRelativePath());
            $shot = $file->getFilenameWithoutExtension();
            $page = TrainingRegistry::get($key);

            if ($page === null || $page['status'] !== 'written' || ! array_key_exists($shot, $page['shots'])) {
                $gaps[] = $this->gap('orphan-shot', "{$key}/{$shot}", 'No written entry declares this screenshot. Delete it.');
            }
        }

        return $gaps;
    }

    /**
     * @return array{check: string, subject: string, message: string}
     */
    private function gap(string $check, string $subject, string $message): array
    {
        return ['check' => $check, 'subject' => $subject, 'message' => $message];
    }
}
