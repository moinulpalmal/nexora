<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * A new logo, a new favicon, or both. Raster images only — an uploaded SVG
 * opened directly would run its scripts on this domain (owner, 2026-10-28).
 */
class BrandingUpdateRequest extends FormRequest
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
        $favicon = $this->file('favicon');
        $faviconIsIco = $favicon instanceof UploadedFile
            && strtolower($favicon->getClientOriginalExtension()) === 'ico';

        return [
            'logo' => ['nullable', 'required_without:favicon', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'favicon' => [
                'nullable',
                'required_without:logo',
                'file',
                'extensions:png,ico',
                'max:256',
                // An ICO holds several sizes and is square by nature; a PNG must be square.
                ...($faviconIsIco
                    ? ['mimetypes:image/x-icon,image/vnd.microsoft.icon']
                    : ['mimes:png', 'dimensions:ratio=1']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.required_without' => __('Choose a logo or a favicon to upload.'),
            'favicon.required_without' => __('Choose a logo or a favicon to upload.'),
            'favicon.dimensions' => __('The favicon must be square.'),
        ];
    }
}
