<?php

namespace App\Models\ProjectManagement;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Observers\ActorObserver;
use Database\Factories\ProjectManagement\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A unit of measure — what a quantity is counted in (pieces, kilograms,
 * square metres, hours…). A **group-wide** master table, as `TenderType`: a
 * kilogram is the same in every company, so there is no `company_id` and no
 * `BelongsToCompany` (owner, 2026-11-02; ARCHITECTURE.md §9.10, §9.14).
 *
 * Restorable: a deleted unit moves to the Deleted tab and keeps its code and
 * name reserved. `UnitSeeder` ships a standard set (§9.14).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $symbol
 * @property int $decimal_places
 * @property string|null $description
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['code', 'name', 'symbol', 'decimal_places', 'description', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Unit extends Model implements Auditable
{
    /** @use HasFactory<UnitFactory> */
    use Audited, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The units list's filter row: a code is typed from its start, a name or
     * symbol searched mid-word. Decimal places are sorted, not filtered — there
     * are only seven values.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'symbol' => FilterType::Contains,
        'status' => FilterType::Equals,
    ];

    /**
     * @var list<string>
     */
    public const array SORTABLE = ['code', 'name', 'symbol', 'decimal_places', 'status', 'created_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
        ];
    }
}
