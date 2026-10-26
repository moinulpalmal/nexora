<?php

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
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
    ->beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions())
    // A closure, not `fakeBreachedPasswordLookup(...)`: Pest rebinds each
    // `beforeEach` to the test case, and a first-class callable made from a
    // global function has no scope to rebind.
    ->beforeEach(function () {
        fakeBreachedPasswordLookup();
    })
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

/**
 * A user holding exactly the given permissions, directly rather than via a role.
 *
 * The permissions are created on the fly, so a test names the abilities it
 * needs without depending on the seeded catalogue.
 */
function userWithPermissions(string ...$permissions): User
{
    $user = User::factory()->create();

    $user->givePermissionTo(array_map(
        fn (string $name): Permission => Permission::findOrCreate($name, 'web'),
        $permissions,
    ));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

/**
 * A user holding the super-admin role, which bypasses every permission check.
 */
function superAdmin(): User
{
    $user = User::factory()->create();

    $user->assignRole(Role::findOrCreate(Role::SUPER_ADMIN, 'web'));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

/**
 * A password that satisfies every rule in `config/auth.password_policy`.
 *
 * The factory password is `password`, which this deliberately is not: it fails
 * four of the six rules and is in every breach corpus there is.
 */
function compliantPassword(): string
{
    return 'Str0ng-Pass!word';
}

/**
 * Answer every Have I Been Pwned range lookup with "not found".
 *
 * The password policy includes `uncompromised` in every environment, so every
 * password-setting test would otherwise make a live HTTPS call — slow, broken
 * offline, and worse, failing **open**: `NotPwnedVerifier` treats a network
 * error as "not compromised", so the assertion would pass without checking
 * anything.
 *
 * An empty 200 body is a valid range response with no matching suffix. Pass
 * `$breached` to get the opposite: the body then carries that password's own
 * SHA-1 suffix, which is what a hit looks like.
 *
 * The factory is swapped rather than re-faked because `Http::fake()` *merges*
 * stubs and the first match wins — a second call would be silently overruled
 * by the suite-wide stub. Other hosts fall through to the real handler.
 */
function fakeBreachedPasswordLookup(?string $breached = null): void
{
    Http::swap(new Factory(app('events')));

    $body = $breached === null
        ? ''
        : substr(strtoupper(sha1($breached)), 5).':42';

    Http::fake(fn (Request $request) => str_contains($request->url(), 'api.pwnedpasswords.com')
        ? Http::response($body, 200)
        : null);
}
