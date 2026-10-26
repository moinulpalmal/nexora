<?php

namespace Database\Seeders\Admin;

use App\Enums\RecordStatus;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the first account and gives it the super-admin role.
 *
 * Credentials come from `config('auth.super_admin')` (the `SUPER_ADMIN_*` env
 * keys). Idempotent: an existing account with that username keeps its password
 * and only regains the role, so re-seeding never resets a changed password.
 *
 * Requires {@see RolePermissionSeeder} to have run first.
 */
class SuperAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        /** @var array{name: string, username: string, email: string, password: ?string} $config */
        $config = config('auth.super_admin');

        if (blank($config['password']) && app()->isProduction()) {
            throw new RuntimeException('Set SUPER_ADMIN_PASSWORD before seeding a production database.');
        }

        $user = User::withTrashed()->firstOrCreate(
            ['username' => mb_strtolower($config['username'])],
            [
                'name' => $config['name'],
                'email' => $config['email'],
                'password' => $config['password'] ?: 'password',
                'status' => RecordStatus::Active,
            ],
        );

        $user->assignRole(Role::SUPER_ADMIN);
    }
}
