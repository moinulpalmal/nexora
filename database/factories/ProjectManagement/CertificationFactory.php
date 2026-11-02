<?php

namespace Database\Factories\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Certification;
use App\Models\ProjectManagement\CertificationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'certification_category_id' => CertificationCategory::factory(),
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{4}'),
            'name' => fake()->unique()->words(3, true),
            'issuing_body' => null,
            'version' => null,
            'description' => null,
            'status' => RecordStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecordStatus::Inactive,
        ]);
    }
}
