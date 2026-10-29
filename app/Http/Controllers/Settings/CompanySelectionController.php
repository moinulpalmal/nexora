<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\User;
use App\Services\Admin\SelectedCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The header's company switcher (ARCHITECTURE.md §9.10). A Settings route like
 * the theme: it changes how the signed-in user works, so there is no
 * permission gate — only the check that the company is one they may use.
 */
class CompanySelectionController extends Controller
{
    public function __construct(protected SelectedCompany $selectedCompany) {}

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $company = Company::query()->find($validated['company_id']);

        if (! $company instanceof Company || ! $this->selectedCompany->canUse($user, $company)) {
            throw ValidationException::withMessages([
                'company_id' => __('You cannot work in that company.'),
            ]);
        }

        $this->selectedCompany->select($user, $company);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Now working in :company.', ['company' => $company->name])]);

        return back();
    }
}
