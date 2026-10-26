<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Concerns\RoleAssignmentRules;
use App\Enums\RecordStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules, RoleAssignmentRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            'phone' => $this->phoneRules(),
            'employee_code' => $this->employeeCodeRules(),
            'status' => ['required', Rule::enum(RecordStatus::class)],
            'avatar' => $this->avatarRules(),
            // Email the new user their sign-in details.
            'notify' => ['boolean'],
            ...$this->roleListRules(),
        ];
    }

    /**
     * The validated columns for the new row — everything the form sent that is
     * not handled separately (password, roles, the avatar file, the mail flag).
     *
     * @return array<string, mixed>
     */
    public function userAttributes(): array
    {
        return $this->safe()->except(['password', 'password_confirmation', 'roles', 'avatar', 'notify']);
    }
}
