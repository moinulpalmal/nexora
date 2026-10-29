import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCallback, useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import FormDialogFooter from '@/components/shared/form-dialog-footer';
import PasswordPolicyChecklist from '@/components/shared/password-policy-checklist';
import { Checkbox } from '@/components/ui/checkbox';
import Combobox from '@/components/ui/combobox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFormDialog } from '@/hooks/use-form-dialog';
import { RECORD_STATUS } from '@/lib/enums';
import type {
    EmployeeListItem,
    EmployeeLookupOption,
    StatusOption,
} from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Only the active options, plus the one the employee already holds — so an
 * employee in a department that was later deactivated still shows it, and
 * nobody newly chooses an inactive one (the server refuses that too).
 */
function choosable(options: EmployeeLookupOption[], current?: number) {
    return options
        .filter(
            (option) =>
                option.status === RECORD_STATUS.Active ||
                option.value === current,
        )
        .map(({ value, label, hint }) => ({ value, label, hint }));
}

/**
 * Create or edit an employee — the form-modal standard in ARCHITECTURE.md
 * §8.10, mirroring `hrm/department-form-dialog.tsx`.
 *
 * **No company field** (§9.10): the employee belongs to the company selected
 * in the header. **The account** (owner, 2026-10-29): with
 * `hrm.employees.create-account` the form shows the "Application user" box;
 * ticking it for an active employee without a working account asks for a
 * temporary password, which the employee must change at first sign-in. The
 * username is the employee ID in lowercase. Without the permission the box is
 * absent and the server leaves the account as it is.
 */
export default function EmployeeFormDialog({
    submit,
    statuses,
    departments,
    designations,
    canSeeCompensation,
    canManageAccount,
    employee,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    departments: EmployeeLookupOption[];
    designations: EmployeeLookupOption[];
    canSeeCompensation: boolean;
    /** `hrm.employees.create-account`. */
    canManageAccount: boolean;
    employee?: EmployeeListItem;
    title: string;
    description: string;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const close = useCallback(() => setOpen(false), []);
    const { formKey, formProps, setIntent } = useFormDialog(close);

    /*
     * The fields the dialog reacts to. "Save & add another" remounts the form
     * under a new `formKey`; the state follows it back to the start, so a
     * typed password never carries into the next employee.
     */
    const initial = () => ({
        formKey,
        status: employee?.status ?? RECORD_STATUS.Active,
        applicationUser: employee?.is_application_user ?? false,
        password: '',
    });
    const [fields, setFields] = useState(initial);

    if (fields.formKey !== formKey) {
        setFields(initial());
    }

    const { status, applicationUser, password } = fields;
    const setStatus = (next: string) =>
        setFields((current) => ({ ...current, status: next }));
    const setApplicationUser = (next: boolean) =>
        setFields((current) => ({ ...current, applicationUser: next }));
    const setPassword = (next: string) =>
        setFields((current) => ({ ...current, password: next }));

    const needsPassword =
        canManageAccount &&
        applicationUser &&
        status === RECORD_STATUS.Active &&
        !employee?.is_application_user;

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                // Each opening starts from the saved employee, not from what
                // was typed before a cancel.
                if (next) {
                    setFields(initial());
                }

                setOpen(next);
            }}
        >
            <DialogTrigger asChild>{children}</DialogTrigger>

            <DialogContent className="max-w-4xl">
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    key={formKey}
                    {...submit}
                    {...formProps}
                    options={{ preserveScroll: true }}
                    className="mt-4 space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="field-grid">
                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="employee_code">
                                        Employee ID
                                    </Label>
                                    <Input
                                        id="employee_code"
                                        name="employee_code"
                                        defaultValue={employee?.employee_code}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={30}
                                        placeholder="E1001"
                                        className="font-mono uppercase"
                                        aria-invalid={Boolean(
                                            errors.employee_code,
                                        )}
                                    />
                                    <p className="text-xs text-base-content/60">
                                        Unique across every company. Saved in
                                        capitals; an application user signs in
                                        with it in lowercase.
                                    </p>
                                    <InputError
                                        message={errors.employee_code}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={employee?.name}
                                        required
                                        autoComplete="off"
                                        maxLength={255}
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={employee?.email}
                                        required
                                        autoComplete="off"
                                        maxLength={255}
                                        aria-invalid={Boolean(errors.email)}
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="joining_date">
                                        Joining date
                                    </Label>
                                    <Input
                                        id="joining_date"
                                        name="joining_date"
                                        type="date"
                                        defaultValue={
                                            employee?.joining_date ?? ''
                                        }
                                        aria-invalid={Boolean(
                                            errors.joining_date,
                                        )}
                                    />
                                    <InputError message={errors.joining_date} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="department_id">
                                        Department
                                    </Label>
                                    <Combobox
                                        id="department_id"
                                        name="department_id"
                                        defaultValue={employee?.department_id}
                                        options={choosable(
                                            departments,
                                            employee?.department_id,
                                        )}
                                        required
                                        aria-invalid={Boolean(
                                            errors.department_id,
                                        )}
                                        data-test="employee-department"
                                    />
                                    <InputError
                                        message={errors.department_id}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="designation_id">
                                        Designation
                                    </Label>
                                    <Combobox
                                        id="designation_id"
                                        name="designation_id"
                                        defaultValue={employee?.designation_id}
                                        options={choosable(
                                            designations,
                                            employee?.designation_id,
                                        )}
                                        required
                                        aria-invalid={Boolean(
                                            errors.designation_id,
                                        )}
                                        data-test="employee-designation"
                                    />
                                    <InputError
                                        message={errors.designation_id}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="official_mobile_no">
                                        Official mobile
                                    </Label>
                                    <Input
                                        id="official_mobile_no"
                                        name="official_mobile_no"
                                        type="tel"
                                        defaultValue={
                                            employee?.official_mobile_no ?? ''
                                        }
                                        autoComplete="off"
                                        maxLength={30}
                                        placeholder="01712345678"
                                        aria-invalid={Boolean(
                                            errors.official_mobile_no,
                                        )}
                                    />
                                    <InputError
                                        message={errors.official_mobile_no}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="personal_mobile_no">
                                        Personal mobile
                                    </Label>
                                    <Input
                                        id="personal_mobile_no"
                                        name="personal_mobile_no"
                                        type="tel"
                                        defaultValue={
                                            employee?.personal_mobile_no ?? ''
                                        }
                                        autoComplete="off"
                                        maxLength={30}
                                        placeholder="01712345678"
                                        aria-invalid={Boolean(
                                            errors.personal_mobile_no,
                                        )}
                                    />
                                    <InputError
                                        message={errors.personal_mobile_no}
                                    />
                                </div>

                                {canSeeCompensation && (
                                    <div className="grid content-start gap-1.5">
                                        <Label htmlFor="monthly_salary">
                                            Monthly salary (BDT)
                                        </Label>
                                        <Input
                                            id="monthly_salary"
                                            name="monthly_salary"
                                            type="number"
                                            min={0}
                                            step="0.01"
                                            defaultValue={
                                                employee?.monthly_salary ?? ''
                                            }
                                            aria-invalid={Boolean(
                                                errors.monthly_salary,
                                            )}
                                        />
                                        <InputError
                                            message={errors.monthly_salary}
                                        />
                                    </div>
                                )}

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        value={status}
                                        onChange={(value) =>
                                            setStatus(
                                                String(
                                                    value ??
                                                        RECORD_STATUS.Active,
                                                ),
                                            )
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="employee-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive employee stays on record;
                                        their sign-in account, if any, stops.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            {canManageAccount && (
                                <fieldset className="grid gap-4 rounded-box border border-base-300/70 p-4">
                                    <legend className="px-1 text-sm font-medium">
                                        Sign-in account
                                    </legend>

                                    <label className="flex cursor-pointer items-start gap-3">
                                        <input
                                            type="hidden"
                                            name="is_application_user"
                                            value="0"
                                        />
                                        <Checkbox
                                            name="is_application_user"
                                            value="1"
                                            checked={applicationUser}
                                            onChange={(event) =>
                                                setApplicationUser(
                                                    event.target.checked,
                                                )
                                            }
                                            className="mt-0.5"
                                            data-test="employee-application-user"
                                        />
                                        <span className="text-sm">
                                            Application user
                                            <span className="block text-xs text-base-content/60">
                                                {employee?.is_application_user
                                                    ? `Signs in as “${employee.username}”. Unticking stops the account; it is kept, inactive.`
                                                    : employee?.username
                                                      ? `The account “${employee.username}” is inactive. Ticking re-opens it with a new temporary password.`
                                                      : 'Opens a sign-in account with the employee ID (lowercase) as username and no role — an administrator assigns roles in Admin → Users.'}
                                            </span>
                                        </span>
                                    </label>
                                    <InputError
                                        message={errors.is_application_user}
                                    />

                                    {needsPassword && (
                                        <div className="field-grid">
                                            <div className="grid content-start gap-1.5">
                                                <Label htmlFor="password">
                                                    Temporary password
                                                </Label>
                                                <PasswordInput
                                                    id="password"
                                                    name="password"
                                                    value={password}
                                                    onChange={(event) =>
                                                        setPassword(
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                    autoComplete="new-password"
                                                    aria-invalid={Boolean(
                                                        errors.password,
                                                    )}
                                                    aria-describedby="employee-password-policy"
                                                />
                                                <PasswordPolicyChecklist
                                                    id="employee-password-policy"
                                                    password={password}
                                                />
                                                <InputError
                                                    message={errors.password}
                                                />
                                            </div>

                                            <div className="grid content-start gap-1.5">
                                                <Label htmlFor="password_confirmation">
                                                    Confirm password
                                                </Label>
                                                <PasswordInput
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    required
                                                    autoComplete="new-password"
                                                    aria-invalid={Boolean(
                                                        errors.password_confirmation,
                                                    )}
                                                />
                                                <p className="text-xs text-base-content/60">
                                                    Emailed to the employee, who
                                                    must change it at first
                                                    sign-in.
                                                </p>
                                                <InputError
                                                    message={
                                                        errors.password_confirmation
                                                    }
                                                />
                                            </div>
                                        </div>
                                    )}
                                </fieldset>
                            )}

                            <FormDialogFooter
                                processing={processing}
                                addAnother={employee === undefined}
                                onIntent={setIntent}
                                saveTestId="save-employee"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
