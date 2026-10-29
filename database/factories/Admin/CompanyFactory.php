<?php

namespace Database\Factories\Admin;

use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'short_code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{2}'),
            'status' => RecordStatus::Active,
        ];
    }

    /**
     * A deactivated company — kept, but no longer selectable.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecordStatus::Inactive,
        ]);
    }
}
