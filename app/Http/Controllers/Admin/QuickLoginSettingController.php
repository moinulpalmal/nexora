<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\QuickLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Application Settings → Quick Login: the switch. The environment lock in
 * {@see QuickLoginService} applies whatever is saved here.
 */
class QuickLoginSettingController extends Controller
{
    public function __construct(protected QuickLoginService $quickLogin) {}

    public function update(Request $request): RedirectResponse
    {
        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];

        $this->quickLogin->update((bool) $enabled);

        Inertia::flash('toast', ['type' => 'success', 'message' => $enabled
            ? __('Quick login switched on. The buttons appear on the login page in local and testing only.')
            : __('Quick login switched off.')]);

        return back();
    }
}
