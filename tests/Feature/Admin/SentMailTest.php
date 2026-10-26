<?php

use App\Mail\Admin\UserPasswordResetMail;
use App\Mail\Admin\UserWelcomeMail;
use App\Models\Admin\AuditLog;
use App\Models\Admin\SentMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Mail;

/*
| Credential mails are queued and encrypted; the Sent Mails log keeps a copy
| with the password masked. `array` is the suite's mailer, so a sent mail
| really fires MessageSent and reaches RecordSentMail.
*/

test('creating a user with notify queues the welcome mail on the mail queue', function () {
    Mail::fake();
    $this->actingAs(userWithPermissions('admin.users.create'));
    config(['audit.console' => true]);

    $this->post(route('admin.users.store'), newUserPayload(['notify' => true]))->assertSessionHasNoErrors();

    Mail::assertQueued(UserWelcomeMail::class, fn (UserWelcomeMail $mail): bool => $mail->hasTo('jane@example.com') && $mail->queue === 'mail');
    expect(AuditLog::where('event', 'user-welcome-mailed')->sole()->new_values)->toBe(['to' => 'jane@example.com']);
});

test('creating a user without notify sends nothing', function () {
    Mail::fake();
    $this->actingAs(userWithPermissions('admin.users.create'));

    $this->post(route('admin.users.store'), newUserPayload());

    Mail::assertNothingQueued();
});

test('a password reset with notify queues the reset mail', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->actingAs(userWithPermissions('admin.users.reset-password'))
        ->put(route('admin.users.password', $user), [
            'password' => compliantPassword(),
            'password_confirmation' => compliantPassword(),
            'notify' => true,
        ]);

    Mail::assertQueued(UserPasswordResetMail::class, fn (UserPasswordResetMail $mail): bool => $mail->hasTo($user->email));
});

test('credential mails are encrypted on the queue', function () {
    expect(is_a(UserWelcomeMail::class, ShouldBeEncrypted::class, true))->toBeTrue()
        ->and(is_a(UserPasswordResetMail::class, ShouldBeEncrypted::class, true))->toBeTrue();
});

test('a sent mail is logged with its password masked', function () {
    $user = User::factory()->create(['email' => 'jane@example.com', 'username' => 'jdoe']);

    Mail::to($user->email)->send(new UserWelcomeMail($user, 'Plain-Text-Secret1!'));

    $log = SentMail::query()->sole();

    expect($log)
        ->to_address->toBe('jane@example.com')
        ->mailable->toBe('UserWelcomeMail')
        ->user_id->toBe($user->id)
        ->and($log->html)->toContain('jdoe')
        ->not->toContain('Plain-Text-Secret1!')
        ->toContain('••••••••');
});

test('the sent mails list is super admin only by default and newest first', function () {
    SentMail::query()->create(['to_address' => 'a@example.com', 'subject' => 'Old', 'sent_at' => now()->subDay()]);
    SentMail::query()->create(['to_address' => 'b@example.com', 'subject' => 'New', 'sent_at' => now()]);

    $this->actingAs(userWithPermissions('admin.users.view'))->get(route('admin.sent-mails.index'))->assertForbidden();

    $this->actingAs(superAdmin())
        ->get(route('admin.sent-mails.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/sent-mails/index')
            ->where('mails.data.0.subject', 'New')
            ->missing('mails.data.0.html'),
        );
});

test('the preview serves the stored copy under a locked-down CSP', function () {
    $mail = SentMail::query()->create(['to_address' => 'a@example.com', 'html' => '<p>Hello</p>', 'sent_at' => now()]);

    $this->actingAs(superAdmin())
        ->get(route('admin.sent-mails.show', $mail))
        ->assertOk()
        ->assertSee('<p>Hello</p>', escape: false)
        ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox");
});
