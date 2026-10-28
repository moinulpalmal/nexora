<?php

namespace App\Services\Admin;

use App\Models\ImportLimit;
use App\Services\Imports\ImportRunner;

/**
 * The write path for Application Settings → Import Limits.
 *
 * Write only: the engine reads `ImportLimit::current()` directly
 * ({@see ImportRunner::stage()}), because injecting an Admin service into the
 * module-less engine is the coupling ARCHITECTURE.md §4 exists to prevent.
 */
class ImportLimitService
{
    /**
     * Save the caps, creating the single row on the first save — the
     * `QuickLoginService::update()` shape.
     *
     * @param  array{max_rows: int, max_columns: int, max_sheets: int}  $attributes
     */
    public function update(array $attributes): ImportLimit
    {
        $limits = ImportLimit::query()->firstOrNew(['id' => ImportLimit::SINGLETON_ID]);

        $limits->fill($attributes)->save();

        return $limits;
    }
}
