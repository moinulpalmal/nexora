{{--
    A new user's sign-in details, sent when an administrator creates the account
    with "Email the user" ticked. The stored copy in Sent Mails has the password
    masked by RecordSentMail.
--}}
@extends('mail.layout')

@section('title', $subject)

@section('content')
    <p style="margin:0 0 6px 0;">Dear {{ $recipientName }},</p>
    <p style="margin:0 0 16px 0;">An account has been created for you on {{ config('app.name') }}.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px 0;font-size:14px;">
        <tr>
            <td style="padding:2px 16px 2px 0;color:#6B7280;">Username</td>
            <td style="padding:2px 0;font-weight:bold;">{{ $username }}</td>
        </tr>
        <tr>
            <td style="padding:2px 16px 2px 0;color:#6B7280;">Password</td>
            <td style="padding:2px 0;font-weight:bold;">{{ $password }}</td>
        </tr>
    </table>

    <p style="margin:0 0 6px 0;">Sign in at <a href="{{ $loginUrl }}" style="color:#4F46E5;">{{ $loginUrl }}</a> with your username or email address.</p>
    <p style="margin:0 0 16px 0;">Please change your password from your security settings after you first sign in.</p>
@endsection

@section('footer')
    <div>Sent {{ $sentAt }}.</div>
@endsection
