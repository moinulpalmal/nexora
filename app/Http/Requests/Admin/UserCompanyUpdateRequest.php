<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Replacing the companies a user may work in (ARCHITECTURE.md §9.10), from the
 * user's Companies dialog — the counterpart of {@see UserRoleUpdateRequest}.
 *
 * An inactive company may be granted (it becomes usable when reactivated); a
 * deleted one may not. The rules live here rather than in a shared trait
 * because no second form grants companies.
 */
class UserCompanyUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'companies' => ['sometimes', 'array'],
            'companies.*' => ['integer', 'distinct', Rule::exists(Company::class, 'id')->withoutTrashed()],
        ];
    }

    /**
     * The submitted company ids, sorted.
     *
     * @return list<int>
     */
    public function submittedCompanies(): array
    {
        $ids = array_map(intval(...), array_filter($this->array('companies'), is_numeric(...)));
        sort($ids);

        return array_values(array_unique($ids));
    }
}
