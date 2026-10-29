<?php

namespace App\Models\Hrm;

use App\Concerns\Audited;
use App\Concerns\BelongsToCompany;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Models\User;
use App\Observers\ActorObserver;
use Database\Factories\Hrm\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An HRM employee — company-scoped and restorable, as {@see Department}
 * (ARCHITECTURE.md §9.10).
 *
 * **An employee may be an application user** (owner, 2026-10-29, reversing
 * the 2026-10-28 rule that an employee never creates an account): `user_id`
 * links the sign-in account `EmployeeService` creates when the form's box is
 * ticked. There is no stored flag — "is an application user" is *a linked
 * account that is active* ({@see self::isApplicationUser()}), so the flag and
 * the link can never disagree.
 *
 * `company_id` and `user_id` are never in `#[Fillable]`: the scope stamps the
 * company, and the service alone links an account.
 *
 * @property int $id
 * @property int $company_id
 * @property string $employee_code
 * @property string $name
 * @property string $email
 * @property int $department_id
 * @property int $designation_id
 * @property string|null $official_mobile_no
 * @property string|null $personal_mobile_no
 * @property Carbon|null $joining_date
 * @property string|null $monthly_salary
 * @property int|null $user_id
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Department $department
 * @property-read Designation $designation
 * @property-read User|null $user
 */
#[Fillable(['employee_code', 'name', 'email', 'department_id', 'designation_id', 'official_mobile_no', 'personal_mobile_no', 'joining_date', 'monthly_salary', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Employee extends Model implements Auditable
{
    /** @use HasFactory<EmployeeFactory> */
    use Audited, BelongsToCompany, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The employees list's filter row. Codes and phone numbers are read left
     * to right (`Prefix`, index-friendly); names and emails are searched
     * mid-word. Department and designation are comboboxes, so `Equals` on the
     * foreign key. `company_id` has no cell: the list is already one company.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'employee_code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'email' => FilterType::Contains,
        'department_id' => FilterType::Equals,
        'designation_id' => FilterType::Equals,
        'official_mobile_no' => FilterType::Prefix,
        'status' => FilterType::Equals,
    ];

    /**
     * The foreign keys stay out: the lists never sort on a joined column —
     * filter by department or designation instead (§8.6).
     *
     * @var list<string>
     */
    public const array SORTABLE = ['employee_code', 'name', 'email', 'joining_date', 'created_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        // Without the company scope: the department is the employee's own
        // company's by validation, and a console reader has no selection.
        return $this->belongsTo(Department::class)->withoutGlobalScope('company')->withTrashed();
    }

    /**
     * @return BelongsTo<Designation, $this>
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class)->withTrashed();
    }

    /**
     * The sign-in account, trashed ones included — the link outlives a soft
     * delete in Admin.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * What the form's "Application user" box shows: an account is linked and
     * it can sign in.
     */
    public function isApplicationUser(): bool
    {
        return $this->user !== null && $this->user->isActive() && ! $this->user->trashed();
    }
}
