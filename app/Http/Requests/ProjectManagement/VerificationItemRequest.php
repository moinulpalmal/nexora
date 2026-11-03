<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\VerificationItem;
use App\Services\Admin\SelectedCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a verification item. One request for both verbs, as
 * `ProductCategoryRequest`.
 *
 * **There is no `company_id` field** (ARCHITECTURE.md §9.10): the item belongs
 * to the actor's selected company, which `VerificationItemService` stamps.
 * There is no code either (owner, 2026-11-03), so the name is the key: unique
 * *within that company*, ignoring case, deleted items included — the same name
 * in another company is legal.
 */
class VerificationItemRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => self::normaliseName($this->input('name')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $item = $this->route('verification_item');

        // `withoutGlobalScopes()` drops the soft-delete scope too, so deleted
        // items are counted, and the company is named explicitly.
        $siblings = VerificationItem::withoutGlobalScopes()->where('company_id', app(SelectedCompany::class)->id() ?? 0);

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $this->uniqueIgnoringCase(
                    $siblings,
                    'name',
                    $item instanceof VerificationItem ? $item->id : null,
                    __('A verification item named ":value" already exists in this company.'),
                    __('A deleted verification item in this company is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * The validated values, an empty description saved as null rather than as
     * an empty string.
     *
     * @return array{name: string, description: string|null, status: string}
     */
    public function verificationItemAttributes(): array
    {
        return [
            'name' => $this->string('name')->value(),
            'description' => $this->filled('description') ? $this->string('description')->trim()->value() : null,
            'status' => $this->string('status')->value(),
        ];
    }
}
