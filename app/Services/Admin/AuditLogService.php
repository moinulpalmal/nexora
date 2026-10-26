<?php

namespace App\Services\Admin;

use App\Models\Admin\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shapes audit rows for the viewer: labels, and user ids inside the values
 * resolved to names (deleted users included).
 */
class AuditLogService
{
    /**
     * Value keys that hold a user id and read better as a name.
     */
    private const array USER_REFERENCE_KEYS = ['inserted_by', 'last_updated_by', 'user_id'];

    /**
     * The model-type filter's options — derived from the morph map, never
     * hand-listed, so it cannot drift from what is actually auditable.
     *
     * @return list<array{value: string, label: string}>
     */
    public function modelTypes(): array
    {
        $aliases = array_keys(Relation::morphMap());
        sort($aliases);

        return array_map(
            fn (string $alias): array => ['value' => $alias, 'label' => $this->labelForType($alias)],
            $aliases,
        );
    }

    /**
     * Every audit of one record, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function historyFor(string $type, int $id): array
    {
        $audits = AuditLog::query()
            ->where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $names = $this->userNamesFor($audits);

        return array_values($audits->map(fn (AuditLog $audit): array => $this->describe($audit, $names))->all());
    }

    /**
     * One row for the viewer.
     *
     * @param  array<int, string>  $names  user id → display name, from {@see self::userNamesFor()}
     * @return array<string, mixed>
     */
    public function describe(AuditLog $audit, array $names): array
    {
        $old = $this->describeValues($audit->old_values ?? [], $names);
        $new = $this->describeValues($audit->new_values ?? [], $names);

        return [
            'id' => $audit->id,
            'event' => $audit->event,
            'event_label' => $audit->eventCase()?->label() ?? $audit->event,
            'auditable_type' => $audit->auditable_type,
            'auditable_id' => $audit->auditable_id,
            'model_label' => $audit->auditable_type === null ? null : $this->labelForType($audit->auditable_type),
            'actor_name' => $audit->actor_name,
            'actor_username' => $audit->actor_username,
            'user_id' => $audit->user_id,
            'changed' => array_values(array_unique([...array_keys($old), ...array_keys($new)])),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $audit->ip_address,
            'url' => $audit->url,
            'user_agent' => $audit->user_agent,
            'created_at' => $audit->created_at?->toIso8601String(),
        ];
    }

    /**
     * Names for every user id the page's values mention, in one query.
     *
     * @param  Collection<int, AuditLog>  $audits
     * @return array<int, string>
     */
    public function userNamesFor(Collection $audits): array
    {
        $ids = [];

        foreach ($audits as $audit) {
            foreach ([$audit->old_values ?? [], $audit->new_values ?? []] as $values) {
                foreach (self::USER_REFERENCE_KEYS as $key) {
                    if (is_numeric($values[$key] ?? null)) {
                        $ids[] = (int) $values[$key];
                    }
                }
            }
        }

        if ($ids === []) {
            return [];
        }

        return User::withTrashed()
            ->whereKey(array_unique($ids))
            ->get(['id', 'name', 'username'])
            ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->username})"])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $names
     * @return array<string, mixed>
     */
    private function describeValues(array $values, array $names): array
    {
        foreach (self::USER_REFERENCE_KEYS as $key) {
            $id = $values[$key] ?? null;

            if (is_numeric($id) && isset($names[(int) $id])) {
                $values[$key] = $names[(int) $id];
            }
        }

        return $values;
    }

    private function labelForType(string $alias): string
    {
        return Str::ucfirst(str_replace('-', ' ', $alias));
    }
}
