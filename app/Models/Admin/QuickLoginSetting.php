<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Contracts\Auditable;
use App\Models\User;
use App\Observers\ActorObserver;
use App\Services\Admin\QuickLoginService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The development quick-login switch — a single row. See
 * {@see QuickLoginService} for when the buttons actually render.
 *
 * @property int $id
 * @property bool $enabled
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $lastUpdatedBy
 */
#[ObservedBy(ActorObserver::class)]
#[Fillable(['enabled'])]
class QuickLoginSetting extends Model implements Auditable
{
    use Audited;

    public const int SINGLETON_ID = 1;

    public static function current(): self
    {
        return static::query()->find(self::SINGLETON_ID) ?? new self(['enabled' => false]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }
}
