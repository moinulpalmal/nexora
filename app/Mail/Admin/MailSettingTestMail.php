<?php

namespace App\Mail\Admin;

use App\Models\User;
use App\Services\Admin\MailSettingService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The test mail from Application Settings → Mail Server. Sent synchronously by
 * {@see MailSettingService::sendTest()}, so the administrator sees a failure at
 * once rather than in the queue.
 */
class MailSettingTestMail extends Mailable
{
    public function __construct(public User $recipient) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(':app — test mail', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin.mail-test',
            with: [
                'subject' => $this->envelope()->subject,
                'recipientName' => $this->recipient->name,
                'sentAt' => now()->format('d M Y, h:i A'),
            ],
        );
    }
}
