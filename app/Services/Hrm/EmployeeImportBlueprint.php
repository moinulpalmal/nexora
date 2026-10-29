<?php

namespace App\Services\Hrm;

use App\Concerns\MasterDataValidationRules;
use App\Enums\Imports\DuplicateAction;
use App\Enums\RecordStatus;
use App\Http\Requests\Hrm\EmployeeRequest;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\Hrm\Employee;
use App\Models\SheetImport;
use App\Models\User;
use App\Services\Imports\ImportBlueprint;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportOutcome;
use App\Services\Imports\ImportRowWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Employees from a spreadsheet (ARCHITECTURE.md §9.13) — a row writer, as
 * {@see DepartmentImportBlueprint}, so every row goes through
 * {@see EmployeeService} and is audited like a form save.
 *
 * **It never opens a sign-in account** (owner, 2026-10-29): an account needs
 * a temporary password typed by a person, so a sheet carries none. An
 * existing employee updated from a sheet keeps their account as it is — its
 * details follow the row, as on a form save.
 *
 * A row is matched on its employee ID, which is unique across the group:
 *
 * - new ID → created in the staged import's company (§9.10);
 * - the ID of this company's employee → skipped or updated as chosen;
 * - the ID of another company's employee, or of a deleted one → rejected;
 * - the department (by code, in this company) or designation (by code) must
 *   exist and be active; the email must be free among employees and, for an
 *   employee with an account, among users.
 *
 * **Salary** is a field only for an uploader holding
 * `hrm.employees.view-compensation`, as on the form.
 */
class EmployeeImportBlueprint implements ImportBlueprint, ImportRowWriter
{
    // For its normalisers — the import fields' transforms are the form's.
    use MasterDataValidationRules;

    private const string MOBILE_PATTERN = '/^01[3-9][0-9]{8}$/';

    public function __construct(private readonly EmployeeService $employees) {}

    public function key(): string
    {
        return 'hrm.employees';
    }

    public function label(): string
    {
        return 'Employees';
    }

    public function modelClass(): string
    {
        return Employee::class;
    }

    public function isCompanyScoped(): bool
    {
        return true;
    }

    public function scopeField(): ?string
    {
        return null;
    }

    public function fields(): array
    {
        $fields = [
            new ImportField('employee_code', 'Employee ID', required: true, maxLength: 30, transform: self::normaliseCode(...)),
            new ImportField('name', 'Name', required: true, maxLength: 255, transform: self::normaliseName(...)),
            new ImportField('email', 'Email', required: true, maxLength: 255, transform: self::normaliseEmail(...)),
            new ImportField('department_code', 'Department code', required: true, maxLength: 20, transform: self::normaliseCode(...)),
            new ImportField('designation_code', 'Designation code', required: true, maxLength: 20, transform: self::normaliseCode(...)),
            new ImportField('official_mobile_no', 'Official mobile', maxLength: 30, transform: self::normaliseSheetMobile(...)),
            new ImportField('personal_mobile_no', 'Personal mobile', maxLength: 30, transform: self::normaliseSheetMobile(...)),
            new ImportField('joining_date', 'Joining date', transform: self::normaliseDate(...)),
            new ImportField('status', 'Status', transform: DepartmentImportBlueprint::normaliseStatus(...)),
        ];

        if (Auth::user()?->can('hrm.employees.view-compensation')) {
            $fields[] = new ImportField('monthly_salary', 'Monthly salary', transform: self::normaliseAmount(...));
        }

        return $fields;
    }

    public function uniqueField(): string
    {
        return 'employee_code';
    }

    public function normaliseRow(array $values): array
    {
        return $values;
    }

    /**
     * Empty although `email` is unique too: this blueprint writes its own rows
     * and checks emails in {@see self::writeRows()}.
     */
    public function guardedFields(): array
    {
        return [];
    }

    public function defaultAttributes(): array
    {
        return ['status' => RecordStatus::Active->value];
    }

    public function confirmRules(): array
    {
        return [];
    }

    public function writeRows(array $rows, DuplicateAction $onDuplicate, array $options, SheetImport $import, bool $dryRun): ImportOutcome
    {
        $companyId = (int) $import->company_id;
        $withSalary = (bool) Auth::user()?->can('hrm.employees.view-compensation');

        $departments = Department::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->get(['id', 'code', 'status'])
            ->keyBy(fn (Department $department): string => mb_strtoupper($department->code));

        $designations = Designation::query()
            ->get(['id', 'code', 'status'])
            ->keyBy(fn (Designation $designation): string => mb_strtoupper($designation->code));

        /*
         * Every employee of the group, deleted ones included — codes and
         * emails are unique across it — kept current as rows are written so a
         * later row is judged against the earlier rows' effects.
         */
        $byCode = [];
        $byEmail = [];

        foreach (Employee::withoutGlobalScopes()->get(['id', 'company_id', 'employee_code', 'email', 'user_id', 'deleted_at']) as $employee) {
            $byCode[mb_strtoupper($employee->employee_code)] = [
                'id' => $employee->id,
                'company_id' => $employee->company_id,
                'user_id' => $employee->user_id,
                'deleted' => $employee->deleted_at !== null,
            ];
            $byEmail[mb_strtolower($employee->email)] = $employee->id;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $rejected = [];
        $notes = [];
        $emailsInFile = [];
        $provisionalId = -1;

        foreach ($rows as $row) {
            $values = $row['values'];
            $code = (string) $values['employee_code'];
            $email = (string) $values['email'];
            $status = $values['status'] ?? null;

            $reject = function (string $message) use (&$rejected, $row): void {
                $rejected[] = ['sheet' => $row['sheet'], 'line' => $row['line'], 'message' => $message];
            };

            $problem = $this->rowProblem($values, $departments, $designations, $withSalary);

            if ($problem !== null) {
                $reject($problem);

                continue;
            }

            if (isset($emailsInFile[$email])) {
                $first = $emailsInFile[$email];
                $reject(__('The email ":email" duplicates ":sheet" row :line.', ['email' => $email, 'sheet' => $first['sheet'], 'line' => $first['line']]));

                continue;
            }

            $emailsInFile[$email] = ['sheet' => $row['sheet'], 'line' => $row['line']];

            $match = $byCode[$code] ?? null;

            if ($match !== null && $match['company_id'] !== $companyId) {
                $reject(__('The employee ID ":code" belongs to an employee of another company.', ['code' => $code]));

                continue;
            }

            if ($match !== null && $match['deleted']) {
                $reject(__('Matches the deleted employee ":code". Restore them from the Deleted tab first.', ['code' => $code]));

                continue;
            }

            $emailOwner = $byEmail[$email] ?? null;

            if ($emailOwner !== null && $emailOwner !== ($match['id'] ?? null)) {
                $reject(__('Another employee already uses ":email".', ['email' => $email]));

                continue;
            }

            if ($match !== null && $onDuplicate === DuplicateAction::Skip) {
                $skipped++;

                continue;
            }

            if ($match !== null && $match['user_id'] !== null && User::withTrashed()->whereKeyNot($match['user_id'])->where(fn ($query) => $query->where('email', $email)->orWhere('username', mb_strtolower($code)))->exists()) {
                $reject(__('Another sign-in account already uses ":email". This employee\'s account would take it.', ['email' => $email]));

                continue;
            }

            $attributes = [
                'employee_code' => $code,
                'name' => (string) $values['name'],
                'email' => $email,
                'department_id' => $departments[(string) $values['department_code']]->id,
                'designation_id' => $designations[(string) $values['designation_code']]->id,
                'official_mobile_no' => $values['official_mobile_no'] ?? null,
                'personal_mobile_no' => $values['personal_mobile_no'] ?? null,
                'joining_date' => $values['joining_date'] ?? null,
            ];

            if ($withSalary && array_key_exists('monthly_salary', $values)) {
                $attributes['monthly_salary'] = $values['monthly_salary'];
            }

            if ($status !== null) {
                $attributes['status'] = (string) $status;
            }

            try {
                if ($match === null) {
                    if (! $dryRun) {
                        // A sheet with no Status column starts every row active.
                        $this->employees->create($companyId, $attributes + ['status' => RecordStatus::Active->value]);
                    }

                    $id = $dryRun ? $provisionalId-- : (int) Employee::withoutGlobalScopes()->where('employee_code', $code)->value('id');
                    $created++;
                    $notes[] = __('Will create :code — :name.', ['code' => $code, 'name' => $attributes['name']]);
                } else {
                    $employee = Employee::withoutGlobalScopes()->with('user')->findOrFail($match['id']);
                    $keepsAccountOpen = ($attributes['status'] ?? $employee->status->value) === RecordStatus::Active->value && $employee->isApplicationUser();
                    $blocker = $this->employees->accountBlocker($employee, $keepsAccountOpen);

                    if ($blocker !== null) {
                        $reject($blocker);

                        continue;
                    }

                    if (! $dryRun) {
                        $this->employees->update($employee, $attributes);
                    }

                    $id = $match['id'];
                    $updated++;
                }
            } catch (UniqueConstraintViolationException) {
                // Someone saved a clashing employee or account between the read
                // above and this write; the row is reported, the rest still land.
                $reject(__('Another employee or account took this ID or email while the import ran. Upload the row again.'));

                continue;
            }

            $previousEmail = $match === null ? false : array_search($match['id'], $byEmail, true);

            if ($previousEmail !== false) {
                unset($byEmail[$previousEmail]);
            }

            $byCode[$code] = ['id' => $id, 'company_id' => $companyId, 'user_id' => $match['user_id'] ?? null, 'deleted' => false];
            $byEmail[$email] = $id;
        }

        // Notes are a preview; after a real write the counts say it all.
        return new ImportOutcome($created, $updated, $skipped, $rejected, $dryRun ? $notes : []);
    }

    /**
     * What is wrong with a row on its own, before it is compared with the
     * employees already on record — or null.
     *
     * @param  array<string, mixed>  $values
     * @param  Collection<string, Department>  $departments
     * @param  Collection<string, Designation>  $designations
     */
    private function rowProblem(array $values, Collection $departments, Collection $designations, bool $withSalary): ?string
    {
        $code = (string) $values['employee_code'];
        $status = $values['status'] ?? null;

        if (preg_match('/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/', $code) !== 1) {
            return __('The employee ID ":code" may use only letters and digits, joined by single hyphens or underscores.', ['code' => $code]);
        }

        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            return __('":email" is not an email address.', ['email' => $values['email']]);
        }

        $department = $departments[(string) $values['department_code']] ?? null;

        if ($department === null || ! $department->isActive()) {
            return __('No active department of this company has the code ":code".', ['code' => $values['department_code']]);
        }

        $designation = $designations[(string) $values['designation_code']] ?? null;

        if ($designation === null || ! $designation->isActive()) {
            return __('No active designation has the code ":code".', ['code' => $values['designation_code']]);
        }

        foreach (['official_mobile_no' => 'official', 'personal_mobile_no' => 'personal'] as $field => $label) {
            $mobile = $values[$field] ?? null;

            if ($mobile !== null && preg_match(self::MOBILE_PATTERN, (string) $mobile) !== 1) {
                return __('The :which mobile ":number" is not a Bangladeshi mobile number.', ['which' => $label, 'number' => $mobile]);
            }
        }

        if (($values['joining_date'] ?? null) === false) {
            return __('The joining date is not a date.');
        }

        if ($withSalary && array_key_exists('monthly_salary', $values) && $values['monthly_salary'] === false) {
            return __('The monthly salary must be a number of zero or more.');
        }

        if ($status !== null && RecordStatus::tryFrom((string) $status) === null) {
            return __('Status must be Active or Inactive.');
        }

        return null;
    }

    public static function normaliseEmail(mixed $value): mixed
    {
        return is_string($value) ? mb_strtolower(trim($value)) : $value;
    }

    /**
     * The form's normaliser, after putting back the leading `0` a spreadsheet
     * drops when a mobile number was typed into a number cell — which
     * `SheetReader` hands over as ten digits of text.
     */
    public static function normaliseSheetMobile(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^1[3-9][0-9]{8}$/', trim($value)) === 1) {
            $value = '0'.trim($value);
        }

        return EmployeeRequest::normaliseMobile($value);
    }

    /**
     * A date cell or a date written as text, to `Y-m-d`; blank to null;
     * anything unreadable to `false`, which {@see self::rowProblem()} reports.
     *
     * `SheetReader` hands every cell over as text, so a real Excel date cell
     * arrives as its serial number in digits (`"46054"` is 2026-02-01) — read
     * as a serial, never as text, which `Carbon::parse()` cannot make a date of.
     */
    public static function normaliseDate(mixed $value): string|false|null
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }

            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * A number of zero or more, to two decimals; blank to null; anything else
     * to `false`.
     */
    public static function normaliseAmount(mixed $value): string|false|null
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        $number = is_string($value) ? str_replace(',', '', trim($value)) : $value;

        if (! is_numeric($number) || (float) $number < 0) {
            return false;
        }

        return number_format((float) $number, 2, '.', '');
    }
}
