<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a role: its name and the permissions it carries.
 *
 * One request for both verbs — the rules differ only by the row the unique
 * check ignores. Role names are kebab-case like every other identifier.
 */
class RoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $unique = Rule::unique(Role::class, 'name')->where('guard_name', 'web');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $role instanceof Role ? $unique->ignore($role->id) : $unique,
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists(Permission::class, 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => __('Use lowercase letters, digits and single hyphens, e.g. "data-entry".'),
        ];
    }

    /**
     * @return list<string>
     */
    public function submittedPermissions(): array
    {
        return array_values(array_filter($this->array('permissions'), is_string(...)));
    }
}
