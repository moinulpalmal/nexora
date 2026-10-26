<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Enums\RecordStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing another user's details. Roles and password have their own requests,
 * gated by their own permissions.
 */
class UserUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user') instanceof User ? $this->route('user')->id : null;

        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules($userId),
            'email' => $this->emailRules($userId),
            'phone' => $this->phoneRules(),
            'employee_code' => $this->employeeCodeRules($userId),
            'status' => ['required', Rule::enum(RecordStatus::class)],
            'avatar' => $this->avatarRules(),
            'remove_avatar' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userAttributes(): array
    {
        return $this->safe()->except(['avatar', 'remove_avatar']);
    }

    public function status(): RecordStatus
    {
        return RecordStatus::from($this->string('status')->value());
    }
}
