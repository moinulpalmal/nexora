<?php

namespace App\Concerns;

use App\Models\Admin\Role;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules for a submitted list of role names, shared by every form that
 * assigns roles (new user, the user's roles dialog).
 *
 * Granting `super-admin` is refused unless the actor holds it: the role passes
 * every check through `Gate::before`, so handing it out would be the widest
 * escalation there is. Revoking it is guarded in `UserService` instead.
 */
trait RoleAssignmentRules
{
    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function roleListRules(): array
    {
        return [
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists(Role::class, 'name'), $this->assignableRoleRule()],
        ];
    }

    /**
     * The submitted role names, strings only.
     *
     * @return list<string>
     */
    public function submittedRoles(): array
    {
        return array_values(array_filter($this->array('roles'), is_string(...)));
    }

    protected function assignableRoleRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value !== Role::SUPER_ADMIN) {
                return;
            }

            if (! $this->user()?->hasRole(Role::SUPER_ADMIN)) {
                $fail(__('Only a super admin may grant the super-admin role.'));
            }
        };
    }
}
