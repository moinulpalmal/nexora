<?php

namespace Tests\Fixtures\Imports;

use App\Http\Controllers\Imports\SheetImportController;
use Illuminate\Http\RedirectResponse;

/**
 * Everything a concrete importer writes: its key, its list route, and a
 * three-line `store()` naming its upload request.
 */
class WidgetImportController extends SheetImportController
{
    public function importKey(): string
    {
        return 'tests.widgets';
    }

    protected function indexRoute(): string
    {
        return 'tests.widgets.index';
    }

    public function store(FixtureImportRequest $request): RedirectResponse
    {
        return $this->stageUpload($request);
    }
}
