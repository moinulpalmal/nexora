<?php

use App\Console\Commands\SyncEnums;
use App\Contracts\IdentifiedEnum;
use App\Enums\EnumRegistry;
use App\Enums\Theme;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| The enum registry
|--------------------------------------------------------------------------
|
| Two things this suite has to prove, since nothing else will:
|
| 1. Every enum under app/Enums/ is actually registered — EnumRegistry::CLASSES
|    is hand-maintained, so an enum added and forgotten here would otherwise
|    exist, compile, and simply never be resolvable by id.
| 2. The committed resources/js/lib/enums.ts matches what the generator would
|    write today — the drift gate. `php artisan enums:sync` regenerates it;
|    this test is what makes forgetting to run that after an enum change a
|    failing test rather than a silently stale frontend mirror.
|
*/

/**
 * Every `IdentifiedEnum` class actually on disk under app/Enums/, found by
 * scanning the filesystem independently of the hand-maintained registry —
 * this is what lets the two be compared against each other.
 *
 * @return list<class-string<IdentifiedEnum>>
 */
function enumClassesOnDisk(): array
{
    $classes = [];

    foreach (Finder::create()->files()->name('*.php')->in(app_path('Enums')) as $file) {
        $relative = str_replace('/', '\\', $file->getRelativePathname());
        $class = 'App\\Enums\\'.substr($relative, 0, -4);

        if (enum_exists($class) && is_a($class, IdentifiedEnum::class, allow_string: true)) {
            $classes[] = $class;
        }
    }

    sort($classes);

    return $classes;
}

test('every IdentifiedEnum under app/Enums/ is registered', function () {
    $onDisk = enumClassesOnDisk();
    $registered = EnumRegistry::classes();
    sort($registered);

    expect($onDisk)->not->toBeEmpty()
        ->and($registered)->toEqual($onDisk);
});

test('every registered class is actually an IdentifiedEnum', function (string $class) {
    expect(is_a($class, IdentifiedEnum::class, allow_string: true))->toBeTrue();
})->with(fn () => EnumRegistry::classes());

test('every id is unique and kebab-case', function () {
    $ids = array_map(fn (string $class) => $class::id(), EnumRegistry::classes());

    expect($ids)->toEqual(array_unique($ids));

    foreach ($ids as $id) {
        expect($id)->toMatch('/^[a-z][a-z0-9-]*$/');
    }
});

test('for() resolves a registered id and throws on an unknown one', function () {
    expect(EnumRegistry::for('theme'))->toBe(Theme::class);

    EnumRegistry::for('does-not-exist');
})->throws(InvalidArgumentException::class, 'No enum is registered under id [does-not-exist].');

test('toArray() marks an unlabelled enum with null labels and describes a labelled one', function () {
    $byId = collect(EnumRegistry::toArray())->keyBy('id');

    expect($byId['filter-type']['labelled'])->toBeFalse()
        ->and(collect($byId['filter-type']['cases'])->pluck('label')->unique()->all())->toBe([null])
        ->and($byId['theme']['labelled'])->toBeTrue()
        ->and($byId['theme']['group'])->toBe('General')
        ->and($byId['theme']['cases'][0])->toBe(['case' => 'System', 'value' => 'system', 'label' => 'System']);
});

/**
 * `SyncEnums::render()` produces valid but not necessarily prettier-formatted
 * TypeScript — `php artisan enums:sync` pipes it through `npx prettier
 * --write` for that. Piping it through prettier here too (rather than a
 * whitespace-only comparison) means this test does not have to hand-emulate
 * prettier's own choices, e.g. its `quote-props: as-needed` dropping quotes
 * from `light:` while keeping them on a key like `'with-dash':`.
 */
function prettierFormatted(string $content): string
{
    $result = Process::path(base_path())
        ->input($content)
        ->run(['npx', 'prettier', '--stdin-filepath', 'resources/js/lib/enums.ts']);

    expect($result->successful())
        ->toBeTrue("npx prettier failed: {$result->errorOutput()}");

    return $result->output();
}

test('the generated resources/js/lib/enums.ts matches what the generator would write today', function () {
    $committed = file_get_contents(resource_path('js/lib/enums.ts'));

    expect(prettierFormatted($committed))->toBe(prettierFormatted(SyncEnums::render()));
})->skip(fn () => ! file_exists(resource_path('js/lib/enums.ts')), 'resources/js/lib/enums.ts has not been generated yet — run `php artisan enums:sync`.');
