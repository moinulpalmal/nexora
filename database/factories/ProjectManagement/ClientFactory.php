<?php

namespace Database\Factories\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Clients always name their company: `company_id` is not fillable and the
 * model's scope would otherwise stamp the signed-in user's selection — which a
 * factory call in a test should never depend on (as `DepartmentFactory`).
 *
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'name' => fake()->unique()->company(),
            'address' => fake()->address(),
            'description' => null,
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('+880 17## ######'),
            'email' => fake()->unique()->companyEmail(),
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
