<?php

namespace Database\Factories\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\ProjectManagement\TenderType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenderType>
 */
class TenderTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{2}'),
            'name' => fake()->unique()->words(3, true),
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
