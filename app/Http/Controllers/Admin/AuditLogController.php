<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLogHistoryRequest;
use App\Http\Requests\Admin\AuditLogIndexRequest;
use App\Models\Admin\AuditLog;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Audit Log: the trail, read-only, gated by `admin.audit-logs.view`
 * (super admin only by default).
 */
class AuditLogController extends Controller
{
    public function __construct(protected AuditLogService $audits) {}

    public function index(AuditLogIndexRequest $request): Response
    {
        $filters = $request->filters();

        $page = AuditLog::query()
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        // Resolved once for the whole page, not per row.
        $names = $this->audits->userNamesFor($page->getCollection());

        return Inertia::render('admin/audit-logs/index', [
            'auditLogs' => $page->through(fn (AuditLog $audit): array => $this->audits->describe($audit, $names)),
            'events' => AuditEvent::options(),
            'modelTypes' => $this->audits->modelTypes(),
            'sortable' => AuditLog::SORTABLE,
            'filterable' => AuditLog::FILTERABLE,
            'perPageOptions' => AuditLogIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
        ]);
    }

    /**
     * One record's whole history, for the history dialog (JSON, not a page).
     */
    public function history(AuditLogHistoryRequest $request): JsonResponse
    {
        return response()->json([
            'audits' => $this->audits->historyFor($request->type(), $request->recordId()),
        ]);
    }
}
