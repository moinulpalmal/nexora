<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Client;
use App\Services\Admin\SelectedCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a client. One request for both verbs, as
 * `Hrm\DepartmentRequest`.
 *
 * **There is no `company_id` field** (ARCHITECTURE.md §9.10): the client
 * belongs to the actor's selected company, which `ClientService` stamps. A
 * browser-sent `company_id` is ignored, not validated. The code and the name
 * are each unique *within that company*, deleted clients included — the same
 * values in another company are legal, and the messages say "in this company"
 * so that does not read as a bug.
 *
 * The email is **not** unique (owner, 2026-11-02): two clients may share one
 * billing or group address. The phone takes the characters a person types
 * into one — digits, spaces, `+ ( ) -` — as a user's phone does
 * (`ProfileValidationRules::phoneRules()`); a client may be abroad, so no
 * national format is imposed.
 */
class ClientRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => self::normaliseCode($this->input('code')),
            'name' => self::normaliseName($this->input('name')),
            'contact_person' => self::normaliseName($this->input('contact_person')),
            'phone' => is_string($phone = $this->input('phone')) ? trim($phone) : $phone,
            'email' => is_string($email = $this->input('email')) ? mb_strtolower(trim($email)) : $email,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');
        $ignore = $client instanceof Client ? $client->id : null;

        // `withoutGlobalScopes()` drops the soft-delete scope too, so deleted
        // clients are counted, and the company is named explicitly.
        $siblings = Client::withoutGlobalScopes()->where('company_id', app(SelectedCompany::class)->id() ?? 0);

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    clone $siblings,
                    'code',
                    $ignore,
                    __('The code ":value" is already taken in this company.'),
                    __('The code ":value" belongs to a deleted client in this company. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                $this->uniqueIgnoringCase(
                    clone $siblings,
                    'name',
                    $ignore,
                    __('A client named ":value" already exists in this company.'),
                    __('A deleted client in this company is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "ACME" or "ACME-01".'),
            'phone.regex' => __('Use digits, spaces and + ( ) - only.'),
        ];
    }

    /**
     * The validated values, an empty optional field saved as null rather than
     * as an empty string.
     *
     * @return array{code: string, name: string, address: string|null, description: string|null, contact_person: string|null, phone: string|null, email: string|null, status: string}
     */
    public function clientAttributes(): array
    {
        return [
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'address' => $this->filled('address') ? $this->string('address')->trim()->value() : null,
            'description' => $this->filled('description') ? $this->string('description')->trim()->value() : null,
            'contact_person' => $this->filled('contact_person') ? $this->string('contact_person')->value() : null,
            'phone' => $this->filled('phone') ? $this->string('phone')->value() : null,
            'email' => $this->filled('email') ? $this->string('email')->value() : null,
            'status' => $this->string('status')->value(),
        ];
    }
}
