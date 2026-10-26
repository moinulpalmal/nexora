<?php

namespace App\Models;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Concerns\Listable;
use App\Contracts\Auditable;
use App\Enums\FilterType;
use App\Enums\RecordStatus;
use App\Enums\Theme;
use App\Observers\ActorObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $phone
 * @property string|null $employee_code
 * @property string|null $avatar_path
 * @property-read string|null $avatar_url
 * @property RecordStatus $status
 * @property Theme|null $theme
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'username', 'email', 'password', 'phone', 'employee_code', 'status', 'theme'])]
#[Hidden(['password', 'remember_token'])]
#[Appends(['avatar_url'])]
#[ObservedBy(ActorObserver::class)]
class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use Audited, HasFactory, HasRoles, HasStatus, Listable, Notifiable, SoftDeletes;

    /**
     * The users list's filter row.
     *
     * Identifiers match by prefix (indexable); names and emails by substring.
     * `role` is not a column: `Scope` hands it to spatie's own `role()` scope.
     *
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'name' => FilterType::Contains,
        'username' => FilterType::Prefix,
        'email' => FilterType::Contains,
        'employee_code' => FilterType::Prefix,
        'phone' => FilterType::Prefix,
        'status' => FilterType::Equals,
        'role' => FilterType::Scope,
    ];

    /**
     * The columns the users list may be sorted by.
     *
     * @var list<string>
     */
    public const array SORTABLE = ['name', 'username', 'email', 'employee_code', 'last_login_at', 'created_at'];

    /**
     * Never written to the trail.
     *
     * Strict mode is off, so `$hidden` is NOT honoured by the auditor — the
     * password hash would otherwise land in `old_values`/`new_values`. A password
     * change is still recorded, as an `updated` row with no values or, from the
     * admin screen, as its own `password-reset` event. Sign-in stamps are saved
     * quietly and excluded so a stray save never diffs them.
     *
     * @var list<string>
     */
    protected array $auditExclude = ['password', 'remember_token', 'last_login_at', 'last_login_ip'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'theme' => Theme::class,
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Stored lowercased, so sign-in by username is case-insensitive on every
     * database driver (SQLite compares case-sensitively; MySQL does not).
     *
     * @return Attribute<string, string>
     */
    protected function username(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => mb_strtolower(trim($value)));
    }

    /**
     * Stored lowercased for the same reason as {@see self::username()}.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => mb_strtolower(trim($value)));
    }

    /**
     * The avatar's public URL, or null to fall back to initials.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_path === null
            ? null
            : Storage::disk('public')->url($this->avatar_path));
    }
}
