<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Imports\SheetImportController;
use App\Http\Requests\Hrm\DepartmentImportRequest;
use App\Http\Requests\Imports\ImportConfirmRequest;
use App\Models\SheetImport;
use App\Services\Admin\SelectedCompany;
use App\Services\Imports\ImportRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * HRM → Departments → Import (ARCHITECTURE.md §9.13). Everything but the
 * company lives in {@see SheetImportController}.
 *
 * **The company is the uploader's selected one**, resolved here and never
 * read from the request (§9.10). A staged file keeps the company it was
 * uploaded for, so reviewing or confirming it is refused while the uploader
 * is working in another — every ERP screen writes only the selected company.
 */
class DepartmentImportController extends SheetImportController
{
    public function importKey(): string
    {
        return 'hrm.departments';
    }

    protected function indexRoute(): string
    {
        return 'hrm.departments.index';
    }

    protected function companyIdFor(FormRequest $request): ?int
    {
        return app(SelectedCompany::class)->id();
    }

    public function store(DepartmentImportRequest $request): RedirectResponse
    {
        return $this->stageUpload($request);
    }

    public function review(ImportConfirmRequest $request, SheetImport $sheetImport, ImportRegistry $registry): JsonResponse
    {
        if (! $this->isForSelectedCompany($sheetImport)) {
            return response()->json(['message' => $this->otherCompanyMessage()], 422);
        }

        return parent::review($request, $sheetImport, $registry);
    }

    public function confirm(ImportConfirmRequest $request, SheetImport $sheetImport): RedirectResponse
    {
        if (! $this->isForSelectedCompany($sheetImport)) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => $this->otherCompanyMessage()]);

            return to_route($this->indexRoute());
        }

        return parent::confirm($request, $sheetImport);
    }

    private function isForSelectedCompany(SheetImport $sheetImport): bool
    {
        return $sheetImport->company_id !== null && (int) $sheetImport->company_id === app(SelectedCompany::class)->id();
    }

    private function otherCompanyMessage(): string
    {
        return __('This file was uploaded for another company. Switch back to it in the header to finish the import, or cancel it.');
    }
}
