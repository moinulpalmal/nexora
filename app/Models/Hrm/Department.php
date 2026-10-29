<?php

namespace App\Models\Hrm;

use App\Concerns\Audited;
use App\Concerns\BelongsToCompany;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Observers\ActorObserver;
use Database\Factories\Hrm\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An HRM department — the first company-scoped master table
 * (ARCHITECTURE.md §9.10, §9.14).
 *
 * {@see BelongsToCompany} confines every query to the actor's selected company
 * and stamps it on create, so `company_id` is never in `#[Fillable]`: a value
 * posted from the browser has nowhere to land.
 *
 * @property int $id
 * @property int $company_id
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
class Department extends Model implements Auditable
{
    /** @use HasFactory<DepartmentFactory> */
    use Audited, BelongsToCompany, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The departments list's filter row. `company_id` has no cell: the list is
     * already one company.
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
    public const array SORTABLE = ['code', 'name', 'created_at'];
}
