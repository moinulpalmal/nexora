<?php

namespace App\Http\Requests\Hrm;

use App\Concerns\MasterDataValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Enums\RecordStatus;
use App\Models\Hrm\Employee;
use App\Models\User;
use App\Services\Admin\SelectedCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Creating or editing an employee — one request for both verbs, as
 * {@see DepartmentRequest}.
 *
 * **There is no `company_id` field** (ARCHITECTURE.md §9.10): the employee
 * belongs to the selected company, which the model's scope stamps. The
 * department must be one of that company's; the designation is group-wide.
 * Either must be active to be *chosen*, but an employee already in a
 * department that was later deactivated keeps it on edit.
 *
 * **Code and email are unique across the whole group** (owner, 2026-10-29),
 * deleted employees included: the code becomes the account's username, and
 * usernames are global.
 *
 * **The account.** `is_application_user` is the form's box. Changing what it
 * says needs `hrm.employees.create-account`; a form without the box (an actor
 * without that permission) leaves the account as it is. Creating an account,
 * or re-activating one, needs a temporary `password`. Whenever an account is
 * linked or about to be, the username (the lowercased code) and the email must
 * also be free among *users*, since every save copies them onto the account.
 *
 * **Salary** is read only from an actor holding
 * `hrm.employees.view-compensation`; from anyone else it is ignored, not
 * validated, as `company_id` is.
 */
class EmployeeRequest extends FormRequest
{
    use MasterDataValidationRules, PasswordValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_code' => self::normaliseCode($this->input('employee_code')),
            'name' => self::normaliseName($this->input('name')),
            'email' => is_string($email = $this->input('email')) ? mb_strtolower(trim($email)) : $email,
            'official_mobile_no' => self::normaliseMobile($this->input('official_mobile_no')),
            'personal_mobile_no' => self::normaliseMobile($this->input('personal_mobile_no')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->employee();
        $ignore = $employee?->id;

        // `withoutGlobalScopes()` drops the company scope *and* the soft-delete
        // scope: the code and email are unique group-wide, deleted rows counted.
        $everyEmployee = Employee::withoutGlobalScopes();

        $rules = [
            'employee_code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    clone $everyEmployee,
                    'employee_code',
                    $ignore,
                    __('The employee ID ":value" is already taken.'),
                    __('The employee ID ":value" belongs to a deleted employee. Restore them from the Deleted tab instead.'),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $this->uniqueIgnoringCase(
                    clone $everyEmployee,
                    'email',
                    $ignore,
                    __('Another employee already uses ":value".'),
                    __('A deleted employee uses ":value". Restore them from the Deleted tab instead.'),
                ),
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('company_id', app(SelectedCompany::class)->id() ?? 0)
                    ->whereNull('deleted_at')
                    ->where(fn (Builder $query) => $query
                        ->where('status', RecordStatus::Active->value)
                        ->orWhere('id', $employee?->department_id ?? 0)),
            ],
            'designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')
                    ->whereNull('deleted_at')
                    ->where(fn (Builder $query) => $query
                        ->where('status', RecordStatus::Active->value)
                        ->orWhere('id', $employee?->designation_id ?? 0)),
            ],
            'official_mobile_no' => ['nullable', 'string', 'regex:'.self::MOBILE_PATTERN],
            'personal_mobile_no' => ['nullable', 'string', 'regex:'.self::MOBILE_PATTERN],
            'joining_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
            'is_application_user' => ['sometimes', 'boolean'],
        ];

        if ($this->canSeeCompensation()) {
            $rules['monthly_salary'] = ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'];
        }

        if ($this->needsTemporaryPassword()) {
            $rules['password'] = $this->passwordRules();
        }

        return $rules;
    }

    /**
     * The checks that need the other fields first: permission to change the
     * account, and the account's username and email being free among users.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->changesAccount() && ! $this->user()?->can('hrm.employees.create-account')) {
                    $validator->errors()->add('is_application_user', __('You may not create or change sign-in accounts.'));

                    return;
                }

                $linked = $this->employee()?->user;

                if (! $this->wantsApplicationUser() && $linked === null) {
                    return;
                }

                $username = mb_strtolower($this->string('employee_code')->value());

                if (mb_strlen($username) < 3) {
                    $validator->errors()->add('employee_code', __('An application user needs an employee ID of at least 3 characters — it becomes their username.'));
                }

                if (User::withTrashed()->where('username', $username)->when($linked !== null, fn ($query) => $query->whereKeyNot($linked->id))->exists()) {
                    $validator->errors()->add('employee_code', __('The username ":value" is already used by another sign-in account.', ['value' => $username]));
                }

                if (User::withTrashed()->where('email', $this->string('email')->value())->when($linked !== null, fn ($query) => $query->whereKeyNot($linked->id))->exists()) {
                    $validator->errors()->add('email', __('Another sign-in account already uses this email.'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "E1001" or "HQ-042".'),
            'official_mobile_no.regex' => __('Enter a Bangladeshi mobile number, e.g. 01712345678 or +8801712345678.'),
            'personal_mobile_no.regex' => __('Enter a Bangladeshi mobile number, e.g. 01712345678 or +8801712345678.'),
            'department_id.exists' => __('Choose an active department of this company.'),
            'designation_id.exists' => __('Choose an active designation.'),
        ];
    }

    /**
     * The employee's own columns. `monthly_salary` only from an actor who may
     * see it — anyone else's save leaves the stored value alone.
     *
     * @return array<string, mixed>
     */
    public function employeeAttributes(): array
    {
        $attributes = [
            'employee_code' => $this->string('employee_code')->value(),
            'name' => $this->string('name')->value(),
            'email' => $this->string('email')->value(),
            'department_id' => $this->integer('department_id'),
            'designation_id' => $this->integer('designation_id'),
            'official_mobile_no' => $this->input('official_mobile_no') ?: null,
            'personal_mobile_no' => $this->input('personal_mobile_no') ?: null,
            'joining_date' => $this->input('joining_date') ?: null,
            'status' => $this->string('status')->value(),
        ];

        if ($this->canSeeCompensation()) {
            $attributes['monthly_salary'] = $this->filled('monthly_salary') ? $this->input('monthly_salary') : null;
        }

        return $attributes;
    }

    /**
     * What the box asks for. A form that did not send it (no permission to see
     * it) asks for the account to stay as it is.
     */
    public function wantsApplicationUser(): bool
    {
        return $this->has('is_application_user')
            ? $this->boolean('is_application_user')
            : (bool) $this->employee()?->isApplicationUser();
    }

    /**
     * The temporary password, when this save creates or re-activates the
     * account; null otherwise.
     */
    public function temporaryPassword(): ?string
    {
        return $this->needsTemporaryPassword() ? $this->string('password')->value() : null;
    }

    /**
     * Local form, `01XXXXXXXXX`; the operator digit is 3–9.
     */
    private const string MOBILE_PATTERN = '/^01[3-9][0-9]{8}$/';

    /**
     * Spaces, hyphens and brackets dropped, and a `+880` / `880` country
     * prefix folded to the local leading `0` — so `+880 1712-345678` is stored
     * as `01712345678` and compares equal to it. Blank is null.
     */
    public static function normaliseMobile(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $digits = preg_replace('/[\s\-()]/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        return preg_replace('/^\+?880(?=1)/', '0', $digits);
    }

    private function employee(): ?Employee
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee ? $employee : null;
    }

    private function changesAccount(): bool
    {
        return $this->has('is_application_user')
            && $this->boolean('is_application_user') !== (bool) $this->employee()?->isApplicationUser();
    }

    /**
     * Ticked for an active employee, and the account does not exist yet or
     * cannot sign in now. An inactive employee's account is never opened, so
     * ticking the box for one asks for no password.
     */
    private function needsTemporaryPassword(): bool
    {
        return $this->wantsApplicationUser()
            && $this->input('status') === RecordStatus::Active->value
            && ! $this->employee()?->isApplicationUser();
    }

    private function canSeeCompensation(): bool
    {
        return (bool) $this->user()?->can('hrm.employees.view-compensation');
    }
}
