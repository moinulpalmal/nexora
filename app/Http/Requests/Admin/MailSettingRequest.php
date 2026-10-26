<?php

namespace App\Http\Requests\Admin;

use App\Enums\Admin\MailEncryption;
use App\Enums\Admin\MailTransport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the Mail Server tab's save.
 *
 * The server fields are required only for SMTP; under *Log only* they may stay as
 * they were, so switching back needs no retyping. A blank password keeps the
 * stored one.
 */
class MailSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $smtp = Rule::requiredIf(fn (): bool => $this->input('transport') === MailTransport::Smtp->value);

        return [
            'transport' => ['required', Rule::enum(MailTransport::class)],
            'host' => [$smtp, 'nullable', 'string', 'max:255'],
            'port' => [$smtp, 'nullable', 'integer', 'between:1,65535'],
            'encryption' => [$smtp, 'nullable', Rule::enum(MailEncryption::class)],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * The tab's own labels, so a message reads "The SMTP server field is
     * required", not "The host field…".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'transport' => __('send mail by'),
            'host' => __('SMTP server'),
            'from_address' => __('sender address'),
            'from_name' => __('sender name'),
        ];
    }
}
