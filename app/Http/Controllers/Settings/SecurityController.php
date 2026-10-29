<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     *
     * The password rules reach the page through the shared `passwordPolicy`
     * prop, not from here.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/security');
    }

    /**
     * Update the user's password.
     *
     * A password the user chose themselves is no longer temporary, so this
     * also releases them from `EnsurePasswordIsChanged`.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->password,
            'must_change_password' => false,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
