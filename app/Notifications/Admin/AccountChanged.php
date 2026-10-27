<?php

namespace App\Notifications\Admin;

use App\Services\Admin\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a user that an administrator changed their account — their roles, or
 * their password — so a change made to someone is never silent to them.
 * Sent by {@see UserService}.
 *
 * Stored for the bell (`database`) and pushed live (`broadcast`). The payload is
 * the bell's generic shape — `title`, `body`, `url`, `tone` — so the bell needs
 * nothing per notification type.
 */
class AccountChanged extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $tone = 'info',
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array{kind: string, title: string, body: string, url: string, tone: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'account_changed',
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('profile.edit', absolute: false),
            'tone' => $this->tone,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
