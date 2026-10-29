import type { ListFilters } from '@/types/ui';

/** One row of the departments list, as `DepartmentController::index()` projects it. */
export type DepartmentListItem = {
    id: number;
    code: string;
    name: string;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type DepartmentFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};

/** One row of the designations list, as `DesignationController::index()` projects it. */
export type DesignationListItem = {
    id: number;
    code: string;
    name: string;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type DesignationFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};

/** One row of the employees list, as `EmployeeController::index()` projects it. */
export type EmployeeListItem = {
    id: number;
    employee_code: string;
    name: string;
    email: string;
    department_id: number;
    department_name: string;
    designation_id: number;
    designation_name: string;
    official_mobile_no: string | null;
    personal_mobile_no: string | null;
    joining_date: string | null;
    /** Present only for a viewer holding `hrm.employees.view-compensation`. */
    monthly_salary?: string | null;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    /** A linked sign-in account that can sign in now. */
    is_application_user: boolean;
    /** The linked account's username, active or not; null when there is none. */
    username: string | null;
    created_at: string | null;
    deleted_at: string | null;
};

export type EmployeeFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};

/**
 * A department or designation as the employee form offers it — inactive ones
 * included so an employee already in one still shows it.
 */
export type EmployeeLookupOption = {
    value: number;
    label: string;
    /** The code, shown after the name. */
    hint: string;
    /** `App\Enums\RecordStatus`. */
    status: string;
};
