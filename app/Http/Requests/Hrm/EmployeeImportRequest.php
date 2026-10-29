<?php

namespace App\Http\Requests\Hrm;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The employees spreadsheet upload, as {@see DepartmentImportRequest}. No
 * company field: the rows go to the uploader's selected company, which
 * `EmployeeImportController` resolves on the server (ARCHITECTURE.md §9.10).
 */
class EmployeeImportRequest extends FormRequest
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
