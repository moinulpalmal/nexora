<?php

namespace App\Http\Requests\ProjectManagement;

use App\Concerns\MasterDataValidationRules;
use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Certification;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a certification. One request for both verbs, as
 * {@see TenderTypeRequest}.
 *
 * Certifications are group-wide (ARCHITECTURE.md §9.10). The code is unique
 * across the whole table; the name is unique **per version** (owner,
 * 2026-11-02) — ISO 9001 2008 and 2015 are two rows — and a blank version
 * counts as one edition of its own, so two unversioned "CE" rows clash. Both
 * count deleted certifications, as {@see MasterDataValidationRules} does.
 *
 * **The category** is group-wide and required. It must be active to be
 * *chosen*, but a certification already in a category that was later
 * deactivated keeps it on edit — as {@see SupplierRequest}'s category.
 */
class CertificationRequest extends FormRequest
{
    use MasterDataValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => self::normaliseCode($this->input('code')),
            'name' => self::normaliseName($this->input('name')),
            'issuing_body' => self::normaliseName($this->input('issuing_body')),
            'version' => is_string($version = $this->input('version')) ? trim($version) : $version,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $certification = $this->route('certification');
        $ignore = $certification instanceof Certification ? $certification->id : null;

        $version = $this->filled('version') ? mb_strtolower($this->string('version')->value()) : null;
        $sameVersion = Certification::withTrashed()->when(
            $version === null,
            fn ($query) => $query->whereNull('version'),
            fn ($query) => $query->whereRaw('LOWER(version) = ?', [$version]),
        );

        return [
            'certification_category_id' => [
                'required',
                'integer',
                Rule::exists('certification_categories', 'id')
                    ->whereNull('deleted_at')
                    ->where(fn (Builder $query) => $query
                        ->where('status', RecordStatus::Active->value)
                        ->orWhere('id', $certification instanceof Certification ? $certification->certification_category_id : 0)),
            ],
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                $this->uniqueIgnoringCase(
                    Certification::withTrashed(),
                    'code',
                    $ignore,
                    __('The code ":value" is already taken.'),
                    __('The code ":value" belongs to a deleted certification. Restore it from the Deleted tab instead.'),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                $this->uniqueIgnoringCase(
                    $sameVersion,
                    'name',
                    $ignore,
                    __('A certification named ":value" already exists in this version.'),
                    __('A deleted certification is named ":value" in this version. Restore it from the Deleted tab instead.'),
                ),
            ],
            'issuing_body' => ['nullable', 'string', 'max:150'],
            'version' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'certification_category_id.exists' => __('Choose an active certification category.'),
            'code.regex' => __('Use letters and digits, optionally joined by single hyphens or underscores, e.g. "ISO9001-2015".'),
        ];
    }

    /**
     * The validated values, an empty optional field saved as null rather than
     * as an empty string.
     *
     * @return array{certification_category_id: int, code: string, name: string, issuing_body: string|null, version: string|null, description: string|null, status: string}
     */
    public function certificationAttributes(): array
    {
        $optional = fn (string $key): ?string => $this->filled($key) ? $this->string($key)->trim()->value() : null;

        return [
            'certification_category_id' => $this->integer('certification_category_id'),
            'code' => $this->string('code')->value(),
            'name' => $this->string('name')->value(),
            'issuing_body' => $optional('issuing_body'),
            'version' => $optional('version'),
            'description' => $optional('description'),
            'status' => $this->string('status')->value(),
        ];
    }
}
