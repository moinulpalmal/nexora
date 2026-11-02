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
use Database\Factories\ProjectManagement\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A client the organization runs projects for — the ERP's one customer list
 * (owner, 2026-11-02): Projects keeps it, and Accounting's invoices will point
 * at it. Company-scoped, as `Hrm\Department` (ARCHITECTURE.md §9.10).
 *
 * {@see BelongsToCompany} confines every query to the actor's selected company
 * and stamps it on create, so `company_id` is never in `#[Fillable]`: a value
 * posted from the browser has nowhere to land.
 *
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $description
 * @property string|null $contact_person
 * @property string|null $phone
 * @property string|null $email
 * @property RecordStatus $status
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['code', 'name', 'address', 'description', 'contact_person', 'phone', 'email', 'status'])]
#[ObservedBy(ActorObserver::class)]
class Client extends Model implements Auditable
{
    /** @use HasFactory<ClientFactory> */
    use Audited, BelongsToCompany, HasFactory, HasStatus, Listable, SoftDeletes;

    /**
     * The clients list's filter row. `company_id` has no cell: the list is
     * already one company. A phone number is typed from its start, so it is a
     * prefix; a contact's name and an email are searched mid-word. Address and
     * description are read in the form, not searched in the list.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'code' => FilterType::Prefix,
        'name' => FilterType::Contains,
        'contact_person' => FilterType::Contains,
        'phone' => FilterType::Prefix,
        'email' => FilterType::Contains,
        'status' => FilterType::Equals,
    ];

    /**
     * The contact columns are filtered, not sorted: they are optional, and a
     * list ordered by a mostly empty column reads as unordered.
     *
     * @var list<string>
     */
    public const array SORTABLE = ['code', 'name', 'created_at'];
}
