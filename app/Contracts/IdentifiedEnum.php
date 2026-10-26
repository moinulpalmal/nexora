<?php

namespace App\Contracts;

use App\Enums\EnumRegistry;

/**
 * A backed enum with a stable, kebab-case identity of its own.
 *
 * The id is **not** the enum's backed value — `RecordStatus::Active->value` is
 * `'A'`, the character the tables store; its id is `'record-status'`, the
 * class's own slug. The id exists so {@see EnumRegistry} can resolve a class by
 * name, and so `php artisan enums:sync` can name the generated TypeScript block.
 *
 * Every enum under `app/Enums/` implements this. Enums that are never shown to
 * a person stop here; the rest implement {@see LabelledEnum}.
 */
interface IdentifiedEnum extends \BackedEnum
{
    /**
     * This enum's stable identity, e.g. `'record-status'`.
     *
     * Kebab-case, matching the route and permission vocabulary. Checked for
     * uniqueness and format by `tests/Feature/EnumRegistryTest.php`.
     */
    public static function id(): string;
}
