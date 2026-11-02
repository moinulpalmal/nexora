<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Models\Hrm\Designation;
use App\Observers\ActorObserver;
use Database\Factories\Admin\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A country — a **group-wide** master table: the same row for every company,
 * so there is no `company_id` and no `BelongsToCompany`, as
 * {@see Designation} (owner, 2026-11-02; ARCHITECTURE.md §9.14).
 *
 * Restorable: a deleted country moves to the Deleted tab and keeps its name
 * and ISO codes reserved. The ISO 3166-1 list is seeded by
 * `Database\Seeders\Admin\CountrySeeder`.
 *
 * @property int $id
 * @property string $name
 * @property string $iso2
 * @property string $iso3
 * @property string|null $numeric_code
 * @property string|null $dial_code
 * @property string|null $currency_code
 * @property string|null $nationality
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'iso2', 'iso3', 'numeric_code', 'dial_code', 'currency_code', 'nationality', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Country extends Model implements Auditable
{
    /** @use HasFactory<CountryFactory> */
    use Audited, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The countries list's filter row. Codes are read left to right, so they
     * are `Prefix` (`+88` finds Bangladesh); names are searched mid-word.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'iso2' => FilterType::Prefix,
        'iso3' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'dial_code' => FilterType::Prefix,
        'currency_code' => FilterType::Prefix,
        'nationality' => FilterType::Contains,
        'status' => FilterType::Equals,
    ];

    /**
     * `nationality` is filtered, not sorted — the list reads by country.
     *
     * @var list<string>
     */
    public const array SORTABLE = ['iso2', 'iso3', 'name', 'dial_code', 'currency_code', 'status', 'created_at'];
}
