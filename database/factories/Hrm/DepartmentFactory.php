<?php

namespace Database\Factories\Hrm;

use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Departments always name their company: `company_id` is not fillable and the
 * model's scope would otherwise stamp the signed-in user's selection — which a
 * factory call in a test should never depend on.
 *
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
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
            'code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{3}'),
            'name' => fake()->unique()->jobTitle().' department',
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
