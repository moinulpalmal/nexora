{{--
    The test mail from Application Settings → Mail Server: proof that the saved
    server and sender work.
--}}
@extends('mail.layout')

@section('title', $subject)

@section('content')
    <p style="margin:0 0 6px 0;">Dear {{ $recipientName }},</p>
    <p style="margin:0 0 6px 0;">This is a test mail from Admin → Application Settings → Mail Server.</p>
    <p style="margin:0 0 16px 0;">If you are reading it, the mail server and sender address saved there work.</p>
@endsection

@section('footer')
    <div>Sent {{ $sentAt }}.</div>
@endsection
