<?php

use App\Enums\Theme;

/*
|--------------------------------------------------------------------------
| Picking a theme, in a real browser
|--------------------------------------------------------------------------
|
| Two things no feature test sees (ARCHITECTURE.md §9.5): that a choice painted
| optimistically was also stored, so it survives a reload; and that
| `<ThemeSync />` adopts a stored theme the screen disagrees with on the next
| Inertia visit. Both assert on `<html data-theme>`, because that attribute is
| the feature — daisyUI resolves every colour from it.
|
| `html[lang]` rather than `html`: the plugin treats a bare tag name as text to
| find, not as CSS.
|
*/

test('a chosen theme is painted at once and survives a reload', function () {
    $this->actingAs(userWithPermissions());

    visit('/settings/appearance')
        ->click('Dracula')
        ->assertDataAttribute('html[lang]', 'theme', Theme::Dracula->value)
        ->refresh()
        ->assertDataAttribute('html[lang]', 'theme', Theme::Dracula->value)
        ->assertNoJavaScriptErrors();
});

/**
 * The stored theme changes behind the browser's back, then an ordinary in-app
 * navigation carries it in the shared props — standing in for a lost save,
 * which the plugin cannot provoke.
 */
test('the client adopts the stored theme when it disagrees with what is painted', function () {
    $user = userWithPermissions();
    $this->actingAs($user);

    $page = visit('/settings/appearance')
        ->click('Nord')
        ->assertDataAttribute('html[lang]', 'theme', Theme::Nord->value);

    $user->forceFill(['theme' => Theme::Forest])->save();

    $page->click('Profile')
        ->assertPathIs('/settings/profile')
        ->assertDataAttribute('html[lang]', 'theme', Theme::Forest->value)
        ->assertNoJavaScriptErrors();
});
