<?php

namespace Database\Factories\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Product categories always name their company, as `ClientFactory`: the
 * model's scope would otherwise stamp the signed-in user's selection.
 *
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
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
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
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
