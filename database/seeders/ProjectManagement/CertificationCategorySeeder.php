<?php

namespace Database\Seeders\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\ProjectManagement\CertificationCategory;
use Illuminate\Database\Seeder;

/**
 * The standard certification categories every installation starts with
 * (owner, 2026-11-02; ARCHITECTURE.md §9.14), shaped as {@see UnitSeeder}.
 * Runs before {@see CertificationSeeder}, whose rows point here by code.
 *
 * **Idempotent and additive.** A category whose code or name is already
 * taken — by any row, a deleted one included, compared ignoring case — is
 * skipped, so re-running `db:seed` never overwrites what someone edited. A
 * category deleted permanently is added back.
 *
 * Seeder rows are console writes, so they are not audited (§9.3).
 */
class CertificationCategorySeeder extends Seeder
{
    /**
     * `[code, name]`.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const array CATEGORIES = [
        ['QMS', 'Quality Management'],
        ['EMS', 'Environmental Management'],
        ['OHS', 'Occupational Health & Safety'],
        ['ISMS', 'Information Security'],
        ['ENMS', 'Energy Management'],
        ['FSMS', 'Food Safety'],
        ['LAB', 'Laboratory & Testing'],
        ['SOC', 'Social Compliance'],
        ['TXT', 'Textile & Apparel'],
        ['ELEC', 'Electrical & Electronics'],
        ['STEEL', 'Steel & Metals'],
        ['PIPE', 'Pipes & Plastics'],
        ['MACH', 'Machinery & Pressure Equipment'],
        ['RADAR', 'Radar & Telecom'],
        ['NAT', 'National Standards (BSTI/BDS)'],
        ['OTHER', 'Other'],
    ];

    /**
     * Add every standard category whose code and name are still free.
     */
    public function run(): void
    {
        $taken = CertificationCategory::withTrashed()->get(['code', 'name']);

        $codes = $taken->mapWithKeys(fn (CertificationCategory $category): array => [mb_strtoupper($category->code) => true])->all();
        $names = $taken->mapWithKeys(fn (CertificationCategory $category): array => [mb_strtolower($category->name) => true])->all();

        $now = now();
        $rows = [];

        foreach (self::CATEGORIES as [$code, $name]) {
            if (isset($codes[$code]) || isset($names[mb_strtolower($name)])) {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'name' => $name,
                'status' => RecordStatus::Active->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        CertificationCategory::query()->insert($rows);
    }
}
