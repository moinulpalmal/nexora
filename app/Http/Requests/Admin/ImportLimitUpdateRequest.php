<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The spreadsheet-import engine's runtime caps (ARCHITECTURE.md §9.13).
 *
 * Every cap has a floor of 1 and **no "unlimited" value**: a cap exists to stop
 * a runaway workbook from exhausting memory while it is staged, and zero meaning
 * "no limit" would be the one value that removes the protection silently. The
 * ceilings are what one upload request can stage without exhausting memory.
 */
class ImportLimitUpdateRequest extends FormRequest
{
    /**
     * The route's `permission:` middleware is the gate (ARCHITECTURE.md §9.1).
     */
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
            'max_rows' => ['required', 'integer', 'min:1', 'max:100000'],
            'max_columns' => ['required', 'integer', 'min:1', 'max:1000'],
            'max_sheets' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max_rows.required' => __('Enter the maximum number of rows a workbook may hold.'),
            'max_rows.min' => __('The row limit must be at least 1. There is no value meaning "no limit".'),
            'max_rows.max' => __('The row limit cannot exceed 100,000 — a larger workbook exhausts memory while it is staged.'),

            'max_columns.required' => __('Enter the maximum number of columns a sheet may hold.'),
            'max_columns.min' => __('The column limit must be at least 1. There is no value meaning "no limit".'),
            'max_columns.max' => __('The column limit cannot exceed 1,000.'),

            'max_sheets.required' => __('Enter the maximum number of worksheets a workbook may hold.'),
            'max_sheets.min' => __('The worksheet limit must be at least 1. There is no value meaning "no limit".'),
            'max_sheets.max' => __('The worksheet limit cannot exceed 100.'),
        ];
    }
}
