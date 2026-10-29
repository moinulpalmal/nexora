<?php

namespace App\Services\Imports;

use App\Services\Hrm\DepartmentImportBlueprint;
use App\Services\Hrm\DesignationImportBlueprint;
use App\Services\Hrm\EmployeeImportBlueprint;
use RuntimeException;

/**
 * Every {@see ImportBlueprint} the application ships, keyed by
 * {@see ImportBlueprint::key()}.
 *
 * The one place a model opts in — the mirror of `AppServiceProvider::MORPH_MAP`
 * for this engine: a single map read by the controller, the confirm request and
 * the runner, so `sheet_imports.import_key` always resolves to a real blueprint
 * or fails loudly rather than silently (ARCHITECTURE.md §9.13).
 *
 * The engine itself is proved by
 * test-only blueprints, which the feature tests register by binding a subclass
 * that overrides {@see self::BLUEPRINTS} — the reason {@see self::resolve()}
 * reads it through `static::`.
 */
class ImportRegistry
{
    /**
     * @var array<string, class-string<ImportBlueprint>>
     */
    protected const array BLUEPRINTS = [
        'hrm.departments' => DepartmentImportBlueprint::class,
        'hrm.designations' => DesignationImportBlueprint::class,
        'hrm.employees' => EmployeeImportBlueprint::class,
    ];

    /**
     * Resolve a blueprint by its key.
     *
     * @throws RuntimeException when the key names nothing registered
     */
    public function resolve(string $key): ImportBlueprint
    {
        $class = static::BLUEPRINTS[$key] ?? null;

        if ($class === null) {
            throw new RuntimeException("No import blueprint is registered for [{$key}].");
        }

        return app($class);
    }
}
