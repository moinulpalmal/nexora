<?php

namespace App\Listeners\Admin;

use App\Models\Admin\SentMail;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Logs every mail the mailer actually handed over, for Admin → Sent Mails.
 *
 * **Secrets never reach the log.** A mailable that carries a credential exposes
 * it as view data under one of {@see self::SECRET_KEYS}; every occurrence of
 * that value in the stored HTML is replaced with a mask. The mail itself went
 * out complete — only the copy is redacted.
 *
 * Fires for queued mail too, inside the worker, once the message is really
 * sent. A failure to log is reported and swallowed: losing a log row must never
 * make a sent mail look failed.
 */
class RecordSentMail
{
    /**
     * View-data keys whose values are masked in the stored copy.
     *
     * @var list<string>
     */
    public const array SECRET_KEYS = ['password'];

    public const string MASK = '••••••••';

    public function handle(MessageSent $event): void
    {
        $message = $event->sent->getOriginalMessage();

        if (! $message instanceof Email) {
            return;
        }

        try {
            $recipient = $event->data['recipient'] ?? null;

            foreach ($message->getTo() as $to) {
                SentMail::query()->create([
                    'mailable' => is_string($event->data['__laravel_mailable'] ?? null)
                        ? class_basename($event->data['__laravel_mailable'])
                        : null,
                    'to_address' => $to->getAddress(),
                    'subject' => $message->getSubject(),
                    'html' => $this->redact($this->html($message), $event->data),
                    'user_id' => $recipient instanceof User ? $recipient->getKey() : null,
                    'mailer' => is_string($event->data['mailer'] ?? null) ? $event->data['mailer'] : null,
                    'sent_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function html(Email $message): ?string
    {
        $body = $message->getHtmlBody() ?? $message->getTextBody();

        if (is_resource($body)) {
            $body = stream_get_contents($body);
        }

        return is_string($body) ? $body : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function redact(?string $html, array $data): ?string
    {
        if ($html === null) {
            return null;
        }

        foreach (self::SECRET_KEYS as $key) {
            $secret = $data[$key] ?? null;

            if (is_string($secret) && $secret !== '') {
                // Both forms: Blade escapes the value it prints.
                $html = str_replace([$secret, e($secret)], self::MASK, $html);
            }
        }

        return $html;
    }
}
