<?php

namespace Database\Seeders\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Unit;
use Illuminate\Database\Seeder;

/**
 * The standard units of measure every installation starts with (owner,
 * 2026-11-02; ARCHITECTURE.md §9.14) — the second seeded master table, after
 * `Admin\CountrySeeder`, and shaped like it.
 *
 * **Idempotent and additive.** A unit whose code or name is already taken —
 * by any row, a deleted one included, compared ignoring case — is skipped, so
 * re-running `db:seed` never overwrites what someone edited and never collides
 * with a unit they renamed. A unit deleted permanently is added back.
 *
 * Seeder rows are console writes, so they are not audited (§9.3).
 */
class UnitSeeder extends Seeder
{
    /**
     * `[code, name, symbol, decimal places]`.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int}>
     */
    public const array UNITS = [
        ['PCS', 'Pieces', 'pcs', 0],
        ['NOS', 'Numbers', 'nos', 0],
        ['SET', 'Set', 'set', 0],
        ['PAIR', 'Pair', 'pr', 0],
        ['DOZEN', 'Dozen', 'dz', 0],
        ['BOX', 'Box', 'box', 0],
        ['BAG', 'Bag', 'bag', 0],
        ['ROLL', 'Roll', 'roll', 0],
        ['LS', 'Lump Sum', 'LS', 0],
        ['G', 'Gram', 'g', 2],
        ['KG', 'Kilogram', 'kg', 3],
        ['TON', 'Metric Ton', 't', 3],
        ['MM', 'Millimetre', 'mm', 0],
        ['CM', 'Centimetre', 'cm', 1],
        ['M', 'Metre', 'm', 2],
        ['KM', 'Kilometre', 'km', 3],
        ['RFT', 'Running Foot', 'rft', 2],
        ['SQM', 'Square Metre', 'm²', 2],
        ['SFT', 'Square Foot', 'sft', 2],
        ['CUM', 'Cubic Metre', 'm³', 3],
        ['ML', 'Millilitre', 'ml', 0],
        ['L', 'Litre', 'L', 2],
        ['HR', 'Hour', 'hr', 2],
        ['DAY', 'Day', 'day', 1],
        ['WEEK', 'Week', 'wk', 1],
        ['MONTH', 'Month', 'mo', 1],
        ['YEAR', 'Year', 'yr', 2],
    ];

    /**
     * Add every standard unit whose code and name are still free.
     */
    public function run(): void
    {
        $taken = Unit::withTrashed()->get(['code', 'name']);

        $codes = $taken->mapWithKeys(fn (Unit $unit): array => [mb_strtoupper($unit->code) => true])->all();
        $names = $taken->mapWithKeys(fn (Unit $unit): array => [mb_strtolower($unit->name) => true])->all();

        $now = now();
        $rows = [];

        foreach (self::UNITS as [$code, $name, $symbol, $decimals]) {
            if (isset($codes[$code]) || isset($names[mb_strtolower($name)])) {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'name' => $name,
                'symbol' => $symbol,
                'decimal_places' => $decimals,
                'status' => RecordStatus::Active->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Unit::query()->insert($rows);
    }
}
