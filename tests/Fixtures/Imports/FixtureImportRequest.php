<?php

namespace Tests\Fixtures\Imports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The upload request every fixture importer shares — the shape a real
 * importer's `{Model}ImportRequest` takes when its rows need no scope field.
 */
class FixtureImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.config('sheet-import.limits.max_file_size_kb'), 'mimes:xlsx,xls'],
        ];
    }
}
