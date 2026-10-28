<?php

namespace Database\Factories;

use App\Enums\Imports\ImportStatus;
use App\Enums\Imports\SheetFileType;
use App\Models\SheetImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A staged import, as `ImportRunner::stage()` leaves one.
 *
 * The default `import_key` is the feature tests' fixture blueprint
 * (`tests.widgets`), because no shipped table imports yet; a test for a real
 * importer overrides it. The staged sheet is a two-column code/name list —
 * the shape almost every master table's import has.
 *
 * @extends Factory<SheetImport>
 */
class SheetImportFactory extends Factory
{
    protected $model = SheetImport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_key' => 'tests.widgets',
            'company_id' => null,
            'scope_id' => null,
            'source_file_name' => 'Widgets '.fake()->bothify('??####').'.xlsx',
            'stored_path' => null,
            'detected_file_type' => SheetFileType::Xlsx,
            'sheet_count' => 1,
            'source_hash' => hash('sha256', fake()->unique()->uuid()),
            'status' => ImportStatus::Pending,
            'row_count' => 1,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'rejected_count' => 0,
            'rejected_rows' => null,
            'column_map' => null,
            'on_duplicate' => null,
            'payload' => [
                'sheets' => [[
                    'index' => 0,
                    'name' => 'Sheet1',
                    'headers' => ['Code', 'Name'],
                    'suggested_map' => ['code' => 0, 'name' => 1],
                    'sample_rows' => [['W-001', 'First widget']],
                    'row_count' => 1,
                ]],
                'skipped_sheets' => [],
                'warnings' => [],
            ],
            'staged_rows' => [
                'version' => 2,
                'sheets' => [[
                    'index' => 0,
                    'name' => 'Sheet1',
                    'headers' => ['Code', 'Name'],
                    'rows' => [['W-001', 'First widget']],
                ]],
            ],
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ImportStatus::Confirmed,
            'column_map' => [0 => ['code' => 0, 'name' => 1]],
            'on_duplicate' => 'skip',
            'created_count' => 1,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ImportStatus::Cancelled,
        ]);
    }
}
