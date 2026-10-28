<?php

namespace App\Models;

use App\Concerns\Audited;
use App\Contracts\Auditable;
use App\Enums\Imports\ImportStatus;
use App\Enums\Imports\SheetFileType;
use App\Observers\ActorObserver;
use App\Services\Imports\ImportBlueprint;
use Database\Factories\SheetImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One uploaded spreadsheet, staged against an {@see ImportBlueprint}
 * and (eventually) resolved into rows on the target model.
 *
 * The engine's own record, module-less like the rest of `app/Services/Imports/`
 * (ARCHITECTURE.md §4, §9.13) — this sits at the root of `app/Models/`, alongside
 * `User`, for that reason. `import_key` names the blueprint this row was staged
 * against (`hrm.departments`), resolved through `ImportRegistry`; it is **not**
 * a morph, and this model carries no polymorphic relation to whatever it
 * eventually creates.
 *
 * **`company_id` is pre-wired and has no relation yet** (ARCHITECTURE.md §9.10):
 * the column exists so the first company-scoped importer needs no migration on
 * this table, but there is no `companies` table to point a foreign key or a
 * `belongsTo` at. The Company master adds both. Until then it is always `null`.
 *
 * @property int $id
 * @property string $import_key
 * @property int|null $company_id
 * @property int|null $scope_id
 * @property string $source_file_name
 * @property string|null $stored_path
 * @property SheetFileType $detected_file_type
 * @property int $sheet_count
 * @property string $source_hash
 * @property ImportStatus $status
 * @property int $row_count
 * @property int $created_count
 * @property int $updated_count
 * @property int $skipped_count
 * @property int $rejected_count
 * @property list<array{sheet: string, line: int, message: string}>|null $rejected_rows set only by an `Imports\ImportRowWriter`'s partial import
 * @property array<int, array<string, int>>|null $column_map worksheet index => field key => column index
 * @property string|null $on_duplicate
 * @property array<string, mixed>|null $payload
 * @property array{version: int, sheets: list<array{index: int, name: string, headers: list<string>, rows: list<list<string|null>>}>}|null $staged_rows
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $insertedBy
 */
#[ObservedBy(ActorObserver::class)]
#[Fillable([
    'import_key', 'company_id', 'scope_id', 'source_file_name', 'stored_path', 'detected_file_type',
    'sheet_count', 'source_hash', 'status', 'row_count', 'created_count', 'updated_count',
    'skipped_count', 'rejected_count', 'rejected_rows', 'column_map', 'on_duplicate', 'payload', 'staged_rows',
])]
class SheetImport extends Model implements Auditable
{
    /** @use HasFactory<SheetImportFactory> */
    use Audited, HasFactory;

    /**
     * Columns the audit trail records the *change* of, never the content.
     *
     * `payload` and `staged_rows` together hold the sheet's headers and every
     * row — auditing them would make the trail a second copy of the
     * spreadsheet.
     * `column_map` and `on_duplicate` are deliberately **not** here: they are
     * the decision the trail exists to record, small, and exactly what
     * answers "why did this column land in that field?" later.
     * `rejected_rows` is excluded too — a partial import's reasons, one per
     * rejected row, which `rejected_count` already summarises.
     *
     * @var array<int, string>
     */
    protected $auditExclude = ['payload', 'staged_rows', 'rejected_rows'];

    /**
     * Imports still awaiting a confirmed mapping.
     *
     * @param  Builder<static>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ImportStatus::Pending);
    }

    /**
     * The user who uploaded the spreadsheet, if there was an authenticated actor.
     *
     * @return BelongsTo<User, $this>
     */
    public function insertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inserted_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'detected_file_type' => SheetFileType::class,
            'status' => ImportStatus::class,
            'column_map' => 'array',
            'payload' => 'array',
            'staged_rows' => 'array',
            'rejected_rows' => 'array',
        ];
    }
}
