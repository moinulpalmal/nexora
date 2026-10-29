<?php

namespace App\Services\Admin;

use App\Models\Admin\Company;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * The company the signed-in user is working in (ARCHITECTURE.md §9.10) — the
 * one place that answers "which company?", for the `BelongsToCompany` scope,
 * the header switcher and every company-scoped write.
 *
 * **A user may only work in a usable company**: an active, undeleted one they
 * were granted — or any active one for a super admin, who needs no grant.
 * `users.selected_company_id` is a remembered *preference*, checked against
 * that list on every request rather than trusted: when it stops being usable
 * (deactivated, deleted, the grant removed) it is cleared, and when exactly
 * one usable company is left that one is chosen, so nobody with one company
 * ever has to pick it.
 *
 * Resolved once per request — the scope asks on every query — and memoised on
 * the request itself, so a new request (or a new test request) always starts
 * from the database.
 */
class SelectedCompany
{
    private const string MEMO = 'selected-company';

    /**
     * The actor's selected company, or null when there is no actor or no
     * usable company is selected.
     */
    public function current(): ?Company
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $memo = request()->attributes->get(self::MEMO);

        if (is_array($memo) && $memo['user'] === $user->id) {
            return $memo['company'];
        }

        $company = $this->resolve($user);

        request()->attributes->set(self::MEMO, ['user' => $user->id, 'company' => $company]);

        return $company;
    }

    public function id(): ?int
    {
        return $this->current()?->id;
    }

    /**
     * The companies this user may select, by name.
     *
     * @return Collection<int, Company>
     */
    public function usable(User $user): Collection
    {
        $query = $user->hasRole(Role::SUPER_ADMIN)
            ? Company::query()
            : $user->companies()->getQuery();

        return $query->active()->orderBy('name')->get();
    }

    public function canUse(User $user, Company $company): bool
    {
        return $this->usable($user)->contains('id', $company->id);
    }

    /**
     * Remember the user's choice. A preference, like the theme: saved quietly,
     * with no audit row or actor stamp — it changes what the user sees, not
     * what anyone's data says.
     */
    public function select(User $user, Company $company): void
    {
        $user->forceFill(['selected_company_id' => $company->id])->saveQuietly();

        $this->forget();
    }

    /**
     * Drop the memoised answer, after anything that may have changed it.
     */
    public function forget(): void
    {
        request()->attributes->remove(self::MEMO);
    }

    /**
     * Check the remembered choice and fall back when it is no longer usable.
     */
    private function resolve(User $user): ?Company
    {
        $usable = $this->usable($user);
        $selected = $usable->firstWhere('id', $user->selected_company_id);

        if ($selected instanceof Company) {
            return $selected;
        }

        $fallback = $usable->count() === 1 ? $usable->first() : null;

        if ($user->selected_company_id !== $fallback?->id) {
            $user->forceFill(['selected_company_id' => $fallback?->id])->saveQuietly();
        }

        return $fallback;
    }
}
