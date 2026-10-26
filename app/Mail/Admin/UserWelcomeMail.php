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
 * A new user's sign-in details — sent by {@see UserCredentialMailer}.
 *
 * Queued on the `mail` queue, and **encrypted** (`ShouldBeEncrypted`) because
 * the payload carries the password into the `jobs` table. The copy kept in Sent
 * Mails has the password masked by {@see RecordSentMail}.
 */
class UserWelcomeMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $recipient, public string $password)
    {
        $this->onQueue('mail');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your :app account', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin.user-welcome',
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
