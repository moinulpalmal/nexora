<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The ids a bulk action is aimed at — the rows ticked on one page of a list
 * (ARCHITECTURE.md §8.6).
 *
 * Module-less, like {@see ListRequest}: every list with a bulk action posts the
 * same shape. It only checks the shape; **which of the ids the actor may act on
 * is the controller's query**, so an id of another company's row, or one
 * already gone, is simply not found rather than an error. Capped at the largest
 * page size, because a selection never spans more than one page.
 */
class BulkRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.max(ListRequest::PER_PAGE_OPTIONS)],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => __('Tick at least one row.'),
            'ids.max' => __('Tick no more than :max rows at a time.'),
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map(intval(...), array_filter($this->array('ids'), is_numeric(...))));
    }
}
