<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportLimitUpdateRequest;
use App\Services\Admin\ImportLimitService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Application Settings → Import Limits: the caps every spreadsheet import is
 * read under (ARCHITECTURE.md §9.13). The tab itself is rendered by
 * {@see ApplicationSettingController}; this saves it.
 */
class ImportLimitController extends Controller
{
    public function __construct(protected ImportLimitService $limits) {}

    public function update(ImportLimitUpdateRequest $request): RedirectResponse
    {
        /** @var array{max_rows: int, max_columns: int, max_sheets: int} $attributes */
        $attributes = $request->validated();

        $this->limits->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Import limits saved.')]);

        return back();
    }
}
