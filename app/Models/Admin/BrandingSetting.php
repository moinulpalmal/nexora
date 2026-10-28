<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Contracts\Auditable;
use App\Models\User;
use App\Observers\ActorObserver;
use App\Services\Admin\BrandingService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The uploaded logo and favicon — a single row. A null path means the
 * built-in default; {@see BrandingService} resolves what is actually shown.
 *
 * @property int $id
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $lastUpdatedBy
 */
#[ObservedBy(ActorObserver::class)]
#[Fillable(['logo_path', 'favicon_path'])]
class BrandingSetting extends Model implements Auditable
{
    use Audited;

    public const int SINGLETON_ID = 1;

    public static function current(): self
    {
        return static::query()->find(self::SINGLETON_ID) ?? new self;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
