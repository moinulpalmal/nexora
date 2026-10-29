<?php

namespace Database\Factories\Hrm;

use App\Enums\RecordStatus;
use App\Models\Admin\Company;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Employees always name their company, as {@see DepartmentFactory}, and their
 * department is made in that same company — an employee whose department
 * belongs elsewhere is a state validation never lets exist.
 *
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
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
            'employee_code' => fake()->unique()->regexify('E[0-9]{5}'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'department_id' => fn (array $attributes) => Department::factory()->state(['company_id' => $attributes['company_id']]),
            'designation_id' => Designation::factory(),
            'official_mobile_no' => null,
            'personal_mobile_no' => null,
            'joining_date' => null,
            'monthly_salary' => null,
            'status' => RecordStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecordStatus::Inactive,
        ]);
    }

    /**
     * Linked to a sign-in account carrying the employee's code and email, as
     * `EmployeeService` would create it.
     */
    public function withAccount(): static
    {
        return $this->afterCreating(function (Employee $employee): void {
            $user = User::factory()->create([
                'name' => $employee->name,
                'username' => mb_strtolower($employee->employee_code),
                'email' => $employee->email,
                'employee_code' => $employee->employee_code,
            ]);

            $user->companies()->attach($employee->company_id);

            $employee->forceFill(['user_id' => $user->id])->saveQuietly();
        });
    }
}
