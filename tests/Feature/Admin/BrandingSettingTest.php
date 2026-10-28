<?php

use App\Models\Admin\AuditLog;
use App\Models\Admin\BrandingSetting;
use App\Services\Admin\BrandingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(BrandingService::DISK);
});

/*
| Access
*/

test('the branding tab opens for its view permission and arrives as null without it', function () {
    $this->actingAs(userWithPermissions('admin.branding.view'))
        ->get(route('admin.application-settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sections.branding.logoUrl', null)
            ->where('sections.branding.faviconUrl', null)
            ->where('sections.mail', null),
        );

    $this->actingAs(userWithPermissions('admin.quick-login.view'))
        ->get(route('admin.application-settings.edit'))
        ->assertInertia(fn ($page) => $page->where('sections.branding', null));
});

test('uploading and removing are refused without the update permission', function () {
    $this->actingAs(userWithPermissions('admin.branding.view'));

    $this->put(route('admin.branding.update'), ['logo' => UploadedFile::fake()->image('logo.png', 200, 60)])
        ->assertForbidden();
    $this->delete(route('admin.branding.destroy', 'logo'))->assertForbidden();
});

/*
| Uploads
*/

test('a logo is stored, audited, and replacing it deletes the old file', function () {
    config(['audit.console' => true]);
    $this->actingAs(userWithPermissions('admin.branding.update'));

    $response = $this->put(route('admin.branding.update'), ['logo' => UploadedFile::fake()->image('logo.png', 200, 60)]);

    assertToast($response, 'success');
    $first = BrandingSetting::current()->logo_path;
    expect($first)->toStartWith('branding/');
    Storage::disk(BrandingService::DISK)->assertExists($first);

    $this->put(route('admin.branding.update'), ['logo' => UploadedFile::fake()->image('logo.webp', 200, 60)]);

    $second = BrandingSetting::current()->logo_path;
    expect($second)->not->toBe($first)->toEndWith('.webp');
    Storage::disk(BrandingService::DISK)->assertMissing($first);
    Storage::disk(BrandingService::DISK)->assertExists($second);

    expect(AuditLog::where('auditable_type', 'branding-setting')->count())->toBe(2);
});

test('a square png or an ico is accepted as the favicon', function () {
    $this->actingAs(userWithPermissions('admin.branding.update'));

    $this->put(route('admin.branding.update'), ['favicon' => UploadedFile::fake()->image('icon.png', 64, 64)])
        ->assertSessionHasNoErrors();
    expect(BrandingSetting::current()->favicon_path)->toEndWith('.png');

    $this->put(route('admin.branding.update'), ['favicon' => UploadedFile::fake()->create('icon.ico', 4, 'image/x-icon')])
        ->assertSessionHasNoErrors();
    expect(BrandingSetting::current()->favicon_path)->toEndWith('.ico');
});

test('unsafe, oversized or misshapen images are refused', function (string $field, UploadedFile $file) {
    $this->actingAs(userWithPermissions('admin.branding.update'));

    $this->put(route('admin.branding.update'), [$field => $file])->assertSessionHasErrors($field);

    expect(BrandingSetting::query()->exists())->toBeFalse();
})->with([
    'an svg logo' => fn () => ['logo', UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml')],
    'an svg favicon' => fn () => ['favicon', UploadedFile::fake()->create('icon.svg', 2, 'image/svg+xml')],
    'a logo over 1 MB' => fn () => ['logo', UploadedFile::fake()->image('logo.png', 200, 60)->size(1100)],
    'a favicon over 256 KB' => fn () => ['favicon', UploadedFile::fake()->image('icon.png', 64, 64)->size(300)],
    'a wide png favicon' => fn () => ['favicon', UploadedFile::fake()->image('icon.png', 64, 32)],
]);

test('a save with no image asks for one', function () {
    $this->actingAs(userWithPermissions('admin.branding.update'))
        ->put(route('admin.branding.update'), [])
        ->assertSessionHasErrors(['logo', 'favicon']);
});

/*
| Removal and fallback
*/

test('removing an image deletes its file and brings the default back', function () {
    $this->actingAs(userWithPermissions('admin.branding.update'));
    $this->put(route('admin.branding.update'), ['logo' => UploadedFile::fake()->image('logo.png', 200, 60)]);
    $path = BrandingSetting::current()->logo_path;

    assertToast($this->delete(route('admin.branding.destroy', 'logo')), 'success');

    expect(BrandingSetting::current()->logo_path)->toBeNull();
    Storage::disk(BrandingService::DISK)->assertMissing($path);
});

test('only the logo and the favicon can be removed', function () {
    $this->actingAs(userWithPermissions('admin.branding.update'))
        ->delete('/admin/application-settings/branding/background')
        ->assertNotFound();
});

test('the shared branding prop falls back from favicon to logo to the default', function () {
    $this->actingAs(userWithPermissions('admin.branding.update'));

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('branding', ['logo' => null, 'favicon' => null]));

    $this->put(route('admin.branding.update'), ['logo' => UploadedFile::fake()->image('logo.png', 200, 60)]);
    $logo = Storage::disk(BrandingService::DISK)->url(BrandingSetting::current()->logo_path);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('branding', ['logo' => $logo, 'favicon' => $logo]));

    $this->put(route('admin.branding.update'), ['favicon' => UploadedFile::fake()->image('icon.png', 32, 32)]);
    $favicon = Storage::disk(BrandingService::DISK)->url(BrandingSetting::current()->favicon_path);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('branding.favicon', $favicon));
});

test('the page links the uploaded favicon instead of the built-in icons', function () {
    $this->actingAs(userWithPermissions('admin.branding.update'));

    $this->get(route('dashboard'))->assertSee('/favicon.ico', false);

    $this->put(route('admin.branding.update'), ['favicon' => UploadedFile::fake()->image('icon.png', 32, 32)]);
    $favicon = Storage::disk(BrandingService::DISK)->url(BrandingSetting::current()->favicon_path);

    $this->get(route('dashboard'))
        ->assertSee('<link rel="icon" href="'.$favicon.'" type="image/png">', false)
        ->assertDontSee('/favicon.ico', false);
});
