<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Fold `username` and `email` the way the User mutators store them, before
     * the rules run.
     *
     * Without this the unique check compares the raw input: `Taken` passes
     * against a stored `taken` on any case-sensitive driver (SQLite) and the
     * insert then fails on the index. Normalising here makes the rule see what
     * will actually be written.
     */
    protected function prepareForValidation(): void
    {
        foreach (['username', 'email'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => mb_strtolower(trim($value))]);
            }
        }
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate usernames.
     *
     * Lowercase letters, digits, dot, dash and underscore, starting with a
     * letter or digit — the shape that is safe in a URL and unambiguous to type.
     * The User mutator lowercases what is stored, so uppercase input is accepted
     * here and folded. Unique across trashed rows too, like the index, so a
     * restore can never collide.
     *
     * An `@` is refused: the login field tells a username from an email by it.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function usernameRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:30',
            'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phoneRules(): array
    {
        return ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function employeeCodeRules(?int $userId = null): array
    {
        return [
            'nullable',
            'string',
            'max:30',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * jpg/png/webp up to 2 MB — the avatar decision in documentation/admin.md.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function avatarRules(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }
}
