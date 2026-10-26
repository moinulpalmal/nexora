<?php

namespace App\Exceptions\Admin;

use App\Services\Admin\MailSettingService;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown by {@see MailSettingService::transport()} when the `database` mailer is
 * asked to send before a server is saved.
 *
 * Rendered as an ordinary form error rather than a 500, because the first place
 * it surfaces is a person asking for a password reset link.
 */
class MailNotConfiguredException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Mail is not configured. Set the mail server on Admin → Application Settings → Mail Server.'));
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->withErrors(['email' => __('Mail is not configured, so no mail can be sent. Ask an administrator.')]);
    }
}
