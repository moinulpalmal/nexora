<?php

use App\Models\Admin\Role;

/*
|--------------------------------------------------------------------------
| Lists heal themselves, driven in a real browser
|--------------------------------------------------------------------------
|
| `hooks/use-list-filters.ts` and `list-toolbar.tsx` are shared by every list
| (ARCHITECTURE.md §8.6), so this is proved once, on `admin/roles`. What a
| feature test cannot answer: whether a row written by someone else appears
| when Refresh is clicked, the window regains focus or a dialog opens — and
| whether the filter survives it.
|
*/

test('refresh shows a row inserted after the page loaded', function () {
    $this->actingAs(userWithPermissions('admin.roles.view'));
    Role::findOrCreate('cutting-master', 'web');

    $page = visit('/admin/roles')
        ->assertSee('cutting-master')
        ->assertDontSee('finishing-manager');

    Role::findOrCreate('finishing-manager', 'web');

    $page->click('[data-test="refresh-list"]')
        ->waitForText('finishing-manager')
        ->assertNoJavaScriptErrors();
});

test('refresh preserves the active filter', function () {
    $this->actingAs(userWithPermissions('admin.roles.view'));
    Role::findOrCreate('cutting-master', 'web');
    Role::findOrCreate('finishing-manager', 'web');

    $page = visit('/admin/roles')
        ->fill('input[aria-label="Filter by role"]', 'cutting')
        ->waitForText('cutting-master')
        ->assertDontSee('finishing-manager');

    $page->click('[data-test="refresh-list"]')
        ->assertSee('cutting-master')
        ->assertDontSee('finishing-manager')
        ->assertNoJavaScriptErrors();

    expect((string) $page->script(<<<'JS'
        (() => document.querySelector('input[aria-label="Filter by role"]')?.value ?? '')();
    JS))->toBe('cutting');
});

test('the window regaining focus refreshes the list on its own', function () {
    $this->actingAs(userWithPermissions('admin.roles.view'));
    Role::findOrCreate('cutting-master', 'web');

    $page = visit('/admin/roles')->assertSee('cutting-master');

    Role::findOrCreate('finishing-manager', 'web');

    $page->script("window.dispatchEvent(new Event('focus'));");

    $page->waitForText('finishing-manager')
        ->assertNoJavaScriptErrors();
});

/*
 * Focus is incidental (a second monitor, devtools), so it is throttled where
 * the deliberate triggers are not.
 */
test('a second focus inside the throttle window does not refresh again', function () {
    $this->actingAs(userWithPermissions('admin.roles.view'));
    Role::findOrCreate('cutting-master', 'web');

    $page = visit('/admin/roles')->assertSee('cutting-master');

    // Sets the clock the throttle measures from.
    $page->click('[data-test="refresh-list"]')->assertSee('cutting-master');

    Role::findOrCreate('finishing-manager', 'web');

    $page->script("window.dispatchEvent(new Event('focus'));");

    // Long enough for a visit to have landed had one been made.
    usleep(1_000_000);

    $page->assertDontSee('finishing-manager')
        ->assertNoJavaScriptErrors();
});

/*
 * A form modal must never open against data that moved while the page sat
 * there — unthrottled, because opening a modal is deliberate.
 */
test('opening a dialog refreshes the list behind it', function () {
    $this->actingAs(userWithPermissions('admin.roles.view', 'admin.roles.create'));
    Role::findOrCreate('cutting-master', 'web');

    $page = visit('/admin/roles')->assertDontSee('finishing-manager');

    Role::findOrCreate('finishing-manager', 'web');

    $page->click('[data-test="new-role"]')
        ->waitForText('finishing-manager')
        ->assertNoJavaScriptErrors();
});
