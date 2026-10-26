<?php

namespace Database\Seeders;

use Database\Seeders\Admin\DemoUserSeeder;
use Database\Seeders\Admin\RolePermissionSeeder;
use Database\Seeders\Admin\SuperAdminSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Every seeder here is idempotent, so `db:seed` is safe to re-run.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoUserSeeder::class);
        }
    }
}
