{{--
    A password set by an administrator, sent when "Email the user" is ticked on
    the password dialog. The stored copy in Sent Mails has the password masked.
--}}
@extends('mail.layout')

@section('title', $subject)

@section('content')
    <p style="margin:0 0 6px 0;">Dear {{ $recipientName }},</p>
    <p style="margin:0 0 16px 0;">Your {{ config('app.name') }} password was reset by {{ $actorName }}.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px 0;font-size:14px;">
        <tr>
            <td style="padding:2px 16px 2px 0;color:#6B7280;">Username</td>
            <td style="padding:2px 0;font-weight:bold;">{{ $username }}</td>
        </tr>
        <tr>
            <td style="padding:2px 16px 2px 0;color:#6B7280;">New password</td>
            <td style="padding:2px 0;font-weight:bold;">{{ $password }}</td>
        </tr>
    </table>

    <p style="margin:0 0 6px 0;">Sign in at <a href="{{ $loginUrl }}" style="color:#4F46E5;">{{ $loginUrl }}</a>.</p>
    <p style="margin:0 0 16px 0;">Please change your password after you sign in. If you did not expect this, tell your administrator.</p>
@endsection

@section('footer')
    <div>Sent {{ $sentAt }}.</div>
@endsection
