<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Concerns\Listable;
use App\Enums\Admin\AuditEvent;
use App\Enums\FilterType;
use App\Models\User;
use App\Services\Admin\AuditRecorder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Models\Audit;

/**
 * One row of the audit trail — ARCHITECTURE.md §9.3.
 *
 * Bound as the package's implementation in `config/audit.php`. Never written
 * directly: rows come from the package's observers on {@see Audited}
 * models, or from {@see AuditRecorder} for what Eloquent
 * events cannot see. Not itself audited, and never edited.
 *
 * @property int $id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property string|null $actor_name
 * @property string|null $actor_username
 * @property string $event
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $tags
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AuditLog extends Audit
{
    use Listable;

    /**
     * The audit viewer's filter row. `auditable_type` and `event` are dropdowns
     * fed from the morph map and {@see AuditEvent}, so they match exactly.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'actor_name' => FilterType::Contains,
        'event' => FilterType::Equals,
        'auditable_type' => FilterType::Equals,
        'ip_address' => FilterType::Prefix,
    ];

    /**
     * @var list<string>
     */
    public const array SORTABLE = ['created_at', 'actor_name', 'event', 'auditable_type'];

    /**
     * The trail reads newest first; the request supplies `desc` by default.
     */
    public static function defaultSort(): string
    {
        return 'created_at';
    }

    /**
     * The event as this application names it, or null for a string no case
     * knows (an event retired after the row was written).
     */
    public function eventCase(): ?AuditEvent
    {
        return AuditEvent::tryFrom($this->event);
    }

    /**
     * The account that acted, including a since-deleted one.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
