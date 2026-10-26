<?php

namespace Database\Seeders\Admin;

use App\Enums\RecordStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * One account per seeded role, for local development and quick login.
 *
 * Run only outside production (see `DatabaseSeeder`). Every password is
 * `password`, which the password policy would never accept through the UI —
 * that is the point: these accounts must never exist anywhere real.
 */
class DemoUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Username → account. Public because Quick Login offers these accounts.
     *
     * @var array<string, array{name: string, email: string, role: string}>
     */
    public const array ACCOUNTS = [
        'admin' => ['name' => 'Demo Admin', 'email' => 'admin@example.com', 'role' => 'admin'],
        'viewer' => ['name' => 'Demo Viewer', 'email' => 'viewer@example.com', 'role' => 'viewer'],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $username => $account) {
            User::withTrashed()->firstOrCreate(
                ['username' => $username],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => 'password',
                    'status' => RecordStatus::Active,
                ],
            )->syncRoles([$account['role']]);
        }
    }
}
