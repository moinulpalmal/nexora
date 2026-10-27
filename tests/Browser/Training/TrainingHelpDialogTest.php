<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Training — the help dialog, the language toggle and the manual's search
|--------------------------------------------------------------------------
|
| What a DOM can answer that a feature test cannot: the header's ? opens the
| guide over the screen it describes, the dialog never light-dismisses
| (ARCHITECTURE.md §8.7), the Bangla choice survives a reload, and a search
| that hid a section brings it back when cleared (documentation/training.md).
|
*/

test('the help button opens the screen\'s guide in a dialog that only the ✕ closes', function () {
    $this->actingAs(userWithPermissions('admin.roles.view'));

    $page = visit(route('admin.roles.index', absolute: false))
        ->click('[data-test="training-help"]')
        ->assertSeeIn('[data-test="training-dialog-title"]', 'Roles')
        ->assertVisible('[data-test="training-section-purpose"]')
        ->assertVisible('[data-test="training-shot-overview"] img')
        ->assertNoJavaScriptErrors();

    // A backdrop click lands on the <dialog> itself, and does nothing (§8.7).
    $page->script("document.querySelector('dialog[open]').click()");
    $page->assertVisible('[data-test="training-dialog-title"]');

    $page->click('dialog[open] [aria-label="Close"]')
        ->assertMissing('[data-test="training-dialog-title"]')
        ->assertNoJavaScriptErrors();
});

test('a screen whose guide is pending says so in the dialog', function () {
    $this->actingAs(User::factory()->create());

    // Every screen has a guide today; a new screen arrives pending.
    $pages = config('training.pages');
    $pages['dashboard']['status'] = 'pending';
    config()->set('training.pages', $pages);

    visit(route('dashboard', absolute: false))
        ->click('[data-test="training-help"]')
        ->assertVisible('[data-test="training-not-documented"]')
        ->assertNoJavaScriptErrors();
});

test('the manual has no help button of its own', function () {
    $this->actingAs(User::factory()->create());

    visit(route('training.index', absolute: false))
        ->assertMissing('[data-test="training-help"]')
        ->assertNoJavaScriptErrors();
});

test('choosing Bangla switches the guide and survives a reload', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('training.show', 'admin.users', absolute: false))
        ->assertSeeIn('[data-test="training-title"]', 'Users')
        ->click('[data-test="training-lang-bn"]')
        ->assertSeeIn('[data-test="training-title"]', 'ব্যবহারকারী')
        ->assertSeeIn('[data-test="training-section-purpose"]', 'এই স্ক্রিনটি কী কাজে লাগে');

    $page->refresh()
        ->assertSeeIn('[data-test="training-title"]', 'ব্যবহারকারী')
        ->click('[data-test="training-lang-en"]')
        ->assertSeeIn('[data-test="training-title"]', 'Users')
        ->assertNoJavaScriptErrors();
});

test('searching the manual narrows it, and clearing the search brings every section back', function () {
    $this->actingAs(User::factory()->create());

    $sections = count(config('training.sections'));
    $pages = count(config('training.pages'));

    $page = visit(route('training.index', absolute: false))
        ->assertCount('[data-test="training-toc-section"]', $sections)
        ->type('[data-test="training-search"]', 'roles')
        ->assertCount('[data-test="training-toc-item"]', 1)
        ->assertCount('[data-test="training-toc-section"]', 1);

    // A Bangla title matches too, whichever language is showing.
    $page->clear('[data-test="training-search"]')
        ->type('[data-test="training-search"]', 'প্রোফাইল')
        ->assertCount('[data-test="training-toc-section"]', 1);

    $page->clear('[data-test="training-search"]')
        ->assertCount('[data-test="training-toc-section"]', $sections)
        ->assertCount('[data-test="training-toc-item"]', $pages)
        ->assertNoJavaScriptErrors();
});
