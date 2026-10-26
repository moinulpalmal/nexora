<?php

namespace App\Concerns;

use App\Models\User;
use App\Observers\ActorObserver;
use App\Services\Admin\AuditRecorder;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;

/**
 * Records every change to a model in `audit_logs` — ARCHITECTURE.md §9.3.
 *
 * A model opts in with two lines, which is the whole registration:
 *
 * ```php
 * use App\Concerns\Audited;
 * use App\Contracts\Auditable;
 *
 * class Thing extends Model implements Auditable
 * {
 *     use Audited;
 * }
 * ```
 *
 * …plus an alias in `AppServiceProvider::MORPH_MAP`: the map is enforced, so an
 * unmapped model fails loudly on its first audit.
 *
 * **The interface is this application's own** ({@see \App\Contracts\Auditable}),
 * which extends the package's and adds the two custom-event operations below.
 * **The trait is named `Audited`, not `Auditable`,** so it cannot collide with
 * that interface in a model's `use` list.
 *
 * The nesting works because `Model::bootTraits()` walks `class_uses_recursive()`,
 * so the package's own `bootAuditable()` still fires through this wrapper.
 *
 * Hooks are overridden here rather than per model, so what the trail records
 * cannot drift between models — the same argument {@see ActorObserver} makes.
 *
 * **Secrets are excluded per model** with `$auditExclude` (strict mode is off, so
 * `$hidden` is not honoured): `User` excludes `password` and `remember_token`.
 */
trait Audited
{
    use Auditable {
        getAuditExclude as packageAuditExclude;
    }

    /**
     * The model's own exclusions, plus the actor stamps.
     *
     * `inserted_by` / `last_updated_by` ({@see ActorObserver}) only repeat the
     * row's own actor, so every update would carry them as noise. They are
     * appended here rather than listed per model because a model's
     * `$auditExclude` *replaces* the config default — a per-model list is exactly
     * where they would be forgotten.
     *
     * @return list<string>
     */
    public function getAuditExclude(): array
    {
        return array_values(array_unique([...$this->packageAuditExclude(), 'inserted_by', 'last_updated_by']));
    }

    /**
     * Stamp who the actor was, by name, at the moment of the write.
     *
     * The package stores only `user_id`. `users` is soft-deleted and the default
     * user provider applies that scope, so resolving the name later would render
     * a deleted account's history as "System". `transformAudit()` is the
     * package's last hook before the row is written, so this applies to
     * package-written and {@see AuditRecorder} custom events alike.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        $actor = Auth::user();

        return [
            ...$data,
            'actor_name' => $actor instanceof User ? $actor->name : null,
            'actor_username' => $actor instanceof User ? $actor->username : null,
        ];
    }

    /**
     * Stage an event the package's Eloquent observers cannot produce.
     *
     * The four properties come from the package trait, which is why this lives
     * here rather than in {@see AuditRecorder}: to a caller holding only the
     * contract they are not visible.
     *
     * `auditEvent` is assigned rather than passed through `setAuditEvent()`,
     * which nulls any name outside `config('audit.events')` — every event this
     * path writes is outside it by definition.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function prepareCustomAudit(string $event, array $old, array $new): void
    {
        $this->auditEvent = $event;
        $this->isCustomEvent = true;
        $this->auditCustomOld = $old;
        $this->auditCustomNew = $new;
    }

    /**
     * Discard staged custom-event state.
     *
     * **`isCustomEvent` must be reset**: the package consults it before anything
     * else, so an instance left staged would write its next ordinary save as
     * another custom event with the previous event's values. `auditEvent` is
     * overwritten by the observer before every dispatch, so it is left alone.
     */
    public function clearCustomAudit(): void
    {
        $this->isCustomEvent = false;
        $this->auditCustomOld = null;
        $this->auditCustomNew = null;
    }
}
