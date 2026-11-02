<?php

namespace Database\Factories\Admin;

use App\Enums\RecordStatus;
use App\Models\Admin\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Codes start with `Q` / `X` — ranges ISO 3166 and 4217 keep for private
     * use — so a factory row never clashes with a seeded country.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city().' Land',
            'iso2' => fake()->unique()->regexify('Q[A-Z]'),
            'iso3' => fake()->unique()->regexify('Q[A-Z]{2}'),
            'numeric_code' => fake()->numerify('9##'),
            'dial_code' => '+'.fake()->numberBetween(200, 999),
            'currency_code' => fake()->regexify('X[A-Z]{2}'),
            'nationality' => ucfirst(fake()->word()),
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
