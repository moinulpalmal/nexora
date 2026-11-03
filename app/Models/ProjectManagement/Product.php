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
use Database\Factories\ProjectManagement\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A product of one company, in one of that company's
 * {@see ProductCategory} rows (owner, 2026-11-03). Company-scoped and
 * restorable, shaped as {@see Supplier} — except that its category is kept per
 * company too, not group-wide.
 *
 * {@see BelongsToCompany} confines every query to the actor's selected company
 * and stamps it on create, so `company_id` is never in `#[Fillable]`.
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_category_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ProductCategory $category
 */
#[Fillable(['product_category_id', 'code', 'name', 'description', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Product extends Model implements Auditable
{
    /** @use HasFactory<ProductFactory> */
    use Audited, BelongsToCompany, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The category is a combobox, so `Equals` on the foreign key, as
     * {@see Supplier::FILTERABLE}. `company_id` has no cell: the list is
     * already one company. The description is read in the form only.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'product_category_id' => FilterType::Equals,
        'status' => FilterType::Equals,
    ];

    /**
     * The category stays out — the lists never sort on a joined column (§8.6).
     *
     * @var list<string>
     */
    public const array SORTABLE = ['code', 'name', 'status', 'created_at'];

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        // A deleted category still names its products until they are moved off.
        return $this->belongsTo(ProductCategory::class, 'product_category_id')->withTrashed();
    }
}
