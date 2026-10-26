<?php

namespace App\Models;

use App\Concerns\HasStatus;
use App\Enums\RecordStatus;
use App\Enums\Theme;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasStatus, Notifiable, SoftDeletes;

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
