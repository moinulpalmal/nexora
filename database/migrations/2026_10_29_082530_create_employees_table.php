<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HRM employees — company-scoped and restorable, like `departments`
 * (ARCHITECTURE.md §9.10, §9.14).
 *
 * `employee_code` and `email` are unique **across the whole group**, deleted
 * rows included (owner, 2026-10-29): the code becomes the sign-in username of
 * an employee who is an application user, and usernames are global. Both
 * uniques are plain single-column indexes for that reason, not led by
 * `company_id`.
 *
 * Every foreign key into master data restricts on delete, so the database
 * refuses what `CompanyService`, `DepartmentService` and `DesignationService`
 * already refuse in words. `user_id` is the optional sign-in account — unique,
 * because one account is one person, and `nullOnDelete` so purging a user in
 * Admin leaves the employee on record.
 *
 * `(company_id, deleted_at, status)` serves every scoped list; the FK indexes
 * on `department_id` / `designation_id` serve the deletion guards' counts.
 * There is deliberately no index on the mobile numbers: they are filtered by
 * prefix within one company's list, which the composite already narrows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('employee_code', 30)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('designation_id')->constrained()->restrictOnDelete();
            $table->string('official_mobile_no', 30)->nullable();
            $table->string('personal_mobile_no', 30)->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('monthly_salary', 12, 2)->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->char('status', 1)->default('A');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'deleted_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
