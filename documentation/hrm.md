# HRM Module — Reference

> **📋 Planned — nothing built.** This is the agreed scope for the Human Resource Management module
> (owner, 2026-10-28). It describes *what* the module will do and *why*; where things will live and
> what they are called is [`ARCHITECTURE.md`](../ARCHITECTURE.md)'s job. When the first slice is
> built, this file becomes its reference and the planned parts shrink as they ship.
>
> **🧪 POC overview:** `/hrm` (`hrm.dashboard`, permission `hrm.dashboard.view`) shows sample data
> under a Preview banner. Its page body and `components/hrm/sample-data.ts` are throwaway; the route,
> permission and role stay ([`ARCHITECTURE.md` §5](../ARCHITECTURE.md#5-module-registry)).

---

## 1. Overview

HRM holds **the people each company employs**: who they are, where they sit in the organisation,
when they work, their leave, and, from phase 2, what they are paid. Palmal is a garments group, so
most employees are factory workers on shifts and production lines who **never sign in**.

| | |
| --- | --- |
| Menu label | HRM |
| Namespace | `Hrm` |
| Route file | `routes/hrm.php` |
| Name / permission prefix | `hrm.` |
| URL prefix | `hrm/` |
| Pages | `pages/hrm/*` |

**Every record belongs to a company** ([ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company-planned)).

### Employee and User are separate

An **Employee** is an HRM record. A **User** is a sign-in account created by Admin. An employee has an
**optional link to one User** (owner, 2026-10-28). The link lets a member of staff log their own
timesheet hours, and later see their own leave and payslips. Creating an employee never creates an
account, and a User need not be an employee (an external auditor, for example).

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
| **HRM-lite** (ships with Projects) | Employees (code, name, company, department, designation, joining date, status, optional User link, **cost rate**), Departments, Designations. |
| **1 — workforce** | Sections and lines, employee documents, shifts and rosters, attendance (manual + device import), leave types, entitlements and applications. |
| **2 — payroll** | Wage grades, salary structures, overtime, attendance bonus, deductions, the salary sheet and payslips. Payroll posts a **draft voucher** to Accounting and **sets the employee cost rate** that Projects uses. |
| **3 — lifecycle** | Recruitment, appraisal, separation and final settlement. |

## 4. Main entities

- **Department**, **Designation**: company-level lookups (HRM-lite).
- **Section / Line**: the factory-floor units under a department (phase 1).
- **Employee**: employee code, name, company, department, designation, joining date, status,
  optional User, and **cost rate**. Phase 1 adds personal and job details and documents.
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

*Draft — not seeded.* Names follow `{module}.{resource}.{action}` ([ARCHITECTURE
§6.2](../ARCHITECTURE.md#62-routes-and-permissions)).

| Permission | Allows |
| --- | --- |
| `hrm.departments.{view,create,update,delete}` | Departments (HRM-lite) |
| `hrm.designations.{view,create,update,delete}` | Designations (HRM-lite) |
| `hrm.employees.{view,create,update,delete}` | Employees (HRM-lite) |
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

- Who can see an employee's cost rate and salary? This probably needs a separate permission, such as
  `hrm.employees.view-compensation`.
- Employee code format: per company, per department, or one series across the group?
- Which attendance devices are used, and how are they imported (file, API, database pull)?
- Does a linked User see a self-service area (own attendance, leave, payslips)? If so, which module
  owns those pages?
- Bangladesh Labour Act parameters: the overtime multiplier, attendance-bonus rules, and festival
  bonus. Are they configurable per company?
