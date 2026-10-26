<?php

namespace App\Enums;

use App\Contracts\IdentifiedEnum;
use App\Contracts\LabelledEnum;
use InvalidArgumentException;

/**
 * The one file that lists every enum in this application.
 *
 * Each enum still lives in its own PSR-4 file; this is the single place that
 * names all of them and resolves any one by its stable
 * {@see IdentifiedEnum::id()}. `php artisan enums:sync` generates
 * `resources/js/lib/enums.ts` from it.
 *
 * `CLASSES` is hand-maintained on purpose, so an enum's presence here is a
 * decision rather than a side effect of where its file sits.
 * `tests/Feature/EnumRegistryTest.php` scans `app/Enums/` independently and
 * fails if a class is missing.
 *
 * This is the one non-enum class inside `app/Enums/`: it is the registry *of*
 * the enums, and a single file to open is the point.
 */
final class EnumRegistry
{
    /**
     * Every enum, grouped the way `app/Enums/` is on disk: module-less enums
     * first, then each module alphabetically.
     *
     * @var list<class-string<IdentifiedEnum>>
     */
    private const array CLASSES = [
        // Module-less — app/Enums/ root
        FilterType::class,
        Theme::class,
    ];

    /**
     * Every registered enum class, in display order.
     *
     * @return list<class-string<IdentifiedEnum>>
     */
    public static function classes(): array
    {
        return self::CLASSES;
    }

    /**
     * Every registered enum class, keyed by its own {@see IdentifiedEnum::id()}.
     *
     * @return array<string, class-string<IdentifiedEnum>>
     */
    public static function map(): array
    {
        $map = [];

        foreach (self::CLASSES as $class) {
            $map[$class::id()] = $class;
        }

        return $map;
    }

    /**
     * Resolve an enum class by its id.
     *
     * @return class-string<IdentifiedEnum>
     *
     * @throws InvalidArgumentException when no registered enum carries this id.
     */
    public static function for(string $id): string
    {
        return self::map()[$id]
            ?? throw new InvalidArgumentException("No enum is registered under id [{$id}].");
    }

    /**
     * Every registered enum, described for the generator.
     *
     * `label` is `null` for an enum that implements {@see IdentifiedEnum}
     * without {@see LabelledEnum}: nothing renders it to a person.
     *
     * @return list<array{
     *     class: class-string<IdentifiedEnum>,
     *     id: string,
     *     name: string,
     *     group: string,
     *     labelled: bool,
     *     cases: list<array{case: string, value: int|string, label: ?string}>,
     * }>
     */
    public static function toArray(): array
    {
        return array_map(function (string $class): array {
            $labelled = is_a($class, LabelledEnum::class, allow_string: true);

            return [
                'class' => $class,
                'id' => $class::id(),
                'name' => class_basename($class),
                'group' => self::groupFor($class),
                'labelled' => $labelled,
                'cases' => array_map(
                    fn (IdentifiedEnum $case): array => [
                        'case' => $case->name,
                        'value' => $case->value,
                        'label' => $case instanceof LabelledEnum ? $case->label() : null,
                    ],
                    $class::cases(),
                ),
            ];
            // Through the typed accessor, not the constant: PHPStan reads a short
            // literal constant as `array<0|1, …>`, which it cannot prove is a list.
        }, self::classes());
    }

    /**
     * The namespace segment between `App\Enums\` and the class name — `Admin`,
     * `Settings`, or `General` for the module-less root.
     */
    private static function groupFor(string $class): string
    {
        $lastSeparator = strrpos($class, '\\');
        $namespace = $lastSeparator === false ? $class : substr($class, 0, $lastSeparator);

        return $namespace === __NAMESPACE__
            ? 'General'
            : substr($namespace, strlen(__NAMESPACE__) + 1);
    }
}
