<?php

namespace App\Services\Hrm;

use App\Enums\Admin\AuditEvent;
use App\Enums\RecordStatus;
use App\Models\Hrm\Employee;
use App\Models\User;
use App\Services\Admin\AuditRecorder;
use App\Services\Admin\UserCredentialMailer;
use App\Services\Admin\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Every write to an employee — from the form and, row by row, from the
 * spreadsheet import ({@see EmployeeImportBlueprint}) — and the sign-in
 * account an employee may have (owner, 2026-10-29).
 *
 * **The account is written through {@see UserService}**, never directly, so
 * it is audited like an Admin change and its guards still bind: unticking the
 * box, deactivating or deleting the employee of the last active super admin is
 * refused by {@see self::accountBlocker()}, which defers to
 * `UserService::statusBlocker()`.
 *
 * The account follows the employee:
 *
 * - created when the box is ticked for an active employee — username the
 *   lowercased code, no role, the employee's company granted, a temporary
 *   password that must be changed at first sign-in (`must_change_password`,
 *   set by `UserService`), emailed with the welcome mail;
 * - kept in step on every save: name, email, official mobile → phone, code →
 *   employee code and username;
 * - deactivated (never deleted) when the box is unticked or the employee goes
 *   inactive or is deleted; re-ticking re-activates the same account with a
 *   new temporary password. Restoring an employee does not re-open it.
 *
 * **No `deletionBlocker()`** (ARCHITECTURE.md §9.14): nothing references an
 * employee yet. Projects members and timesheets will bring the first clause.
 */
class EmployeeService
{
    /**
     * @var list<array{user: User, password: string, reactivated: bool}>
     */
    private array $pendingCredentials = [];

    public function __construct(
        private readonly AuditRecorder $audits,
        private readonly UserService $users,
        private readonly UserCredentialMailer $credentials,
    ) {}

    /**
     * Create an employee in the given company — the selected company from the
     * form, the staged import's company from a sheet (§9.10).
     *
     * @param  array<string, mixed>  $attributes
     * @return string|null the address the sign-in details were emailed to, if an account was created
     */
    public function create(int $companyId, array $attributes, bool $applicationUser = false, ?string $password = null): ?string
    {
        $this->pendingCredentials = [];

        DB::transaction(function () use ($companyId, $attributes, $applicationUser, $password): void {
            $employee = new Employee($attributes);
            $employee->company_id = $companyId;
            $employee->save();

            if ($applicationUser && $employee->isActive() && $password !== null) {
                $this->openAccount($employee, $password);
            }
        });

        return $this->sendCredentials();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return string|null the address the sign-in details were emailed to, if an account was created or re-opened
     */
    public function update(Employee $employee, array $attributes, ?bool $applicationUser = null, ?string $password = null): ?string
    {
        $this->pendingCredentials = [];

        DB::transaction(function () use ($employee, $attributes, $applicationUser, $password): void {
            $applicationUser ??= $employee->isApplicationUser();

            $employee->update($attributes);

            $this->reconcileAccount($employee, $applicationUser && $employee->isActive(), $password);
        });

        return $this->sendCredentials();
    }

    /**
     * Soft delete: the employee moves to Deleted, keeps their code and email
     * reserved, and their account stops signing in.
     */
    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $employee->delete();

            $this->closeAccount($employee);
        });
    }

    /**
     * The account stays closed: re-opening it needs a new temporary password,
     * which only the form can supply.
     */
    public function restore(Employee $employee): void
    {
        $employee->restore();
    }

    /**
     * Delete for good, recording who the employee was first. The account is
     * closed and stays in Admin, unlinked.
     */
    public function forceDelete(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $this->closeAccount($employee);

            $this->audits->record($employee, AuditEvent::ForceDeleted, [
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'email' => $employee->email,
                'company_id' => $employee->company_id,
                'user_id' => $employee->user_id,
            ]);

            $employee->forceDelete();
        });
    }

    /**
     * Soft delete each employee, one at a time so each is audited. Those whose
     * account may not be closed are left alone and not counted.
     *
     * @param  iterable<Employee>  $employees
     */
    public function bulkDelete(iterable $employees): int
    {
        $count = 0;

        foreach ($employees as $employee) {
            if ($this->accountBlocker($employee, false) !== null) {
                continue;
            }

            $this->delete($employee);
            $count++;
        }

        return $count;
    }

    /**
     * Delete each soft-deleted employee for good, recording each.
     *
     * @param  iterable<Employee>  $employees
     */
    public function bulkForceDelete(iterable $employees): int
    {
        $count = 0;

        foreach ($employees as $employee) {
            if ($this->accountBlocker($employee, false) !== null) {
                continue;
            }

            $this->forceDelete($employee);
            $count++;
        }

        return $count;
    }

    /**
     * Refused when the save would close an account `UserService` will not
     * let go of — the actor's own, or the last active super admin's.
     *
     * @param  bool  $keepsAccountOpen  whether the account stays able to sign in after the write
     */
    public function accountBlocker(Employee $employee, bool $keepsAccountOpen): ?string
    {
        $user = $employee->user;

        if ($keepsAccountOpen || $user === null || ! $user->isActive() || $user->trashed()) {
            return null;
        }

        return $this->users->statusBlocker($user, RecordStatus::Inactive);
    }

    /**
     * Bring the linked account in line with the employee: copy their details,
     * then open or close it as asked.
     */
    private function reconcileAccount(Employee $employee, bool $open, ?string $password): void
    {
        $user = $employee->user;

        if ($user === null) {
            if ($open && $password !== null) {
                $this->openAccount($employee, $password);
            }

            return;
        }

        $this->users->update($user, $this->accountAttributes($employee));

        $canSignIn = $user->isActive() && ! $user->trashed();

        if ($open && ! $canSignIn && $password !== null) {
            $this->reopenAccount($employee, $user, $password);
        } elseif (! $open && $canSignIn) {
            $this->users->update($user, ['status' => RecordStatus::Inactive->value]);
        }
    }

    private function openAccount(Employee $employee, string $password): void
    {
        $user = $this->users->create($this->accountAttributes($employee), $password);

        $this->users->assignCompanies($user, [$employee->company_id]);

        $employee->forceFill(['user_id' => $user->id])->save();
        $employee->setRelation('user', $user);

        $this->pendingCredentials[] = ['user' => $user, 'password' => $password, 'reactivated' => false];
    }

    /**
     * The same account, signing in again under a new temporary password, and
     * granted the employee's company once more if it lost it.
     */
    private function reopenAccount(Employee $employee, User $user, string $password): void
    {
        if ($user->trashed()) {
            $this->users->restore($user);
        }

        $this->users->update($user, ['status' => RecordStatus::Active->value]);
        $this->users->resetPassword($user, $password);

        $companyIds = array_map(intval(...), $user->companies()->pluck('companies.id')->all());

        if (! in_array($employee->company_id, $companyIds, true)) {
            $companyIds[] = $employee->company_id;
            sort($companyIds);

            $this->users->assignCompanies($user, $companyIds);
        }

        $this->pendingCredentials[] = ['user' => $user, 'password' => $password, 'reactivated' => true];
    }

    private function closeAccount(Employee $employee): void
    {
        $user = $employee->user;

        if ($user !== null && $user->isActive() && ! $user->trashed()) {
            $this->users->update($user, ['status' => RecordStatus::Inactive->value]);
        }
    }

    /**
     * What the account copies from the employee on every save.
     *
     * @return array{name: string, username: string, email: string, phone: string|null, employee_code: string}
     */
    private function accountAttributes(Employee $employee): array
    {
        return [
            'name' => $employee->name,
            'username' => mb_strtolower($employee->employee_code),
            'email' => $employee->email,
            'phone' => $employee->official_mobile_no,
            'employee_code' => $employee->employee_code,
        ];
    }

    /**
     * Mail the credentials of every account this write opened — after the
     * transaction, so a rolled-back save never emails a password that does
     * not work.
     */
    private function sendCredentials(): ?string
    {
        $mailedTo = null;
        $actor = Auth::user();

        foreach ($this->pendingCredentials as ['user' => $user, 'password' => $password, 'reactivated' => $reactivated]) {
            $mailed = $reactivated && $actor instanceof User
                ? $this->credentials->passwordReset($user, $password, $actor)
                : $this->credentials->welcome($user, $password);

            if ($mailed) {
                $mailedTo = $user->email;
            }
        }

        $this->pendingCredentials = [];

        return $mailedTo;
    }
}
