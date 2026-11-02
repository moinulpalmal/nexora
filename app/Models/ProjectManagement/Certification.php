<?php

namespace App\Models\ProjectManagement;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Observers\ActorObserver;
use Database\Factories\ProjectManagement\CertificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A certification — a standard a supplier may hold or a tender may demand:
 * ISO 9001, amfori BSCI, CE, the BSTI mark. A catalogue of standards, not a
 * certificate anyone holds (owner, 2026-11-02): a held certificate, with its
 * number and expiry, will be a table of its own pointing here.
 *
 * A **group-wide** master table, as {@see TenderType}: a standard is the same
 * for every company, so there is no `company_id` and no `BelongsToCompany`
 * (ARCHITECTURE.md §9.10, §9.14). Classified by a required group-wide
 * {@see CertificationCategory} — the pairing of {@see Supplier} and
 * {@see SupplierCategory}.
 *
 * Restorable: a deleted certification moves to the Deleted tab and keeps its
 * code, and its name within its version, reserved. Nothing points here yet,
 * so `CertificationService` has no deletion guard.
 *
 * @property int $id
 * @property int $certification_category_id
 * @property string $code
 * @property string $name
 * @property string|null $issuing_body
 * @property string|null $version
 * @property string|null $description
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read CertificationCategory $category
 */
#[Fillable(['certification_category_id', 'code', 'name', 'issuing_body', 'version', 'description', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Certification extends Model implements Auditable
{
    /** @use HasFactory<CertificationFactory> */
    use Audited, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The certifications list's filter row: a code is typed from its start, a
     * name or an issuing body searched mid-word, the category a combobox
     * (`Equals` on the foreign key). A version is short and read left to
     * right, so `Prefix`. The description is read in the form.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'certification_category_id' => FilterType::Equals,
        'issuing_body' => FilterType::Contains,
        'version' => FilterType::Prefix,
        'status' => FilterType::Equals,
    ];

    /**
     * The category stays out — the lists never sort on a joined column (§8.6).
     *
     * @var list<string>
     */
    public const array SORTABLE = ['code', 'name', 'issuing_body', 'version', 'status', 'created_at'];

    /**
     * @return BelongsTo<CertificationCategory, $this>
     */
    public function category(): BelongsTo
    {
        // A deleted category still names its certifications until they are moved off.
        return $this->belongsTo(CertificationCategory::class, 'certification_category_id')->withTrashed();
    }
}
