<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\MailEncryption;
use App\Enums\Admin\MailTransport;
use App\Http\Controllers\Controller;
use App\Models\Admin\BrandingSetting;
use App\Models\Admin\MailSetting;
use App\Models\Admin\QuickLoginSetting;
use App\Models\User;
use App\Services\Admin\BrandingService;
use App\Services\Admin\QuickLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Application Settings: one page, one tab per application-level
 * setting (ARCHITECTURE.md §5).
 *
 * Each tab has its own `.view`/`.update` pair, so the tabs are independently
 * gated: the page opens for anyone holding either `.view` (route middleware),
 * and a section the actor cannot read arrives as `null` — so the page can tell
 * "no permission" from "not loaded" without guessing. Each tab saves through
 * its own controller and route.
 */
class ApplicationSettingController extends Controller
{
    public function __construct(protected QuickLoginService $quickLogin) {}

    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('admin/application-settings', [
            'sections' => [
                'mail' => $user->can('admin.mail-settings.view') ? $this->mailSection($user) : null,
                'quick-login' => $user->can('admin.quick-login.view') ? $this->quickLoginSection() : null,
                'branding' => $user->can('admin.branding.view') ? $this->brandingSection() : null,
            ],
        ]);
    }

    /**
     * The mail server, minus the password: the page only learns whether one is
     * set, and whether it can still be read under this APP_KEY.
     *
     * @return array<string, mixed>
     */
    private function mailSection(User $user): array
    {
        $setting = MailSetting::current()->loadMissing('lastUpdatedBy:id,name');

        return [
            'transport' => $setting->transport->value,
            'host' => $setting->host,
            'port' => $setting->port,
            'encryption' => $setting->encryption->value,
            'username' => $setting->username,
            'passwordSet' => filled($setting->readablePassword()),
            'passwordUnreadable' => $setting->hasUnreadablePassword(),
            'from_address' => $setting->from_address,
            'from_name' => $setting->from_name,
            'complete' => $setting->isComplete(),
            'transports' => MailTransport::options(),
            'encryptions' => MailEncryption::options(),
            'testRecipient' => $user->email,
            'updatedBy' => $setting->lastUpdatedBy?->name,
            'updatedAt' => $setting->last_updated_by !== null ? $setting->updated_at?->toIso8601String() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quickLoginSection(): array
    {
        $setting = QuickLoginSetting::current()->loadMissing('lastUpdatedBy:id,name');

        return [
            'enabled' => $setting->enabled,
            'environmentAllows' => $this->quickLogin->environmentAllows(),
            'environment' => app()->environment(),
            'accounts' => $this->quickLogin->accounts(),
            'updatedBy' => $setting->lastUpdatedBy?->name,
            'updatedAt' => $setting->last_updated_by !== null ? $setting->updated_at?->toIso8601String() : null,
        ];
    }

    /**
     * The uploaded images only — `null` where the built-in default shows.
     *
     * @return array<string, mixed>
     */
    private function brandingSection(): array
    {
        $setting = BrandingSetting::current()->loadMissing('lastUpdatedBy:id,name');
        $disk = Storage::disk(BrandingService::DISK);

        return [
            'logoUrl' => $setting->logo_path === null ? null : $disk->url($setting->logo_path),
            'faviconUrl' => $setting->favicon_path === null ? null : $disk->url($setting->favicon_path),
            'updatedBy' => $setting->lastUpdatedBy?->name,
            'updatedAt' => $setting->last_updated_by !== null ? $setting->updated_at?->toIso8601String() : null,
        ];
    }
}
