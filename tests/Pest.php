<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Cached bootstrap artifacts are removed before anything boots
|--------------------------------------------------------------------------
|
| A cached config makes every `<env>` entry in `phpunit.xml` inert, because
| `LoadConfiguration` never reads the environment when the cache exists. The
| sqlite `:memory:` connection then silently becomes the MySQL development
| database, and `RefreshDatabase` drops every table in it. The same cache bakes
| `app.env => local`, so non-GET requests fail with 419. The route cache goes for
| the same reason: cached routes hide newly added ones from the suite.
|
| A cached config is invalid for a test run by definition, so it is deleted rather
| than refused. `TestCase::createApplication()` is the backstop.
|
*/

$cachedArtifacts = [
    'config' => $_ENV['APP_CONFIG_CACHE'] ?? __DIR__.'/../bootstrap/cache/config.php',
    'route' => __DIR__.'/../bootstrap/cache/routes-v7.php',
];

foreach ($cachedArtifacts as $kind => $cachedPath) {
    if (! is_file($cachedPath)) {
        continue;
    }

    unlink($cachedPath);

    // STDERR, so the notice never lands in `--compact`'s JSON on stdout.
    fwrite(STDERR, sprintf(
        '[tests] Removed a cached %s file (%s). It would have made phpunit.xml inert.%s',
        $kind,
        basename($cachedPath),
        PHP_EOL,
    ));
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

// Browser tests share the Feature binding; only the connection differs, and
// `phpunit.browser.xml` supplies that.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
