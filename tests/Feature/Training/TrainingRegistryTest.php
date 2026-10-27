<?php

use App\Services\Training\TrainingRegistry;

/*
|--------------------------------------------------------------------------
| Training — reading the registry
|--------------------------------------------------------------------------
*/

test('a route resolves to the guide that covers it', function () {
    expect(TrainingRegistry::keyForRoute('admin.users.index'))->toBe('admin.users')
        ->and(TrainingRegistry::keyForRoute('profile.edit'))->toBe('profile.edit')
        ->and(TrainingRegistry::keyForRoute('admin.users.availability'))->toBeNull()
        ->and(TrainingRegistry::keyForRoute(null))->toBeNull();
});

test('the table of contents follows the sidebar: its groups in order, and their screens in order', function () {
    $grouped = TrainingRegistry::grouped();

    expect(array_column($grouped, 'key'))->toBe(['general', 'admin', 'settings'])
        ->and(array_sum(array_map(fn (array $section): int => count($section['pages']), $grouped)))->toBe(count(config('training.pages')))
        ->and(array_column($grouped[1]['pages'], 'key'))->toBe([
            'admin.users', 'admin.roles', 'admin.permissions', 'admin.audit-logs', 'admin.sent-mails', 'admin.application-settings',
        ])
        ->and(array_column($grouped[2]['pages'], 'key'))->toBe(['profile.edit', 'security.edit', 'appearance.edit']);
});

test('the shared prop carries only what the help button needs', function () {
    expect(TrainingRegistry::forRoute('admin.users.index'))->toBe([
        'key' => 'admin.users',
        'title_en' => 'Users',
        'title_bn' => 'ব্যবহারকারী',
        'status' => 'written',
    ]);
});

test('a screenshot not yet captured has no url', function () {
    // Page keys hold dots, so the whole array is replaced rather than set by path.
    $pages = config('training.pages');
    $pages['admin.users']['shots'] = ['not-taken' => 'Never captured'];
    config()->set('training.pages', $pages);

    expect(TrainingRegistry::present('admin.users')['shots']['not-taken'])
        ->toBe(['description' => 'Never captured', 'url' => null]);
});
