<?php

namespace Database\Factories\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\VerificationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Verification items always name their company, as `ProductCategoryFactory`:
 * the model's scope would otherwise stamp the signed-in user's selection.
 *
 * @extends Factory<VerificationItem>
 */
class VerificationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(3, true),
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
