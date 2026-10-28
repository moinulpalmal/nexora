<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandingUpdateRequest;
use App\Services\Admin\BrandingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Application Settings → Branding: the logo and the favicon. Each upload
 * replaces the stored image; removing one brings back the built-in default.
 */
class BrandingSettingController extends Controller
{
    public function __construct(protected BrandingService $branding) {}

    public function update(BrandingUpdateRequest $request): RedirectResponse
    {
        foreach (array_keys(BrandingService::IMAGES) as $image) {
            $file = $request->file($image);

            if ($file !== null && ! is_array($file)) {
                $this->branding->replace($image, $file);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding saved. The browser tab icon updates on the next page load.')]);

        return back();
    }

    public function destroy(string $image): RedirectResponse
    {
        $this->branding->remove($image);

        Inertia::flash('toast', ['type' => 'success', 'message' => $image === 'logo'
            ? __('Logo removed. The default mark is back.')
            : __('Favicon removed. The tab shows the logo, or the default icon.')]);

        return back();
    }
}
