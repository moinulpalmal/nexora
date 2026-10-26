<?php

namespace App\Http\Requests\Admin;

use App\Enums\Admin\AuditEvent;
use App\Http\Requests\ListRequest;
use App\Models\Admin\AuditLog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Rule;

class AuditLogIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return AuditLog::SORTABLE;
    }

    protected function filterable(): array
    {
        return AuditLog::FILTERABLE;
    }

    /**
     * The dropdown cells are checked against their real sources: the event
     * vocabulary and the morph map — the same map that decides what can be
     * audited at all, so the two can never disagree.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'filter.event' => ['nullable', Rule::enum(AuditEvent::class)],
            'filter.auditable_type' => ['nullable', Rule::in(array_keys(Relation::morphMap()))],
        ];
    }

    /**
     * A trail reads newest first.
     */
    protected function defaultDirection(): string
    {
        return 'desc';
    }
}
