<?php

namespace Database\Factories\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\Supplier;
use App\Models\ProjectManagement\SupplierCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Suppliers always name their company, as `ClientFactory`: `company_id` is not
 * fillable and the model's scope would otherwise stamp the signed-in user's
 * selection — which a factory call in a test should never depend on.
 *
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
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
            'supplier_category_id' => SupplierCategory::factory(),
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'name' => fake()->unique()->company(),
            'address' => fake()->address(),
            'description' => null,
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('+880 17## ######'),
            'email' => fake()->unique()->companyEmail(),
            'tin' => fake()->numerify('############'),
            'bin' => fake()->numerify('#########-####'),
            'trade_license_no' => null,
            'bank_name' => null,
            'bank_branch' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'bank_routing_number' => null,
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
