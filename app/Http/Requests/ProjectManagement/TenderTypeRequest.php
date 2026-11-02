<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\TenderType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a tender type. One request for both verbs, as
 * `Hrm\DesignationRequest`.
 *
 * Tender types are group-wide (ARCHITECTURE.md §9.10), so the code and the
 * name are each unique across the whole table, deleted tender types included
 * — see {@see MasterDataValidationRules}.
 */
class TenderTypeRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => self::normaliseCode($this->input('code')),
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
        $tenderType = $this->route('tender_type');
        $ignore = $tenderType instanceof TenderType ? $tenderType->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    TenderType::withTrashed(),
                    'code',
                    $ignore,
                    __('The code ":value" is already taken.'),
                    __('The code ":value" belongs to a deleted tender type. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                $this->uniqueIgnoringCase(
                    TenderType::withTrashed(),
                    'name',
                    $ignore,
                    __('A tender type named ":value" already exists.'),
                    __('A deleted tender type is named ":value". Restore it from the Deleted tab instead.'),
                ),
            ],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "OTM".'),
        ];
    }

    /**
     * @return array{code: string, name: string, status: string}
     */
    public function tenderTypeAttributes(): array
    {
        return [
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'status' => $this->string('status')->value(),
        ];
    }
}
