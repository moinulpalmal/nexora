<?php

namespace App\Models\Admin;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Models\Hrm\Department;
use App\Models\User;
use App\Observers\ActorObserver;
use Database\Factories\Admin\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One company of the organization (ARCHITECTURE.md §9.10). A global Admin master:
 * the same row for every user, and the dimension every ERP record is scoped by.
 *
 * Restorable, like users: a deleted company moves to the Deleted tab and keeps
 * its name and short code reserved until it is permanently deleted.
 *
 * @property int $id
 * @property string $name
 * @property string $short_code
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'short_code', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Company extends Model implements Auditable
{
    /** @use HasFactory<CompanyFactory> */
    use Audited, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The companies list's filter row.
     *
     * The short code is read left to right, so it matches by prefix and stays
     * index-seekable; a name is searched mid-word.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'short_code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'status' => FilterType::Equals,
    ];

    /**
     * @var list<string>
     */
    public const array SORTABLE = ['short_code', 'name', 'created_at'];

    /**
     * @return HasMany<Department, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * The users granted this company.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
