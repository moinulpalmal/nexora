<?php

namespace App\Models;

use App\Concerns\Audited;
use App\Contracts\Auditable;
use App\Observers\ActorObserver;
use App\Services\Imports\ImportLimits;
use App\Services\Imports\ImportRunner;
use App\Services\Imports\SheetReader;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The mapped-import engine's runtime caps — one row, edited on Application
 * Settings → Import Limits (ARCHITECTURE.md §9.13).
 *
 * **Root of `app/Models/`, not `app/Models/Admin/`**, for the same reason
 * {@see SheetImport} sits here: the row is the *engine's* configuration, and
 * the engine is module-less per §4. The screen that edits it belongs to Admin,
 * which is a different question.
 *
 * Shaped like `Admin\QuickLoginSetting`: **no row is seeded**. {@see self::current()}
 * falls back to the compiled defaults in `config/sheet-import.php`, and the
 * first save creates the row (`Admin\ImportLimitService`). Two traits other
 * tables carry are deliberately absent: no `HasStatus` (a limit is not
 * retired — there is one row and it always applies) and no `Listable` (there
 * is no list).
 *
 * There is also **no factory**: tests save limits through the screen's route
 * or `ImportLimitService`, which is how the row comes to exist anyway.
 *
 * @property int $id
 * @property int $max_rows
 * @property int $max_columns
 * @property int $max_sheets
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $insertedBy
 * @property-read User|null $lastUpdatedBy
 */
#[ObservedBy(ActorObserver::class)]
#[Fillable(['max_rows', 'max_columns', 'max_sheets'])]
class ImportLimit extends Model implements Auditable
{
    use Audited;

    /** The row the migration inserts. There is never a second one. */
    public const int SINGLETON_ID = 1;

    /**
     * The one row, falling back to the compiled defaults when it is absent.
     *
     * **Deliberately not cached**: it is a one-row primary-key lookup read once
     * per file upload, not once per request, so there is nothing to amortise.
     */
    public static function current(): self
    {
        return static::query()->find(self::SINGLETON_ID)
            ?? new self(ImportLimits::fromConfig()->toArray());
    }

    /**
     * The value object {@see ImportRunner::stage()} hands {@see SheetReader}.
     */
    public function limits(): ImportLimits
    {
        return new ImportLimits(
            (int) $this->max_rows,
            (int) $this->max_columns,
            (int) $this->max_sheets,
        );
    }

    /** @return BelongsTo<User, $this> */
    public function insertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inserted_by');
    }

    /** @return BelongsTo<User, $this> */
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
            'max_rows' => 'integer',
            'max_columns' => 'integer',
            'max_sheets' => 'integer',
        ];
    }
}
