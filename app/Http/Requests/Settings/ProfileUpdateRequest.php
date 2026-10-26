<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The signed-in user editing their own profile.
 *
 * Name, phone and photo change freely. **Changing the username or email — the
 * two things that sign in — asks for the current password**, so an unattended
 * session cannot be used to take the account over. Employee code, status and
 * roles are administrators' business and are not accepted here at all.
 */
class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules($userId),
            'email' => $this->emailRules($userId),
            'phone' => $this->phoneRules(),
            'avatar' => $this->avatarRules(),
            'remove_avatar' => ['boolean'],
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->changesSignInIdentity()),
                'nullable',
                'string',
                'current_password',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => __('Enter your current password to change your username or email.'),
        ];
    }

    /**
     * Whether the submitted username or email differs from the stored one —
     * compared after the lowercasing `prepareForValidation()` applies.
     */
    public function changesSignInIdentity(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        return $this->input('username') !== $user->username
            || $this->input('email') !== $user->email;
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        return $this->safe()->only(['name', 'username', 'email', 'phone']);
    }
}
