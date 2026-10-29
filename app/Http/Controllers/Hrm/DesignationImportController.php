<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Imports\SheetImportController;
use App\Http\Requests\Hrm\DesignationImportRequest;
use Illuminate\Http\RedirectResponse;

/**
 * HRM → Designations → Import (ARCHITECTURE.md §9.13). Designations are
 * group-wide, so the base controller's `companyIdFor()` (`null`) stands and
 * a staged file is not tied to the uploader's selected company.
 */
class DesignationImportController extends SheetImportController
{
    public function importKey(): string
    {
        return 'hrm.designations';
    }

    protected function indexRoute(): string
    {
        return 'hrm.designations.index';
    }

    public function store(DesignationImportRequest $request): RedirectResponse
    {
        return $this->stageUpload($request);
    }
}
