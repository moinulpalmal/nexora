<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Supplier;
use App\Services\Admin\SelectedCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a supplier. One request for both verbs, as
 * {@see ClientRequest}, whose rules it repeats for the shared columns.
 *
 * **There is no `company_id` field** (ARCHITECTURE.md §9.10): the supplier
 * belongs to the actor's selected company, which `SupplierService` stamps.
 * The code and the name are each unique *within that company*, deleted
 * suppliers included; the email is not unique.
 *
 * **The category** is group-wide and required. It must be active to be
 * *chosen*, but a supplier already in a category that was later deactivated
 * keeps it on edit — as `Hrm\EmployeeRequest`'s designation.
 *
 * **Tax and bank numbers** are optional and not unique (owner, 2026-11-02):
 * branches of one firm may share them. A TIN is the 12 digits Bangladesh
 * issues; a BIN is typed with or without its hyphen, so digits and `-` are
 * accepted. A bank account number may carry letters (an IBAN abroad); a
 * routing number is digits.
 */
class SupplierRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $trim = fn (string $key): mixed => is_string($value = $this->input($key)) ? trim($value) : $value;

        $this->merge([
            'code' => self::normaliseCode($this->input('code')),
            'name' => self::normaliseName($this->input('name')),
            'contact_person' => self::normaliseName($this->input('contact_person')),
            'phone' => $trim('phone'),
            'email' => is_string($email = $this->input('email')) ? mb_strtolower(trim($email)) : $email,
            'tin' => $trim('tin'),
            'bin' => $trim('bin'),
            'trade_license_no' => $trim('trade_license_no'),
            'bank_name' => self::normaliseName($this->input('bank_name')),
            'bank_branch' => self::normaliseName($this->input('bank_branch')),
            'bank_account_name' => self::normaliseName($this->input('bank_account_name')),
            'bank_account_number' => is_string($account = $this->input('bank_account_number')) ? mb_strtoupper(trim($account)) : $account,
            'bank_routing_number' => $trim('bank_routing_number'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $ignore = $supplier instanceof Supplier ? $supplier->id : null;

        // `withoutGlobalScopes()` drops the soft-delete scope too, so deleted
        // suppliers are counted, and the company is named explicitly.
        $siblings = Supplier::withoutGlobalScopes()->where('company_id', app(SelectedCompany::class)->id() ?? 0);

        return [
            'supplier_category_id' => [
                'required',
                'integer',
                Rule::exists('supplier_categories', 'id')
                    ->whereNull('deleted_at')
                    ->where(fn (Builder $query) => $query
                        ->where('status', RecordStatus::Active->value)
                        ->orWhere('id', $supplier instanceof Supplier ? $supplier->supplier_category_id : 0)),
            ],
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
                    __('The code ":value" belongs to a deleted supplier in this company. Restore it from the Deleted tab instead.'),
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
                    __('A supplier named ":value" already exists in this company.'),
                    __('A deleted supplier in this company is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'tin' => ['nullable', 'string', 'regex:/^[0-9]{12}$/'],
            'bin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+(?:-[0-9]+)*$/'],
            'trade_license_no' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_branch' => ['nullable', 'string', 'max:150'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9\-\s]*$/'],
            'bank_routing_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]*$/'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_category_id.exists' => __('Choose an active supplier category.'),
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "BUILDMART" or "BM-01".'),
            'phone.regex' => __('Use digits, spaces and + ( ) - only.'),
            'tin.regex' => __('A TIN is 12 digits.'),
            'bin.regex' => __('Use digits, optionally joined by single hyphens, e.g. "000123456-0101".'),
            'bank_account_number.regex' => __('Use letters, digits, spaces and hyphens only.'),
            'bank_routing_number.regex' => __('Use digits only.'),
        ];
    }

    /**
     * The validated values, an empty optional field saved as null rather than
     * as an empty string.
     *
     * @return array{supplier_category_id: int, code: string, name: string, address: string|null, description: string|null, contact_person: string|null, phone: string|null, email: string|null, tin: string|null, bin: string|null, trade_license_no: string|null, bank_name: string|null, bank_branch: string|null, bank_account_name: string|null, bank_account_number: string|null, bank_routing_number: string|null, status: string}
     */
    public function supplierAttributes(): array
    {
        $optional = fn (string $key): ?string => $this->filled($key) ? $this->string($key)->trim()->value() : null;

        return [
            'supplier_category_id' => $this->integer('supplier_category_id'),
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'address' => $optional('address'),
            'description' => $optional('description'),
            'contact_person' => $optional('contact_person'),
            'phone' => $optional('phone'),
            'email' => $optional('email'),
            'tin' => $optional('tin'),
            'bin' => $optional('bin'),
            'trade_license_no' => $optional('trade_license_no'),
            'bank_name' => $optional('bank_name'),
            'bank_branch' => $optional('bank_branch'),
            'bank_account_name' => $optional('bank_account_name'),
            'bank_account_number' => $optional('bank_account_number'),
            'bank_routing_number' => $optional('bank_routing_number'),
            'status' => $this->string('status')->value(),
        ];
    }
}
