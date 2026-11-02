<?php

namespace App\Models\ProjectManagement;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Observers\ActorObserver;
use Database\Factories\ProjectManagement\TenderTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A tender type — how a project was procured (open tendering, RFQ, direct
 * procurement…). A **group-wide** master table, as `Hrm\Designation`: a
 * procurement method is a classification, not a fact about one company, so
 * there is no `company_id` and no `BelongsToCompany` (owner, 2026-11-02;
 * ARCHITECTURE.md §9.10, §9.14).
 *
 * Restorable: a deleted tender type moves to the Deleted tab and keeps its
 * code and name reserved.
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
class TenderType extends Model implements Auditable
{
    /** @use HasFactory<TenderTypeFactory> */
    use Audited, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The tender types list's filter row: a code is typed from its start, a
     * name searched mid-word.
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
