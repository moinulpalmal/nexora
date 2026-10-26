<?php

use App\Enums\Admin\MailTransport;
use App\Exceptions\Admin\MailNotConfiguredException;
use App\Mail\Admin\MailSettingTestMail;
use App\Models\Admin\AuditLog;
use App\Models\Admin\MailSetting;
use App\Models\Admin\QuickLoginSetting;
use App\Models\User;
use App\Services\Admin\MailSettingService;
use App\Services\Admin\QuickLoginService;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mailPayload(array $overrides = []): array
{
    return [
        'transport' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'starttls',
        'username' => 'mailer',
        'password' => 'secret-smtp-pass',
        'from_address' => 'noreply@example.com',
        'from_name' => 'Nexora',
        ...$overrides,
    ];
}

/*
| The hub
*/

test('the hub is refused to anyone holding neither tab', function () {
    $this->actingAs(userWithPermissions('admin.users.view'))
        ->get(route('admin.application-settings.edit'))
        ->assertForbidden();
});

test('a tab the actor cannot read arrives as null', function () {
    $this->actingAs(userWithPermissions('admin.quick-login.view'))
        ->get(route('admin.application-settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/application-settings')
            ->where('sections.mail', null)
            ->has('sections.quick-login.accounts'),
        );
});

test('the mail section never ships the password', function () {
    MailSetting::query()->updateOrCreate(['id' => 1], mailPayload());

    $this->actingAs(userWithPermissions('admin.mail-settings.view'))
        ->get(route('admin.application-settings.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('sections.mail.passwordSet', true)
            ->missing('sections.mail.password')
            ->where('sections.mail.host', 'smtp.example.com'),
        )
        ->assertDontSee('secret-smtp-pass');
});

/*
| Mail server
*/

test('saving the mail server encrypts the password and audits only that it changed', function () {
    $this->actingAs(userWithPermissions('admin.mail-settings.update'));
    config(['audit.console' => true]);

    assertToast($this->put(route('admin.mail-settings.update'), mailPayload()), 'success');

    $setting = MailSetting::current();
    $raw = DB::table('mail_settings')->value('password');

    expect($setting->readablePassword())->toBe('secret-smtp-pass')
        ->and($raw)->not->toBe('secret-smtp-pass')
        ->and(AuditLog::where('event', 'mail-password-changed')->exists())->toBeTrue()
        ->and(AuditLog::query()->get()->every(fn (AuditLog $audit): bool => ! str_contains(json_encode($audit->getAttributes()), 'secret-smtp-pass')))->toBeTrue();
});

test('a blank password keeps the stored one', function () {
    MailSetting::query()->updateOrCreate(['id' => 1], mailPayload());
    $this->actingAs(userWithPermissions('admin.mail-settings.update'));

    $this->put(route('admin.mail-settings.update'), mailPayload(['password' => '', 'host' => 'other.example.com']));

    expect(MailSetting::current())
        ->host->toBe('other.example.com')
        ->and(MailSetting::current()->readablePassword())->toBe('secret-smtp-pass');
});

test('SMTP needs a host and port, Log only does not', function () {
    $this->actingAs(userWithPermissions('admin.mail-settings.update'));

    $this->put(route('admin.mail-settings.update'), mailPayload(['host' => '', 'port' => '']))
        ->assertSessionHasErrors(['host', 'port']);

    $this->put(route('admin.mail-settings.update'), mailPayload(['transport' => 'log', 'host' => '', 'port' => '']))
        ->assertSessionHasNoErrors();
});

test('saving is refused without the update permission', function () {
    $this->actingAs(userWithPermissions('admin.mail-settings.view'))
        ->put(route('admin.mail-settings.update'), mailPayload())
        ->assertForbidden();
});

test('the database mailer builds its transport from the saved row', function () {
    MailSetting::query()->updateOrCreate(['id' => 1], mailPayload(['transport' => MailTransport::Log->value]));

    $transport = app(MailSettingService::class)->transport(app(MailManager::class));

    expect((string) $transport)->toContain('log')
        ->and(config('mail.from.address'))->toBe('noreply@example.com');
});

test('an unconfigured server refuses to build a transport', function () {
    MailSetting::query()->updateOrCreate(['id' => 1], mailPayload(['host' => null, 'from_address' => null]));

    app(MailSettingService::class)->transport(app(MailManager::class));
})->throws(MailNotConfiguredException::class);

test('the test mail goes to the actor and is audited', function () {
    Mail::fake();
    $actor = userWithPermissions('admin.mail-settings.update');
    $this->actingAs($actor);
    config(['audit.console' => true]);

    assertToast($this->post(route('admin.mail-settings.test')), 'success');

    Mail::assertSent(MailSettingTestMail::class, fn (MailSettingTestMail $mail): bool => $mail->hasTo($actor->email));
    expect(AuditLog::where('event', 'mail-test-sent')->sole()->new_values['sent'])->toBeTrue();
});

/*
| Quick login
*/

test('quick login is switched on and off', function () {
    $this->actingAs(userWithPermissions('admin.quick-login.update'));

    $this->put(route('admin.quick-login.update'), ['enabled' => true]);
    expect(QuickLoginSetting::current()->enabled)->toBeTrue();

    $this->put(route('admin.quick-login.update'), ['enabled' => false]);
    expect(QuickLoginSetting::current()->enabled)->toBeFalse();
});

test('the login page offers quick login only when switched on, for accounts that exist', function () {
    User::factory()->create(['username' => 'admin']);
    User::factory()->inactive()->create(['username' => 'viewer']);

    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('quickLoginAccounts', []));

    app(QuickLoginService::class)->update(true);

    $this->get(route('login'))->assertInertia(fn ($page) => $page
        ->where('quickLoginAccounts', [['label' => 'Demo Admin', 'username' => 'admin']]),
    );
});

test('quick login never renders in production, whatever the switch says', function () {
    User::factory()->create(['username' => 'admin']);
    app(QuickLoginService::class)->update(true);

    app()->detectEnvironment(fn () => 'production');

    expect(app(QuickLoginService::class)->loginAccounts())->toBe([]);
});
