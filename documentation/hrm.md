# HRM Module — Reference

> **📋 Mostly planned.** This is the agreed scope for the Human Resource Management module
> (owner, 2026-10-28). It describes *what* the module will do and *why*; where things will live and
> what they are called is [`ARCHITECTURE.md`](../ARCHITECTURE.md)'s job. **Built so far:
> Departments** ([§4.1](#41-departments--built)), **Designations** ([§4.2](#42-designations--built))
> and **Employees** ([§4.3](#43-employees--built)), 2026-10-29; the planned parts shrink as they ship.
>
> **🧪 POC overview:** `/hrm` (`hrm.dashboard`, permission `hrm.dashboard.view`) shows sample data
> under a Preview banner. Its page body and `components/hrm/sample-data.ts` are throwaway; the route,
> permission and role stay ([`ARCHITECTURE.md` §5](../ARCHITECTURE.md#5-module-registry)).

---

## 1. Overview

HRM holds **the people each company employs**: who they are, where they sit in the organisation,
when they work, their leave, and, from phase 2, what they are paid. In many small
organizations most employees — shift, field or floor staff — **never sign in**, so an employee
record never depends on a user account.

| | |
| --- | --- |
| Menu label | HRM |
| Namespace | `Hrm` |
| Route file | `routes/hrm.php` |
| Name / permission prefix | `hrm.` |
| URL prefix | `hrm/` |
| Pages | `pages/hrm/*` |

**Every record belongs to a company** ([ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company)).

### Employee and User

An **Employee** is an HRM record. A **User** is a sign-in account. An employee has an **optional
link to one User** (owner, 2026-10-28), which lets a member of staff log their own timesheet hours,
and later see their own leave and payslips.

**An employee may be made an application user from the HRM form** (owner, 2026-10-29 — this
reverses the earlier rule that creating an employee never creates an account). Ticking
*Application user* opens a sign-in account for them; most employees still never sign in, and a User
need not be an employee (an external auditor, for example). The details are in
[§4.3](#43-employees--built).

## 2. Scope

**In scope**

- Org structure: departments, designations, sections and production lines.
- Employees: personal details, job details, documents, status (active, on leave, separated).
- Shifts and rosters, and attendance, entered by hand or **imported from attendance devices**.
- Leave: types, entitlements, applications with approval, balances.
- Payroll under the **Bangladesh Labour Act**: wage grades, overtime, attendance bonus, deductions,
  and the salary sheet (phase 2).
- Recruitment, appraisal, separation and final settlement (phase 3).

**Out of scope**

- Sign-in accounts, roles and permissions belong to Admin.
- Ledger postings belong to Accounting. HRM produces the payroll *draft voucher* and Accounting
  posts it.
- Project hours belong to Projects. A timesheet is not attendance.

## 3. Phases

Build order across the ERP is **Company (Admin) → Projects → Accounting → HRM** (owner, 2026-10-28).
Projects needs employees, so a small **HRM-lite** slice ships first:

| Phase | Delivers |
| --- | --- |
| **HRM-lite** (ships with Projects) | Employees (employee ID, name, email, company, department, designation, mobiles, joining date, status, optional sign-in account, **monthly salary**), Departments, Designations — built. |
| **1 — workforce** | Sections and lines, employee documents, shifts and rosters, attendance (manual + device import), leave types, entitlements and applications. |
| **2 — payroll** | Wage grades, salary structures, overtime, attendance bonus, deductions, the salary sheet and payslips. Payroll posts a **draft voucher** to Accounting and **sets the employee cost rate** that Projects uses. |
| **3 — lifecycle** | Recruitment, appraisal, separation and final settlement. |

## 4. Main entities

- **Department** (built, [§4.1](#41-departments--built)): a company-level lookup (HRM-lite).
- **Designation** (built, [§4.2](#42-designations--built)): a **group-wide** lookup — one list of job
  titles every company shares (HRM-lite; owner, 2026-10-29).
- **Section / Line**: the working units under a department, such as teams or lines (phase 1).
- **Employee**: employee code, name, company, department, designation, joining date, status,
  optional User, and **monthly salary** (built, [§4.3](#43-employees--built)). Phase 1 adds personal and
  job details and documents.
- **Shift** and **roster**: working hours, and who works which shift.
- **Attendance record**: an employee, a date, in and out times, and a source (manual or device).
- **Leave type**, **leave entitlement**, **leave application**.
- **Payroll run** (phase 2): a company, a month, a status, payslip lines per employee, and the link
  to its Accounting voucher.

### The cost rate

The cost rate is what an hour of this employee costs the company. Projects uses it to price
timesheet hours for budget against actual ([`projects.md` §4](projects.md#billing-rate-and-cost-rate)).
Until payroll exists it is **entered by hand**; from phase 2 payroll calculates it. It is
salary-derived, so it is not visible to everyone ([§8](#8-open-decisions)).

### 4.1 Departments — built

HRM → Departments (`hrm.departments.index`, `pages/hrm/departments/index.tsx`), the first
company-scoped master table. `Models/Hrm/Department`, `Hrm\DepartmentController`,
`Hrm\DepartmentService`, tests in `tests/Feature/Hrm/DepartmentTest.php` and `DepartmentImportTest.php`.

| Column | Rule |
| --- | --- |
| `company_id` | The **selected** company, stamped by the server — never a form field. `restrictOnDelete`: the company's service refuses to delete a company with departments. |
| `code` | Required, up to 20, letters and digits joined by single `-`/`_`; stored in capitals. |
| `name` | Required, up to 100; trimmed, inner spaces collapsed. |
| `status` | `A` / `I`. Inactive keeps the department on record but out of new entries. |
| actor stamps, timestamps, `deleted_at` | Restorable: Active / Deleted tabs. |

- **Duplicates** (owner, 2026-10-29): `code` and `name` are each unique **within the company**,
  ignoring case and spacing, **deleted departments included** — the refusal says to restore the
  deleted one. The same code or name in another company is allowed. Unique indexes
  `(company_id, code)` and `(company_id, name)` back the rule.
- **Bulk delete** on both tabs (ARCHITECTURE §8.6): the header box ticks the page; Active moves the
  ticked departments to Deleted, Deleted removes them for good. Only the selected company's ids are
  found, so another company's department is never touched.
- **Deletion guard**: `DepartmentService::deletionBlocker()` refuses to delete (or delete for good) a
  department any employee is in, deleted employees included — "Used by 3 employees. Move them to
  another department, or deactivate this one instead." In bulk, the guarded rows are named in a
  warning toast and the rest go.
- **Import** (`hrm.departments.import`; `Hrm\DepartmentImportBlueprint`, a row writer): columns Code,
  Name, optional Status; every row goes to the uploader's selected company. **A row matches an existing
  department when its code or its name matches** (owner, 2026-10-29): Skip leaves it, Update rewrites
  its code, name and status. Rejected, with the reason: a row whose code and name match two different
  departments, a row matching a deleted department, a name or code repeated in the file, an unknown
  status. A file uploaded for one company cannot be reviewed or confirmed while the uploader works in
  another.
- Roles by rule: `hr-officer` receives every `hrm.departments.*`, `viewer` the `.view`.

### 4.2 Designations — built

HRM → Designations (`hrm.designations.index`, `pages/hrm/designations/index.tsx`), the first
**group-wide** master table: **no `company_id`** — the same job titles for every company, and the page
works with no company selected (owner, 2026-10-29; ARCHITECTURE §9.10). `Models/Hrm/Designation`,
`Hrm\DesignationController`, `Hrm\DesignationService`, tests in `tests/Feature/Hrm/DesignationTest.php`
and `DesignationImportTest.php`.

| Column | Rule |
| --- | --- |
| `code` | Required, up to 20, letters and digits joined by single `-`/`_`; stored in capitals. |
| `name` | Required, up to 100; trimmed, inner spaces collapsed. |
| `status` | `A` / `I`. Inactive keeps the designation on record but out of new entries. |
| actor stamps, timestamps, `deleted_at` | Restorable: Active / Deleted tabs. |

- **Duplicates**: `code` and `name` are each unique **across the whole table**, ignoring case and
  spacing, **deleted designations included** — the refusal says to restore the deleted one. Unique
  indexes on `code` and on `name` back the rule.
- **Bulk delete** on both tabs, as departments.
- **Deletion guard**: `DesignationService::deletionBlocker()` refuses while any employee, in any
  company, holds the designation — the same sentence and bulk behaviour as departments.
- **Import** (`hrm.designations.import`; `Hrm\DesignationImportBlueprint`, a row writer): columns
  Code, Name, optional Status; the same matching and rejection rules as departments, with no company
  — the staged file stores none, and switching company does not affect it.
- **Roles** (owner, 2026-10-29): `hr-officer` may view, create, update and import, but **not** delete,
  restore or force-delete — one officer's delete would remove the title from every company.
  `RolePermissionSeeder::ROLE_EXCLUSIONS` takes those three back after the `hrm.` rule matches; they
  stay with super-admin unless granted to a role by hand. `viewer` gets the `.view`.

### 4.3 Employees — built

HRM → Employees (`hrm.employees.index`, `pages/hrm/employees/index.tsx`), company-scoped and
restorable like departments. `Models/Hrm/Employee`, `Hrm\EmployeeController`, `Hrm\EmployeeService`,
tests in `tests/Feature/Hrm/EmployeeTest.php` and `EmployeeImportTest.php` (owner, 2026-10-29).

| Column | Rule |
| --- | --- |
| `company_id` | The **selected** company, stamped by the server — never a form field. |
| `employee_code` | Labelled **Employee ID**. Required, up to 30, letters and digits joined by single `-`/`_`; stored in capitals. **Unique across every company**, deleted employees included. |
| `name` | Required, up to 255. |
| `email` | Required, lowercased, **unique across every company**, deleted employees included. |
| `department_id` | Required: an active department of the selected company. `restrictOnDelete`. |
| `designation_id` | Required: an active designation. `restrictOnDelete`. |
| `official_mobile_no`, `personal_mobile_no` | Optional **Bangladeshi** mobiles (`01[3-9]` + 8 digits); spaces, hyphens and a `+880`/`880` prefix are folded, so `+880 1712-345678` is stored `01712345678`. |
| `joining_date` | Optional date. |
| `monthly_salary` | Optional, BDT, ≥ 0, two decimals. Seen and saved only with `hrm.employees.view-compensation`; for anyone else the column is not even sent and a posted value is ignored. Projects will derive its hourly cost rate from it. |
| `user_id` | The optional sign-in account; unique; set only by the service. |
| `status` | `A` / `I`. |
| actor stamps, timestamps, `deleted_at` | Restorable: Active / Deleted tabs. |

An employee keeps a department or designation that was deactivated after they joined it; only a new
choice must be active. Ids are filters (`Equals`) on the list, not sort keys.

**The sign-in account** (`hrm.employees.create-account`; without it the form has no box and the
account is left as it is):

- **Opening** — ticking *Application user* for an active employee asks for a temporary password
  (the password policy applies). The account's **username is the employee ID in lowercase** (so the ID
  needs at least 3 characters), its email and phone are the employee's, it gets **no role** (an
  administrator assigns one in Admin → Users) and is granted the employee's company. The password is
  emailed with the welcome mail and **must be changed at first sign-in**. A username or email already
  used by another account is a field error; existing accounts are not linked in this slice.
- **Keeping in step** — every save copies name, email, official mobile (→ phone) and employee ID (→
  employee code and username) onto the account.
- **Closing** — unticking, deactivating the employee, or deleting them sets the account Inactive; it
  is never deleted. Re-ticking re-opens the same account with a new temporary password (mailed as a
  password reset). Restoring an employee does not re-open it. Deleting an employee for good leaves
  the account in Admin, inactive and unlinked.
- **Guard** — closing the account of the actor themselves, or of the last active super admin, is
  refused with `UserService`'s own sentence (`EmployeeService::accountBlocker()`).

- **No deletion guard of its own yet**: nothing references an employee. Projects members and
  timesheets will bring the first clause.
- **Import** (`hrm.employees.import`; `Hrm\EmployeeImportBlueprint`, a row writer): columns Employee
  ID, Name, Email, Department code, Designation code, optional mobiles, joining date (date cell or
  text), status, and monthly salary for an uploader with `view-compensation`. A row matches on its
  employee ID; another company's ID, a deleted employee, an unknown or inactive department or
  designation, a taken email, a bad mobile or date are rejected with the reason. **An import never
  opens an account**; an updated employee's existing account follows their row.
- Roles by rule: `hr-officer` receives every `hrm.employees.*`, including `create-account` and
  `view-compensation`; `viewer` gets only `hrm.employees.view` (the suffix rule matches `.view`
  exactly).

## 5. Workflows and approvals

Approvals follow the ERP-wide pattern ([ARCHITECTURE §9.11](../ARCHITECTURE.md#911-approvals-planned)):
a status of draft → submitted → approved or rejected, an `.approve` permission, and a service blocker
that stops anyone approving their own record.

- **Leave.** An application is submitted (by the employee, or by HR on their behalf), approved or
  rejected, and the balance updates when it is approved.
- **Attendance.** A device import is reviewed for gaps, and a correction to a record is itself
  approved.
- **Payroll** (phase 2). A run is calculated, reviewed and approved. Approval creates a **draft
  voucher** in Accounting ([ARCHITECTURE §9.12](../ARCHITECTURE.md#912-posting-to-the-ledger-planned)),
  and the run locks once Accounting posts it.

## 6. Draft permission catalogue

*Draft — not seeded*, except `hrm.dashboard.view`,
`hrm.departments.{view,create,update,delete,restore,force-delete,import}`,
`hrm.designations.{view,create,update,delete,restore,force-delete,import}` and
`hrm.employees.{view,create,update,delete,restore,force-delete,import,create-account,view-compensation}`, which are. Names follow `{module}.{resource}.{action}` ([ARCHITECTURE
§6.2](../ARCHITECTURE.md#62-routes-and-permissions)).

| Permission | Allows |
| --- | --- |
| `hrm.departments.{view,create,update,delete,restore,force-delete,import}` | Departments (HRM-lite) — **seeded** |
| `hrm.designations.{view,create,update,delete,restore,force-delete,import}` | Designations (HRM-lite, group-wide) — **seeded** |
| `hrm.employees.{view,create,update,delete,restore,force-delete,import,create-account,view-compensation}` | Employees (HRM-lite) — **seeded** |
| `hrm.sections.{view,create,update,delete}` | Sections and lines |
| `hrm.shifts.{view,manage}` | Shifts and rosters |
| `hrm.attendance.{view,manage,import}` | Attendance records and device import |
| `hrm.leave.{view,manage,approve}` | Leave applications |
| `hrm.payroll.{view,run,approve}` | Payroll runs (phase 2) |

## 7. Links to other modules

| Module | HRM uses | HRM provides |
| --- | --- | --- |
| Admin | Companies, Users (the optional link) | — |
| Projects | — | Employees, departments, designations, the cost rate |
| Accounting | Ledger accounts for payroll | Payroll draft vouchers (phase 2) |

## 8. Open decisions

- How Projects turns the monthly salary into an hourly cost rate (working hours per month).
- Which attendance devices are used, and how are they imported (file, API, database pull)?
- Does a linked User see a self-service area (own attendance, leave, payslips)? If so, which module
  owns those pages?
- Bangladesh Labour Act parameters: the overtime multiplier, attendance-bonus rules, and festival
  bonus. Are they configurable per company?
