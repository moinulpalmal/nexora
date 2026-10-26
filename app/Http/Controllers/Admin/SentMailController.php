<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SentMailIndexRequest;
use App\Models\Admin\SentMail;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Sent Mails: every mail the application sent, newest first, with a
 * preview of the stored (redacted) copy. Super admin only by default.
 */
class SentMailController extends Controller
{
    public function index(SentMailIndexRequest $request): Response
    {
        $filters = $request->filters();

        $mails = SentMail::query()
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (SentMail $mail): array => [
                'id' => $mail->id,
                'mailable' => $mail->mailable,
                'to_address' => $mail->to_address,
                'subject' => $mail->subject,
                'sent_at' => $mail->sent_at->toIso8601String(),
            ]);

        return Inertia::render('admin/sent-mails/index', [
            'mails' => $mails,
            'mailables' => SentMail::query()
                ->whereNotNull('mailable')
                ->distinct()
                ->orderBy('mailable')
                ->pluck('mailable')
                ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
                ->values(),
            'sortable' => SentMail::SORTABLE,
            'filterable' => SentMail::FILTERABLE,
            'perPageOptions' => SentMailIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
        ]);
    }

    /**
     * The stored copy, as a standalone HTML document for the preview iframe.
     *
     * Served with a CSP that blocks scripts and remote loads: the body is mail
     * HTML, rendered by this application, but it is still never trusted to run.
     */
    public function show(SentMail $sentMail): HttpResponse
    {
        return response($sentMail->html ?? '', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox",
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
