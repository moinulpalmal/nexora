<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(protected UserService $users) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/profile');
    }

    /**
     * Update the user's profile information.
     *
     * The writes go through `UserService`, so the photo is stored and replaced
     * exactly as on the admin screen. The update itself is audited like any
     * other (the actor is the user), which is what records an email or username
     * change. There is no self-delete: accounts are retired by administrators.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->users->update(
            $user,
            $request->profileAttributes(),
            $request->file('avatar'),
            $request->boolean('remove_avatar'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }
}
