<?php

use App\Services\Training\TrainingGapFinder;

/*
|--------------------------------------------------------------------------
| Training — the gate
|--------------------------------------------------------------------------
|
| The suite fails on any training gap (owner, 2026-10-27): a screen with no
| registry entry, a guide pointing at a dead route, a written guide missing
| its file, a screenshot, a language or its shape. A new screen nobody has
| written a guide for yet is registered `pending`, which passes.
|
| `php artisan training:gaps` prints the same list with what to do about
| each one.
|
*/

/**
 * Change one registry entry. Page keys hold dots, so the whole array is
 * replaced rather than set by path.
 *
 * @param  array<string, mixed>|null  $entry  null removes the entry
 */
function changeTrainingPage(string $key, ?array $entry): void
{
    $pages = config('training.pages');

    if ($entry === null) {
        unset($pages[$key]);
    } else {
        $pages[$key] = [...$pages[$key], ...$entry];
    }

    config()->set('training.pages', $pages);
}

/**
 * @return list<string>
 */
function trainingGapChecks(string $subject): array
{
    return collect(app(TrainingGapFinder::class)->find())->where('subject', $subject)->pluck('check')->values()->all();
}

test('the training module has no gaps', function () {
    $gaps = app(TrainingGapFinder::class)->find();

    expect($gaps)->toBe([], collect($gaps)
        ->map(fn (array $gap): string => "[{$gap['check']}] {$gap['subject']}: {$gap['message']}")
        ->prepend('Training gaps — run php artisan training:gaps:')
        ->implode(PHP_EOL));
});

test('a new screen with no entry is a gap', function () {
    changeTrainingPage('admin.permissions', null);

    // Its committed screenshot is now declared by nobody, so it is an orphan too.
    expect(trainingGapChecks('admin.permissions.index'))->toBe(['unregistered-screen'])
        ->and(trainingGapChecks('admin.permissions/overview'))->toBe(['orphan-shot']);
});

test('an entry naming a route that no longer exists is a gap', function () {
    changeTrainingPage('admin.permissions', ['route' => 'admin.permissions.gone']);

    expect(trainingGapChecks('admin.permissions'))->toBe(['dead-route']);
});

test('a written entry without its guide file or overview is a gap', function () {
    changeTrainingPage('admin.permissions', ['guide' => 'admin/no-such-guide', 'shots' => []]);

    expect(trainingGapChecks('admin.permissions'))->toBe(['missing-guide', 'missing-overview']);
});

test('a pending entry whose guide exists is a gap, so its status is not forgotten', function () {
    changeTrainingPage('security.edit', ['status' => 'pending']);

    expect(trainingGapChecks('security.edit'))->toBe(['unflipped-status']);
});

test('a shot the guide shows but the entry does not declare is a gap, and the reverse', function () {
    $shots = config('training.pages')['admin.users']['shots'];
    unset($shots['deleted']);
    changeTrainingPage('admin.users', ['shots' => [...$shots, 'spare' => 'Never shown']]);

    // Its committed PNG is now declared by nobody, so it is an orphan as well.
    expect(trainingGapChecks('admin.users/deleted'))->toBe(['undeclared-shot', 'orphan-shot'])
        ->and(trainingGapChecks('admin.users/spare'))->toContain('unused-shot');
});

test('the gaps command lists the registry and exits successfully', function () {
    $this->artisan('training:gaps')
        ->expectsOutputToContain('registered screens')
        ->assertSuccessful();
});
