<?php

namespace App\Models\ProjectManagement;

use App\Concerns\Audited;
use App\Concerns\BelongsToCompany;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Observers\ActorObserver;
use Database\Factories\ProjectManagement\VerificationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A verification item of one company (owner, 2026-11-03). Company-scoped, as
 * `ProductCategory` (ARCHITECTURE.md §9.10), but with no code — the name is
 * its key. What will use an item is still the owner's to name.
 *
 * {@see BelongsToCompany} confines every query to the actor's selected company
 * and stamps it on create, so `company_id` is never in `#[Fillable]`: a value
 * posted from the browser has nowhere to land.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'description', 'status'])]
#[ObservedBy(ActorObserver::class)]
class VerificationItem extends Model implements Auditable
{
    /** @use HasFactory<VerificationItemFactory> */
    use Audited, BelongsToCompany, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * `company_id` has no cell: the list is already one company. The
     * description is read in the form, not searched in the list.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'name' => FilterType::Contains,
        'status' => FilterType::Equals,
    ];

    /**
     * @var list<string>
     */
    public const array SORTABLE = ['name', 'status', 'created_at'];
}
