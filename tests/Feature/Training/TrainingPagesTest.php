<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Training — the manual, one guide, and the help dialog's panel
|--------------------------------------------------------------------------
|
| Open to every signed-in user with no permission: the person who cannot open
| a screen yet is the person who needs its guide (documentation/training.md).
|
*/

test('a user holding no permission opens the manual', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('training.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/index')
            ->has('sections', count(config('training.sections')))
            ->where('sections.1.key', 'admin')
            ->where('sections.1.pages.0.key', 'admin.users')
            ->where('sections.1.pages.0.status', 'written'));
});

test('a user holding no permission opens a guide to a screen they cannot open', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('training.show', 'admin.users'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/show')
            ->where('page.key', 'admin.users')
            ->where('page.guide', 'admin/users')
            ->where('page.title_bn', 'ব্যবহারকারী')
            ->where('page.screen_url', '/admin/users')
            ->has('page.shots.overview'));
});

test('a guide to a screen that takes a parameter offers no link to it', function () {
    // No registered screen takes a parameter today; the sent mail preview does.
    $pages = config('training.pages');
    $pages['admin.sent-mails.show'] = [...$pages['admin.sent-mails'], 'route' => 'admin.sent-mails.show'];
    config()->set('training.pages', $pages);

    $this->actingAs(User::factory()->create())
        ->get(route('training.show', 'admin.sent-mails.show'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('page.screen_url', null));
});

test('a pending guide still opens, so the manual can say it is coming', function () {
    // Every screen has a guide today; a new screen arrives pending. Page keys
    // hold dots, so the whole array is replaced rather than set by path.
    $pages = config('training.pages');
    $pages['admin.users']['status'] = 'pending';
    config()->set('training.pages', $pages);

    $this->actingAs(User::factory()->create())
        ->get(route('training.show', 'admin.users'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('page.status', 'pending'));
});

test('the help dialog reads the same guide as JSON', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('training.panel', 'admin.roles'))
        ->assertSuccessful()
        ->assertJsonPath('page.key', 'admin.roles')
        ->assertJsonPath('page.status', 'written')
        ->assertJsonStructure(['page' => ['shots' => ['overview' => ['description', 'url']]], 'sections']);
});

test('an unknown key is not found', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route, 'no.such.screen'))
        ->assertNotFound();
})->with(['training.show', 'training.panel']);

test('a guest is sent to log in', function () {
    $this->get(route('training.index'))->assertRedirect(route('login'));
});

test('a screen shares the guide that covers it, for the help button', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('training.key', 'dashboard')
            ->where('training.title_en', 'Dashboard and getting around')
            ->where('training.status', 'written'));
});

test('the manual itself has no guide, so the help button is hidden there', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('training.index'))
        ->assertInertia(fn (Assert $page) => $page->where('training', null));
});
