# Projects Module — Reference

> **📋 Planned — only the project stage enum is built** ([§4](#project-stages)). This is the agreed scope for the Project Management module (owner,
> 2026-10-28). It describes *what* the module will do and *why*; where things will live and what they
> are called is [`ARCHITECTURE.md`](../ARCHITECTURE.md)'s job. When the first slice is built, this
> file becomes its reference and the planned parts shrink as they ship.
>
> **🧪 POC overview:** `/projects` (`projects.dashboard`, permission `projects.dashboard.view`) shows sample data
> under a Preview banner. Its page body and `components/projects/sample-data.ts` are throwaway; the route,
> permission and role stay ([`ARCHITECTURE.md` §5](../ARCHITECTURE.md#5-module-registry)).

---

## 1. Overview

Project Management runs **client projects from contract to invoice**: what was agreed with the
customer, who works on it, the tasks and milestones, the hours logged, and what is ready to bill.
It does not issue invoices itself. It raises **billing requests**, and Accounting turns them into
invoices ([§7](#7-links-to-other-modules)).

| | |
| --- | --- |
| Menu label | Projects |
| Namespace | `ProjectManagement` (`Http/Controllers/ProjectManagement/`, `Models/ProjectManagement/`, …) |
| Route file | `routes/projects.php` |
| Name / permission prefix | `projects.` |
| URL prefix | `projects/` |
| Pages | `pages/projects/*` |

**Every record belongs to a company** ([ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company)).
A user works in one selected company at a time and sees only that company's projects.

## 2. Scope

**In scope**

- Projects for a customer, with contract terms: **milestone billing** (a fixed amount per milestone)
  or **time-and-materials** (hours × billing rate).
- Milestones, tasks (list and board views), and the project team.
- Timesheets: hours per employee per task per day, approved before they can be billed.
- Billing requests: an approved milestone or a batch of approved hours, sent to Accounting.
- Budget against actual: revenue billed, against cost (hours × the employee's cost rate).

**Out of scope**

- Issuing invoices, receiving payments and the customer ledger belong to **Accounting**.
- Payroll, attendance and leave belong to **HRM**. A timesheet is not attendance.
- Purchasing and material costs on a project. These can be added once Accounting has payables.
- Gantt charts, resource levelling and dependencies between tasks. Reconsider after phase 1.

## 3. Phases

Build order across the ERP is **Company (Admin) → Projects → Accounting → HRM** (owner,
2026-10-28). Projects comes first, so it ships with two small early slices that belong to other
modules:

| Phase | Delivers |
| --- | --- |
| **0 — prerequisites** | Admin → Companies and user↔company access ([`admin.md`](admin.md#10-companies)) — **built**. **HRM-lite**: Employees, Departments, Designations ([`hrm.md`](hrm.md#3-phases)) — **built** 2026-10-29; employees carry the monthly salary the cost rate will come from. **Accounting → Customers** ([`accounting.md`](accounting.md#3-phases)) — not yet built. |
| **1 — delivery** | Projects with contract terms, milestones, tasks (list + board), project members with billing rates, timesheets with approval. |
| **2 — billing** | Billing requests from approved milestones and hours. They wait as **ready to invoice** until Accounting's invoices exist, then become invoices. |
| **3 — profitability** | Budget against actual per project and per milestone, using the employee cost rate. |

## 4. Main entities

- **Project**: belongs to a company and a **customer** (Accounting's master). It has a project
  manager (an employee), a billing method (milestone or time-and-materials), a currency, start and
  end dates, a budget, a **stage** ([below](#project-stages)) and the usual Active/Inactive status.
- **Milestone**: a dated deliverable of one project. Under milestone billing it carries the amount
  to bill on completion.
- **Task**: belongs to a project and optionally a milestone. It has an assignee (a project member),
  a status column on the board, a priority, a due date and an estimate in hours.
- **Project member**: an **Employee** on a project, with a role on it and a **billing rate**. The
  billing rate is per project because each contract sets its own.
- **Timesheet entry**: an employee, a project, an optional task, a date, hours, a note and an
  approval status.
- **Billing request**: a project, a source (a milestone, or a set of timesheet entries), an amount
  in the project currency, and a status (ready to invoice, invoiced, cancelled). Once invoiced it
  links to the Accounting invoice.

### Project stages

*Built 2026-11-02 as `App\Enums\ProjectManagement\ProjectStage` (id `project-stage`). The projects
table that will use it is not built yet.* The organizations using Nexora deliver projects for client
companies, and every project passes through these stages (owner, 2026-11-02):

| Stage | Stored | Means |
| --- | --- | --- |
| Upcoming | `upcoming` | A lead has been noted. **Every project starts here.** |
| Evaluation | `evaluation` | Being assessed or quoted before a contract, waiting for the client's decision. |
| Running | `running` | Contracted and in delivery. |
| On Hold | `on-hold` | Paused, by the client or the organization. |
| Completed | `completed` | Delivered. *Closed.* |
| Cancelled | `cancelled` | The bid was lost or the project was abandoned. *Closed.* |

- **One stage at a time.** A project is always in exactly one stage. The usual path is
  Upcoming → Evaluation → Running → Completed, but a project may move from any stage to any other,
  forward or back.
- **Reopening is a separate permission.** Moving a project *out* of a closed stage (Completed or
  Cancelled) needs `projects.projects.reopen` ([§6](#6-draft-permission-catalogue)). The guard will
  live in the project service; the enum only answers `isClosed()`.
- **Stage is not the Active/Inactive status.** A project also carries the standard status flag
  ([ARCHITECTURE §9.3.1](../ARCHITECTURE.md#931-activeinactive-status)). Inactive *archives* a
  project at any stage: it is kept and still reported on, but no longer offered when logging hours,
  assigning tasks or raising billing. Cancelled is the business outcome. The two are independent.

### Billing rate and cost rate

These are two different numbers (owner, 2026-10-28):

- The **billing rate** is what the customer pays per hour. It is set by the contract, so it lives on the
  **project member**.
- The **cost rate** is what an hour of that person costs the company. It is the same on every
  project, so it lives on the **Employee** in HRM: entered by hand for now, and calculated by payroll
  in HRM phase 2.

For example, Karim (Senior Developer, BDT 80,000 a month ≈ BDT 455 an hour) logs 10 hours on project
A at USD 25/h and 10 hours on project B at USD 30/h. A bills USD 250 and B bills USD 300, and both
cost BDT 4,550.

## 5. Workflows and approvals

Approvals follow the ERP-wide pattern ([ARCHITECTURE §9.11](../ARCHITECTURE.md#911-approvals-planned)):
a status of draft → submitted → approved or rejected, an `.approve` permission, and a service blocker
that stops anyone approving their own record.

- **Timesheets.** An employee who has a linked sign-in logs their **own** hours. A holder of
  `projects.timesheets.manage` logs hours **on behalf of** any member, which covers the many
  employees who never sign in. Hours are submitted, then approved by the project manager or a holder
  of `projects.timesheets.approve`. Only approved hours can be billed, and billed hours are locked.
- **Milestones.** A milestone is marked complete; under milestone billing that raises a billing
  request for its amount.
- **Billing requests.** These are raised from an approved milestone, or from a selection of approved,
  unbilled hours (summed per member at their billing rate). They wait as **ready to invoice**.
  Accounting creates the invoice, and the request becomes **invoiced** and is locked.

## 6. Draft permission catalogue

*Draft — not seeded.* Names follow `{module}.{resource}.{action}` ([ARCHITECTURE
§6.2](../ARCHITECTURE.md#62-routes-and-permissions)). Each is added to `RolePermissionSeeder::CATALOGUE`
and `PermissionDescriptions` when its screen is built.

| Permission | Allows |
| --- | --- |
| `projects.projects.{view,create,update,delete}` | Manage projects and their contract terms |
| `projects.projects.reopen` | Move a project out of Completed or Cancelled ([§4](#project-stages)) |
| `projects.milestones.{view,create,update,delete}` | Manage milestones |
| `projects.tasks.{view,create,update,delete}` | Manage tasks |
| `projects.members.{view,manage}` | Staff a project and set billing rates |
| `projects.timesheets.view` | See timesheets beyond one's own |
| `projects.timesheets.manage` | Log hours on behalf of any member |
| `projects.timesheets.approve` | Approve or reject submitted hours |
| `projects.billing-requests.{view,create,cancel}` | Raise and cancel billing requests |
| `projects.reports.view` | Budget against actual |

## 7. Links to other modules

| Module | Projects uses | Projects provides |
| --- | --- | --- |
| Admin | Companies, user↔company access | — |
| HRM | Employees (members, project managers), the employee **cost rate** | — |
| Accounting | Customers, currencies | Billing requests → invoices. Later, project cost as a draft voucher ([ARCHITECTURE §9.12](../ARCHITECTURE.md#912-posting-to-the-ledger-planned)) |

## 8. Open decisions

- Is moving a project between stages part of `projects.projects.update`, or its own permission?
- Is a dated history of stage changes kept, or is the audit trail enough?
- The badge colour of each stage on the list and board.
- Can a member see tasks across the whole project, or only their own?
- Who can see the cost rate and the profitability report? This is salary-derived data, so the
  likely answer is a separate permission.
- Project codes and billing-request numbering: the format, and whether it is per company.
- Is there a weekly timesheet lock, and how late can hours be logged?
- Does a project in a foreign currency convert its budget into BDT for reporting? This depends on
  Accounting's exchange-rate source.
