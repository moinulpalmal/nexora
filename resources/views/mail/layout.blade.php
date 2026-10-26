{{--
    The one layout every mail of this application shares: the app name as a
    text wordmark over a rule, the body, and a footer.

    Tables and inline styles only — mail clients ignore stylesheets and most of
    CSS, and an image would be blocked, so the wordmark is text. A mail is stored
    as sent (`sent_mails.html`), so nothing here may depend on when it is read.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,Helvetica,sans-serif;color:#1F2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#FFFFFF;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="padding:20px 28px 0 28px;">
                        <div style="font-size:22px;font-weight:bold;color:#1E1B4B;letter-spacing:0.5px;">{{ config('app.name') }}</div>
                        <div style="height:3px;background:#4F46E5;margin-top:8px;"></div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 28px 8px 28px;font-size:14px;line-height:1.5;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px 24px 28px;font-size:11px;color:#6B7280;border-top:1px solid #E5E7EB;">
                        @yield('footer')
                        <div style="margin-top:6px;">Sent by {{ config('app.name') }} — please do not reply to this address.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
