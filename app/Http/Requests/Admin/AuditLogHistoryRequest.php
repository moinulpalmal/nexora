<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One record's history. The type must be a morph-map alias, never a class
 * name, so a client can only ever ask about something the application audits.
 */
class AuditLogHistoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(Relation::morphMap()))],
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function type(): string
    {
        return $this->string('type')->value();
    }

    public function recordId(): int
    {
        return $this->integer('id');
    }
}
