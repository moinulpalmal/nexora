<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MailSettingRequest;
use App\Models\User;
use App\Services\Admin\MailSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Application Settings → Mail Server: save the server, send a test mail.
 */
class MailSettingController extends Controller
{
    public function __construct(protected MailSettingService $settings) {}

    public function update(MailSettingRequest $request): RedirectResponse
    {
        $this->settings->update([
            'transport' => $request->string('transport')->value(),
            'host' => $request->filled('host') ? $request->string('host')->value() : null,
            'port' => $request->filled('port') ? $request->integer('port') : null,
            'encryption' => $request->filled('encryption') ? $request->string('encryption')->value() : null,
            'username' => $request->filled('username') ? $request->string('username')->value() : null,
            'password' => $request->filled('password') ? $request->string('password')->value() : null,
            'from_address' => $request->string('from_address')->value(),
            'from_name' => $request->string('from_name')->value(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mail settings saved. The next mail goes out with them.')]);

        return back();
    }

    public function test(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $error = $this->settings->sendTest($actor);

        Inertia::flash('toast', $error === null
            ? ['type' => 'success', 'message' => $this->settings->isLogOnly()
                ? __('Test mail written to the log (Log only). Nobody received it.')
                : __('Test mail sent to :email.', ['email' => $actor->email])]
            : ['type' => 'error', 'message' => __('The test mail failed: :error', ['error' => $error])]);

        return back();
    }
}
