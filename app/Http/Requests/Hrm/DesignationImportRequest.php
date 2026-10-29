<?php

namespace App\Http\Requests\Hrm;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The designations spreadsheet upload. Designations are group-wide, so the
 * upload carries no company (ARCHITECTURE.md §9.10).
 */
class DesignationImportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.config('sheet-import.limits.max_file_size_kb'), 'mimes:xlsx,xls'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Choose a spreadsheet to upload.'),
            'file.max' => __('The file is larger than :max KB.'),
            'file.mimes' => __('Upload an Excel workbook (.xlsx or .xls).'),
        ];
    }
}
