<?php

namespace App\Models\Hrm;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Observers\ActorObserver;
use Database\Factories\Hrm\DesignationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An HRM designation (job title) — a **group-wide** master table: the same
 * row for every company, so there is no `company_id` and no
 * `BelongsToCompany` (owner, 2026-10-29; ARCHITECTURE.md §9.10, §9.14).
 *
 * Restorable, like {@see Company}: a deleted designation
 * moves to the Deleted tab and keeps its code and name reserved.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['code', 'name', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Designation extends Model implements Auditable
{
    /** @use HasFactory<DesignationFactory> */
    use Audited, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The designations list's filter row.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'status' => FilterType::Equals,
    ];

    /**
     * @var list<string>
     */
    public const array SORTABLE = ['code', 'name', 'status', 'created_at'];
}
