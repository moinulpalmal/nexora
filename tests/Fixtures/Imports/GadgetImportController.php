<?php

namespace Tests\Fixtures\Imports;

use App\Http\Controllers\Imports\SheetImportController;
use Illuminate\Http\RedirectResponse;

class GadgetImportController extends SheetImportController
{
    public function importKey(): string
    {
        return 'tests.gadgets';
    }

    protected function indexRoute(): string
    {
        return 'tests.gadgets.index';
    }

    public function store(FixtureImportRequest $request): RedirectResponse
    {
        return $this->stageUpload($request);
    }
}
