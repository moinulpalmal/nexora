<?php

namespace App\Services\Admin;

use App\Concerns\Audited;
use App\Contracts\Auditable;
use App\Enums\Admin\AuditEvent;
use App\Models\Admin\AuditLog;
use App\Models\User;
use App\Observers\ActorObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;

/**
 * Records the things Eloquent events cannot see — ARCHITECTURE.md §9.3.
 *
 * The package covers `created` / `updated` / `deleted` / `restored` on a model
 * *instance*. Some changes never go through one:
 *
 * - **Pivot writes.** spatie/laravel-permission writes `model_has_roles` and
 *   `role_has_permissions` as raw pivot rows. These are the escalation-sensitive
 *   actions, so a trail that misses them misses the point of having one.
 * - **Authentication.** A sign-in changes no row.
 * - **Things done to a record rather than to its columns** — a password set by
 *   an administrator, a mail sent, a permanent delete.
 *
 * **This is the only writer for all of them**, for the reason
 * {@see ActorObserver} is one shared class: "recorded identically" stops being
 * true the moment there is a second copy of the logic. It deliberately does
 * **not** wrap ordinary model saves, which are already audited.
 */
final class AuditRecorder
{
    /**
     * Record something that happened, whether or not it changed a row.
     *
     * Pass the record it concerns as `$subject` where there is one — the audit is
     * then written through the package, so it picks up the actor name and the
     * IP/URL/agent resolvers like every other row. Pass null only when there
     * genuinely is no record: a rejected sign-in for a login matching no user.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function record(?Model $subject, AuditEvent $event, array $old = [], array $new = []): void
    {
        if (! $this->enabled()) {
            return;
        }

        if ($subject instanceof Auditable) {
            $this->recordThroughPackage($subject, $event, $old, $new);

            return;
        }

        $this->recordWithoutSubject($event, $old, $new);
    }

    /**
     * Record a change, and nothing at all when there was no change.
     *
     * Opening the roles dialog and pressing save without changing anything is
     * the common case; without this guard the trail fills with rows saying a
     * value stayed the same. **Callers pass list values in a stable order**
     * (sorted names), because `[a, b]` and `[b, a]` would read as a change.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function recordChange(?Model $subject, AuditEvent $event, array $old, array $new): void
    {
        if ($old === $new) {
            return;
        }

        $this->record($subject, $event, $old, $new);
    }

    /**
     * Whether an audit should be written at all in this context.
     *
     * Mirrors `Auditable::isAuditingEnabled()` on purpose: the package checks it
     * before every *model* event, but the listener behind {@see AuditCustom}
     * checks nothing. Without this, a seeder that assigned a role would write an
     * audit while the user it created wrote none.
     */
    private function enabled(): bool
    {
        if (! config('audit.enabled', true)) {
            return false;
        }

        return ! App::runningInConsole() || (bool) config('audit.console', false);
    }

    /**
     * Write the audit through the package, so one code path stamps every row.
     *
     * The staging lives on {@see Audited} because the properties belong to the
     * package's trait. The `finally` is not tidiness: an instance left prepared
     * would write its next ordinary save as another custom event.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function recordThroughPackage(Auditable&Model $subject, AuditEvent $event, array $old, array $new): void
    {
        $subject->prepareCustomAudit($event->value, $old, $new);

        try {
            Event::dispatch(new AuditCustom($subject));
        } finally {
            $subject->clearCustomAudit();
        }
    }

    /**
     * Write a row that concerns no record, stamping what the package would.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function recordWithoutSubject(AuditEvent $event, array $old, array $new): void
    {
        $actor = Auth::user();
        $request = request();

        AuditLog::query()->create([
            'user_type' => $actor instanceof User ? $actor->getMorphClass() : null,
            'user_id' => $actor?->getAuthIdentifier(),
            'actor_name' => $actor instanceof User ? $actor->name : null,
            'actor_username' => $actor instanceof User ? $actor->username : null,
            'event' => $event->value,
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => $old,
            'new_values' => $new,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => null,
        ]);
    }
}
