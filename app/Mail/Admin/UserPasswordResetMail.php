<?php

namespace App\Mail\Admin;

use App\Listeners\Admin\RecordSentMail;
use App\Models\User;
use App\Services\Admin\UserCredentialMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A password set by an administrator — sent by {@see UserCredentialMailer}.
 * Queued and encrypted for the same reason as {@see UserWelcomeMail}; masked in
 * Sent Mails by {@see RecordSentMail}.
 */
class UserPasswordResetMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $recipient, public string $password, public string $actorName)
    {
        $this->onQueue('mail');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your :app password was reset', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin.user-password-reset',
            with: [
                'subject' => $this->envelope()->subject,
                'recipientName' => $this->recipient->name,
                'username' => $this->recipient->username,
                'loginUrl' => route('login'),
                'sentAt' => now()->format('d M Y, h:i A'),
            ],
        );
    }
}
