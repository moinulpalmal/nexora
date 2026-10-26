<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application, refusing to run against a real database.
     *
     * `RefreshDatabase` runs `migrate:fresh`, which drops every table, so it is
     * safe only against a throwaway database. This runs from
     * `refreshApplication()`, before `setUpTraits()` boots the trait, so a wrong
     * connection is caught while there is still a database to protect.
     *
     * The usual culprit is a cached `bootstrap/cache/config.php`: it makes
     * `phpunit.xml` inert, so the sqlite connection it declares silently becomes
     * the MySQL development database. `tests/Pest.php` deletes that file before
     * anything boots; this is the backstop for every other way it could happen.
     *
     * A sqlite *file* is allowed as well as `:memory:` because browser tests drive
     * a separate HTTP process that cannot see an in-memory database
     * (`phpunit.browser.xml`). The file must live under `database/`.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || ! $this->isThrowawayDatabase($app, $database)) {
            throw new RuntimeException(sprintf(
                'Refusing to run tests on connection [%s] (database "%s"). RefreshDatabase drops '
                .'every table, so tests run on sqlite — `:memory:` (phpunit.xml) or a .sqlite file '
                .'under database/ (phpunit.browser.xml). Something is overriding it — usually a '
                .'cached config, so run `php artisan config:clear`.',
                $connection,
                is_string($database) ? $database : 'unknown',
            ));
        }

        return $app;
    }

    /**
     * Whether the configured sqlite database is one `migrate:fresh` may safely drop.
     */
    private function isThrowawayDatabase(Application $app, mixed $database): bool
    {
        if ($database === ':memory:') {
            return true;
        }

        if (! is_string($database) || ! str_ends_with($database, '.sqlite')) {
            return false;
        }

        $directory = realpath($app->databasePath());

        return $directory !== false
            && str_starts_with(realpath(dirname($database)) ?: dirname($database), $directory);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
