<?php

namespace App\Services\Admin;

use App\Enums\Admin\AuditEvent;
use App\Enums\RecordStatus;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Every write to a user account, and the guards that may refuse one.
 *
 * Controllers call a `*Blocker()` first and refuse with its message, then call
 * the write. The guards live here, not in a policy or gate, because the
 * super-admin `Gate::before` bypass would skip a gate — and these rules
 * ("not yourself", "not the last super admin") must hold for a super admin too.
 * See ARCHITECTURE.md §9.1.
 */
class UserService
{
    /**
     * The disk and folder avatars live in: `public` so the browser can load them
     * straight from `/storage/avatars/…` (`php artisan storage:link`).
     */
    public const string AVATAR_DISK = 'public';

    public const string AVATAR_DIRECTORY = 'avatars';

    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * Create a user, assign their roles and store their avatar.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $roles
     */
    public function create(array $attributes, string $password, array $roles = [], ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($attributes, $password, $roles, $avatar): User {
            $user = new User($attributes);
            $user->password = $password;
            $user->save();

            $this->syncRoles($user, $roles);

            if ($avatar !== null) {
                $this->replaceAvatar($user, $avatar);
            }

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes, ?UploadedFile $avatar = null, bool $removeAvatar = false): void
    {
        $user->update($attributes);

        if ($avatar !== null) {
            $this->replaceAvatar($user, $avatar);
        } elseif ($removeAvatar) {
            $this->removeAvatar($user);
        }
    }

    /**
     * @param  list<string>  $roles
     */
    public function assignRoles(User $user, array $roles): void
    {
        $this->syncRoles($user, $roles);
    }

    /**
     * Set another user's password.
     *
     * The `updated` row the save writes carries no values (`password` is
     * excluded from the trail), so the act itself is recorded as its own event.
     */
    public function resetPassword(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->save();

        $this->audits->record($user, AuditEvent::PasswordReset);
    }

    /**
     * Soft delete: the account stays, signs in no more, and can be restored.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    public function restore(User $user): void
    {
        $user->restore();
    }

    /**
     * Delete for good, recording who the user was first — afterwards there is
     * no row left for the trail to point at by anything but its id.
     */
    public function forceDelete(User $user): void
    {
        $this->audits->record($user, AuditEvent::ForceDeleted, [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $this->roleNames($user),
        ]);

        $path = $user->avatar_path;

        $user->forceDelete();

        if ($path !== null) {
            Storage::disk(self::AVATAR_DISK)->delete($path);
        }
    }

    /**
     * Store a new avatar under a random name and delete the one it replaces.
     *
     * Written quietly: the file is not an account detail anyone needs diffed,
     * and the column change would otherwise be its own `updated` row.
     */
    public function replaceAvatar(User $user, UploadedFile $file): void
    {
        $previous = $user->avatar_path;

        $path = $file->storeAs(
            self::AVATAR_DIRECTORY,
            Str::uuid()->toString().'.'.$file->extension(),
            self::AVATAR_DISK,
        );

        $user->forceFill(['avatar_path' => $path ?: null])->saveQuietly();

        if ($previous !== null) {
            Storage::disk(self::AVATAR_DISK)->delete($previous);
        }
    }

    public function removeAvatar(User $user): void
    {
        if ($user->avatar_path === null) {
            return;
        }

        Storage::disk(self::AVATAR_DISK)->delete($user->avatar_path);

        $user->forceFill(['avatar_path' => null])->saveQuietly();
    }

    /*
    |--------------------------------------------------------------------------
    | Guards — each returns the refusal message, or null to allow
    |--------------------------------------------------------------------------
    */

    /**
     * @param  list<string>  $roles
     */
    public function roleAssignmentBlocker(User $user, array $roles): ?string
    {
        if ($this->isActor($user)) {
            return __('You cannot change your own roles. Ask another administrator.');
        }

        $losesSuperAdmin = $user->hasRole(Role::SUPER_ADMIN) && ! in_array(Role::SUPER_ADMIN, $roles, true);

        if ($losesSuperAdmin && ! $this->actorIsSuperAdmin()) {
            return __('Only a super admin may revoke the super-admin role.');
        }

        if ($losesSuperAdmin && $this->isLastSuperAdmin($user)) {
            return __('This is the last active super admin. Grant the role to someone else first.');
        }

        return null;
    }

    public function statusBlocker(User $user, RecordStatus $status): ?string
    {
        if ($status === RecordStatus::Active) {
            return null;
        }

        if ($this->isActor($user)) {
            return __('You cannot deactivate your own account.');
        }

        if ($this->isLastSuperAdmin($user)) {
            return __('This is the last active super admin and cannot be deactivated.');
        }

        return null;
    }

    public function deletionBlocker(User $user): ?string
    {
        if ($this->isActor($user)) {
            return __('You cannot delete your own account.');
        }

        if ($this->isLastSuperAdmin($user)) {
            return __('This is the last active super admin. Grant the role to someone else first.');
        }

        return null;
    }

    public function forceDeletionBlocker(User $user): ?string
    {
        return $this->deletionBlocker($user);
    }

    /**
     * Whether this user is the only *active* super admin left.
     *
     * Inactive and deleted super admins cannot sign in, so they do not count:
     * losing the last active one would leave nobody able to administer anything.
     */
    public function isLastSuperAdmin(User $user): bool
    {
        if (! $user->hasRole(Role::SUPER_ADMIN) || ! $user->isActive() || $user->trashed()) {
            return false;
        }

        return User::query()
            ->role(Role::SUPER_ADMIN)
            ->active()
            ->whereKeyNot($user->getKey())
            ->doesntExist();
    }

    public function isActor(User $user): bool
    {
        return Auth::id() === $user->getKey();
    }

    public function actorIsSuperAdmin(): bool
    {
        $actor = Auth::user();

        return $actor instanceof User && $actor->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * The role names the actor may hand out — `super-admin` only by a super admin.
     *
     * @return list<string>
     */
    public function assignableRoleNames(): array
    {
        $query = Role::query()->orderBy('name');

        if (! $this->actorIsSuperAdmin()) {
            $query->whereNot('name', Role::SUPER_ADMIN);
        }

        return array_values(array_filter($query->pluck('name')->all(), is_string(...)));
    }

    /**
     * Sync roles and record the change by name — the pivot write fires no
     * model event, and it is the most escalation-sensitive change there is.
     *
     * @param  list<string>  $roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        $before = $this->roleNames($user);

        $user->syncRoles($roles);

        $this->audits->recordChange($user, AuditEvent::RolesChanged, ['roles' => $before], ['roles' => $this->roleNames($user)]);
    }

    /**
     * Sorted, so an unchanged set compares equal in `recordChange()`.
     *
     * @return list<string>
     */
    private function roleNames(User $user): array
    {
        $names = array_values(array_filter($user->roles()->pluck('name')->all(), is_string(...)));
        sort($names);

        return $names;
    }
}
