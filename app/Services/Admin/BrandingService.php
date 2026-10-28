<?php

namespace App\Services\Admin;

use App\Models\Admin\BrandingSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The uploaded logo and favicon (Admin → Application Settings → Branding).
 *
 * Files go to the `public` disk under `branding/`, named by a fresh uuid, so a
 * replaced image gets a new URL and no browser keeps showing the old one. The
 * effective URLs are cached, because the favicon tag is on every page; every
 * write forgets the cache.
 */
class BrandingService
{
    public const string DISK = 'public';

    public const string DIRECTORY = 'branding';

    public const string CACHE_KEY = 'branding';

    /**
     * The images an administrator can set, and the column each is stored in.
     *
     * @var array<string, string>
     */
    public const array IMAGES = ['logo' => 'logo_path', 'favicon' => 'favicon_path'];

    /**
     * Store a new image under a random name and delete the one it replaces.
     */
    public function replace(string $image, UploadedFile $file): BrandingSetting
    {
        $column = self::IMAGES[$image];
        $setting = BrandingSetting::query()->firstOrNew(['id' => BrandingSetting::SINGLETON_ID]);
        $previous = $setting->{$column};

        $path = $file->storeAs(self::DIRECTORY, Str::uuid()->toString().'.'.$file->extension(), self::DISK);

        $setting->fill([$column => $path ?: null])->save();

        if ($previous !== null) {
            Storage::disk(self::DISK)->delete($previous);
        }

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    /**
     * Clear an image, so the default shows again, and delete its file.
     */
    public function remove(string $image): BrandingSetting
    {
        $column = self::IMAGES[$image];
        $setting = BrandingSetting::current();
        $previous = $setting->{$column};

        if ($previous === null) {
            return $setting;
        }

        $setting->fill([$column => null])->save();
        Storage::disk(self::DISK)->delete($previous);
        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    /**
     * The URL of each uploaded image, or `null` for the built-in default. The
     * favicon falls back to the logo, so a logo alone also brands the tab.
     *
     * @return array{logo: string|null, favicon: string|null}
     */
    public function urls(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $setting = BrandingSetting::current();
            $logo = $this->url($setting->logo_path);

            return [
                'logo' => $logo,
                'favicon' => $this->url($setting->favicon_path) ?? $logo,
            ];
        });
    }

    /**
     * The `type` of a `<link rel="icon">` for an image URL.
     */
    public function mimeType(string $url): string
    {
        return match (Str::lower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION))) {
            'ico' => 'image/x-icon',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }
}
