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
use Database\Factories\ProjectManagement\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A supplier the organization buys from — the ERP's one supplier list
 * (owner, 2026-11-02): Projects keeps it, and Accounting's bills will point at
 * it. Company-scoped and restorable, as {@see Client} (ARCHITECTURE.md §9.10),
 * with a group-wide {@see SupplierCategory} — the pairing of
 * `Hrm\Employee` and `Hrm\Designation`.
 *
 * {@see BelongsToCompany} confines every query to the actor's selected company
 * and stamps it on create, so `company_id` is never in `#[Fillable]`.
 *
 * @property int $id
 * @property int $company_id
 * @property int $supplier_category_id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $description
 * @property string|null $contact_person
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $tin
 * @property string|null $bin
 * @property string|null $trade_license_no
 * @property string|null $bank_name
 * @property string|null $bank_branch
 * @property string|null $bank_account_name
 * @property string|null $bank_account_number
 * @property string|null $bank_routing_number
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read SupplierCategory $category
 */
#[Fillable([
    'supplier_category_id', 'code', 'name', 'address', 'description', 'contact_person', 'phone', 'email',
    'tin', 'bin', 'trade_license_no',
    'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number', 'bank_routing_number',
    'status',
])]
#[ObservedBy(ActorObserver::class)]
class Supplier extends Model implements Auditable
{
    /** @use HasFactory<SupplierFactory> */
    use Audited, BelongsToCompany, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The suppliers list's filter row, as {@see Client::FILTERABLE} plus the
     * category (a combobox, so `Equals` on the foreign key) and the tax
     * numbers, typed from their start. `company_id` has no cell: the list is
     * already one company. Address, description, the trade licence and the
     * bank details are read in the form, not searched in the list.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'supplier_category_id' => FilterType::Equals,
        'contact_person' => FilterType::Contains,
        'phone' => FilterType::Prefix,
        'email' => FilterType::Contains,
        'tin' => FilterType::Prefix,
        'bin' => FilterType::Prefix,
        'status' => FilterType::Equals,
    ];

    /**
     * As clients': the optional columns are filtered, not sorted, and the
     * category stays out — the lists never sort on a joined column (§8.6).
     *
     * @var list<string>
     */
    public const array SORTABLE = ['code', 'name', 'created_at'];

    /**
     * @return BelongsTo<SupplierCategory, $this>
     */
    public function category(): BelongsTo
    {
        // A deleted category still names its suppliers until it is moved off.
        return $this->belongsTo(SupplierCategory::class, 'supplier_category_id')->withTrashed();
    }
}
