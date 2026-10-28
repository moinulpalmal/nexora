<?php

namespace Tests\Fixtures\Imports;

use App\Http\Controllers\Imports\SheetImportController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * A company-scoped importer. The selected company is read from the session —
 * the stand-in for the header switcher ARCHITECTURE.md §9.10 plans — and never
 * from the request body.
 */
class CompanyWidgetImportController extends SheetImportController
{
    public function importKey(): string
    {
        return 'tests.company-widgets';
    }

    protected function indexRoute(): string
    {
        return 'tests.company-widgets.index';
    }

    protected function companyIdFor(FormRequest $request): ?int
    {
        $companyId = $request->session()->get('selected_company_id');

        return is_int($companyId) ? $companyId : null;
    }

    public function store(FixtureImportRequest $request): RedirectResponse
    {
        return $this->stageUpload($request);
    }
}
