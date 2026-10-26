<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Requests\Admin\UserPasswordUpdateRequest;
use App\Http\Requests\Admin\UserRoleUpdateRequest;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use App\Services\Admin\UserCredentialMailer;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Users: one index page; every write arrives from a dialog on it and
 * answers `back()` with a toast (ARCHITECTURE.md §8.6).
 *
 * Each action is gated by its own permission on the route; the self- and
 * last-super-admin rules are `UserService` guards, refused here with a toast.
 */
class UserController extends Controller
{
    public function __construct(
        protected UserService $users,
        protected UserCredentialMailer $credentials,
    ) {}

    public function index(UserIndexRequest $request): Response
    {
        $filters = $request->filters();

        $users = User::query()
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->with('roles:id,name')
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (User $user): array => $this->describe($user));

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'roles' => $this->users->assignableRoleNames(),
            'statuses' => RecordStatus::options(),
            'sortable' => User::SORTABLE,
            'filterable' => User::FILTERABLE,
            'perPageOptions' => UserIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => User::query()->count(),
                'trashed' => User::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $password = $request->string('password')->value();

        $user = $this->users->create(
            $request->userAttributes(),
            $password,
            $request->submittedRoles(),
            $request->file('avatar'),
        );

        $mailed = $request->boolean('notify') && $this->credentials->welcome($user, $password);

        Inertia::flash('toast', ['type' => 'success', 'message' => $mailed
            ? __('User created. Their sign-in details are being emailed to :address.', ['address' => $user->email])
            : __('User created.')]);

        return back();
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $blocker = $this->users->statusBlocker($user, $request->status());

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->users->update(
            $user,
            $request->userAttributes(),
            $request->file('avatar'),
            $request->boolean('remove_avatar'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $blocker = $this->users->deletionBlocker($user);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->users->delete($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User moved to Deleted. They can no longer sign in.')]);

        return back();
    }

    public function restore(User $user): RedirectResponse
    {
        $this->users->restore($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User restored.')]);

        return back();
    }

    public function forceDelete(User $user): RedirectResponse
    {
        $blocker = $this->users->forceDeletionBlocker($user);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->users->forceDelete($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User permanently deleted.')]);

        return back();
    }

    public function updatePassword(UserPasswordUpdateRequest $request, User $user): RedirectResponse
    {
        $password = $request->string('password')->value();

        $this->users->resetPassword($user, $password);

        /** @var User $actor */
        $actor = $request->user();

        $mailed = $request->boolean('notify') && $this->credentials->passwordReset($user, $password, $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => $mailed
            ? __('Password updated. The new password is being emailed to :address.', ['address' => $user->email])
            : __('Password updated.')]);

        return back();
    }

    public function updateRoles(UserRoleUpdateRequest $request, User $user): RedirectResponse
    {
        $roles = $request->submittedRoles();

        $blocker = $this->users->roleAssignmentBlocker($user, $roles);

        if ($blocker !== null) {
            return $this->refuse($blocker);
        }

        $this->users->assignRoles($user, $roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Roles updated.')]);

        return back();
    }

    /**
     * Whether a username or email is still free, for the form's live check.
     *
     * Trashed rows count as taken — the unique indexes include them. This is a
     * convenience; the form requests are what enforce uniqueness.
     */
    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:username,email'],
            'value' => ['required', 'string', 'max:255'],
            'ignore' => ['nullable', 'integer'],
        ]);

        $taken = User::withTrashed()
            ->where($validated['field'], mb_strtolower(trim($validated['value'])))
            ->when($validated['ignore'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();

        return response()->json(['available' => ! $taken]);
    }

    protected function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return back();
    }

    /**
     * One row of the users table.
     *
     * @return array<string, mixed>
     */
    protected function describe(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'employee_code' => $user->employee_code,
            'status' => $user->status->value,
            'avatar_url' => $user->avatar_url,
            'roles' => $user->roles->pluck('name')->sort()->values(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toDateString(),
            'deleted_at' => $user->deleted_at?->toDateString(),
            'is_self' => $this->users->isActor($user),
            'is_last_super_admin' => $this->users->isLastSuperAdmin($user),
        ];
    }
}
