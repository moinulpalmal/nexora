<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * The Projects setup lists moved under Projects → Settings (owner, 2026-11-04),
 * and their permissions moved with them. The rows are renamed, not reseeded: the
 * seeder only adds, so reseeding would leave the old names behind and lose what a
 * custom role was granted. A fresh database has no rows yet, so this does nothing.
 */
return new class extends Migration
{
    private const array RESOURCES = [
        'clients',
        'product-categories',
        'products',
        'supplier-categories',
        'suppliers',
        'tender-types',
        'units',
        'certification-categories',
        'certifications',
        'verification-items',
    ];

    private const array ACTIONS = ['view', 'create', 'update', 'delete', 'restore', 'force-delete'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->rename('projects.', 'projects.settings.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->rename('projects.settings.', 'projects.');
    }

    /**
     * Rename every moved permission from one prefix to the other.
     */
    private function rename(string $from, string $to): void
    {
        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                DB::table('permissions')
                    ->where('name', "{$from}{$resource}.{$action}")
                    ->update(['name' => "{$to}{$resource}.{$action}", 'updated_at' => now()]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
