# Projects Module — Reference

> **📋 Planned — built so far: the project stage enum ([§4](#project-stages)), Clients
> ([§4](#clients--built)), Product Categories ([§4](#product-categories--built)), Supplier Categories ([§4](#supplier-categories--built)), Suppliers
> ([§4](#suppliers--built)), Tender Types ([§4](#tender-types--built)), Units of Measure
> ([§4](#units-of-measure--built)), Certification Categories
> ([§4](#certification-categories--built)) and Certifications ([§4](#certifications--built)).** This is the agreed scope for the Project Management module (owner,
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
It keeps the **client list** the whole ERP bills and the **supplier list** the whole ERP buys from
(owner, 2026-11-02). It does not issue invoices itself. It raises **billing requests**, and Accounting turns them into
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

- Projects for a client, with contract terms: **milestone billing** (a fixed amount per milestone)
  or **time-and-materials** (hours × billing rate).
- Milestones, tasks (list and board views), and the project team.
- Timesheets: hours per employee per task per day, approved before they can be billed.
- Billing requests: an approved milestone or a batch of approved hours, sent to Accounting.
- Budget against actual: revenue billed, against cost (hours × the employee's cost rate).

**Out of scope**

- Issuing invoices, receiving payments and the customer ledger belong to **Accounting**.
- Payroll, attendance and leave belong to **HRM**. A timesheet is not attendance.
- Purchasing and material costs on a project. These can be added once Accounting has payables;
  the supplier list they will use is already here ([§4](#suppliers--built)).
- Gantt charts, resource levelling and dependencies between tasks. Reconsider after phase 1.

## 3. Phases

Build order across the ERP is **Company (Admin) → Projects → Accounting → HRM** (owner,
2026-10-28). Projects comes first, so it ships with two small early slices that belong to other
modules:

| Phase | Delivers |
| --- | --- |
| **0 — prerequisites** | Admin → Companies and user↔company access ([`admin.md`](admin.md#10-companies)) — **built**. **HRM-lite**: Employees, Departments, Designations ([`hrm.md`](hrm.md#3-phases)) — **built** 2026-10-29; employees carry the monthly salary the cost rate will come from. **Projects → Clients** ([§4](#clients--built)) — **built** 2026-11-02, in place of the planned Accounting → Customers. **Projects → Suppliers** and their **Supplier Categories** ([§4](#suppliers--built)) — **built** 2026-11-02, in place of Accounting phase 2's planned supplier list. |
| **1 — delivery** | Projects with contract terms, milestones, tasks (list + board), project members with billing rates, timesheets with approval. |
| **2 — billing** | Billing requests from approved milestones and hours. They wait as **ready to invoice** until Accounting's invoices exist, then become invoices. |
| **3 — profitability** | Budget against actual per project and per milestone, using the employee cost rate. |

## 4. Main entities

- **Project**: belongs to a company and a **client** ([below](#clients--built)). It has a project
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

### Clients — built

*Built 2026-11-02 (owner's decisions of that day).* **Projects → Clients** (`projects/clients`,
`projects.clients.index`) is the organizations the company runs projects for — and **the ERP's one
customer list**. It was planned as Accounting → Customers; the owner moved it here because Projects
needs a client for every project before Accounting exists. Accounting's invoices will point at the
same rows and add their billing fields (currency, credit terms, receivable account) when Accounting
is built ([`accounting.md`](accounting.md)).

| Column | Rule |
| --- | --- |
| `company_id` | The selected company, stamped on create; never a form field ([ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company)). |
| `code` | Required, up to 20, letters and digits joined by single `-`/`_`, stored in capitals. Unique **within the company**, ignoring case, deleted clients included. |
| `name` | Required, up to 150, trimmed and inner spaces collapsed. Unique like the code. |
| `contact_person` | Optional, up to 150, tidied like the name. |
| `phone` | Optional, up to 30: digits, spaces and `+ ( ) -`. No national format — a client may be abroad. |
| `email` | Optional, a valid address, stored lowercase. **Not unique**: two clients may share a group or billing address. |
| `address` | Optional, up to 500. |
| `description` | Optional free text. Shown in the form, not the list. |
| `status` | `A` / `I` ([§9.3.1](../ARCHITECTURE.md#931-activeinactive-status)). An inactive client stays on record but will not be offered for new projects. |
| `inserted_by`, `last_updated_by`, timestamps, `deleted_at` | The usual actor stamps; restorable. Every change is in the audit trail (record type *Client*). |

- **Company-scoped**, shaped as HRM → Departments: another company's client is a 404; with no
  company selected the page shows the no-company panel and hides **New client**. The same code or
  name in another company is fine.
- **Active / Deleted tabs.** Delete moves a client to Deleted, where it can be restored or deleted
  for good; a deleted client keeps its code and name reserved, and creating a duplicate says to
  restore it instead.
- **Bulk delete** on both tabs ([ARCHITECTURE §8.6](../ARCHITECTURE.md#86-every-list-is-paginated-sortable-and-filtered-per-column)).
- **The list** filters by code (starts with), name, contact person and email (contains), phone
  (starts with) and status; it sorts by code and name.
- **No deletion guard yet**: nothing points at a client until projects are built. The projects
  table adds `client_id` with `restrictOnDelete()` and a `ClientService::deletionBlocker()` clause
  ([ARCHITECTURE §9.14](../ARCHITECTURE.md#914-master-data-and-deletion-guards)).
- **Outward guard:** a company that still has clients, deleted ones included, cannot be deleted
  ([`admin.md` §10.1](admin.md#101-the-company-master--admin--companies)).
- **No spreadsheet import** (owner, 2026-11-02): clients are entered by hand, so there is no
  bulk-load path.
- **Roles:** `project-manager` holds every `projects.clients.*` by rule, `viewer` the `.view`; the
  `admin` role holds none (it takes no module prefix).

Tests: `tests/Feature/ProjectManagement/ClientTest.php`. Guide: Projects → Clients.

### Product categories — built

*Built 2026-11-03 (owner's decisions of that day).* **Projects → Product Categories**
(`projects/product-categories`, `projects.product-categories.index`, model `ProductCategory`) is the
groups a company's products and items are sorted into. **What will point at a category is not yet
decided** — the owner will name it; until then the wording stays general. **Company-scoped**, shaped
as Clients: each company keeps its own list, and the same code or name in another company is fine.

| Column | Rule |
| --- | --- |
| `company_id` | The selected company, stamped on create; never a form field ([ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company)). `restrictOnDelete()`. |
| `code` | Required, up to 20, letters and digits joined by single `-`/`_`, stored in capitals. Unique **within the company**, ignoring case, deleted categories included. |
| `name` | Required, up to 100, trimmed and inner spaces collapsed. Unique like the code. |
| `description` | Optional free text, up to 5000. Shown in the form, not the list. |
| `status` | `A` / `I` ([§9.3.1](../ARCHITECTURE.md#931-activeinactive-status)). An inactive category stays on record but will not be offered for new entries. |
| `inserted_by`, `last_updated_by`, timestamps, `deleted_at` | The usual actor stamps; restorable. Every change is in the audit trail (record type *Product category*). |

- **Company-scoped**: another company's category is a 404; with no company selected the page shows
  the no-company panel and hides **New product category**.
- **Active / Deleted tabs**, **bulk delete** on both; a deleted category keeps its code and name
  reserved, and creating a duplicate says to restore it instead.
- **The list** shows code, name and status. It filters by code (starts with), name (contains) and
  status; it sorts by code, name, status and created date.
- **Every company starts with a starter set** (owner, 2026-11-03) — the first seeded master kept per
  company ([ARCHITECTURE §9.14](../ARCHITECTURE.md#914-master-data-and-deletion-guards)). The 13
  categories live in `ProductCategoryService::STARTER`: RAW Raw Materials, CONS Consumables, SPARE
  Spare Parts, EQUIP Equipment & Machinery, TOOL Tools, ELEC Electrical, IT IT & Electronics, FURN
  Furniture & Fixtures, STAT Stationery & Office Supplies, PACK Packaging, FIN Finished Goods, SERV
  Services, OTHER Other.
  - `CompanyService::create()` adds them to **every new company**, in the same transaction, each an
    audited create by the person who made the company.
  - `ProductCategorySeeder` (called from `DatabaseSeeder`) **back-fills existing companies**; on a
    fresh install, where no company exists yet, it adds nothing.
  - Both go through `ProductCategoryService::addStarterSet()`, which only **adds**: a starter row
    whose code or name is already taken in the company — a deleted one included, ignoring case — is
    skipped, so re-running never overwrites an edit. Deleted companies are not back-filled.
- **No deletion guard yet**: nothing points at a product category. The first table with a
  `product_category_id` adds `restrictOnDelete()` and a `ProductCategoryService::deletionBlocker()`
  clause.
- **Outward guard:** a company that still has product categories, deleted ones included, cannot be
  deleted ([`admin.md` §10.1](admin.md#101-the-company-master--admin--companies)). Because every
  company made on screen starts with the starter set, **a company is deleted only after its product
  categories are permanently deleted** — the owner's choice (2026-11-03) over removing them with the
  company; deactivating remains the normal way to retire a company.
- **No spreadsheet import** (owner, 2026-11-03): categories beyond the starter set are entered by hand.
- **Roles:** `project-manager` holds every `projects.product-categories.*` by rule — per-company
  data, so no `ROLE_EXCLUSIONS` entry — `viewer` the `.view`; the `admin` role holds none.

Tests: `tests/Feature/ProjectManagement/ProductCategoryTest.php` (and the company-side rules in
`tests/Feature/Admin/CompanyTest.php`). Guide: Projects → Product categories.

### Supplier categories — built

*Built 2026-11-02 (owner's decisions of that day).* **Projects → Supplier Categories**
(`projects/supplier-categories`, `projects.supplier-categories.index`) is what kind of supplier each
one is — materials, services, subcontractors, equipment hire and the like. Every supplier is in
exactly one. A class of supplier is not a fact about one company, so the list is **group-wide**: one
list every company shares, with no `company_id` — the third ERP exception to
[ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company), shaped as Tender Types with a
description, and guarded like HRM → Designations.

| Column | Rule |
| --- | --- |
| `code` | Required, up to 20, letters and digits joined by single `-`/`_`, stored in capitals. Unique **across the whole table**, ignoring case, deleted categories included. |
| `name` | Required, up to 100, trimmed and inner spaces collapsed. Unique like the code. |
| `description` | Optional free text. Shown in the form, not the list. |
| `status` | `A` / `I` ([§9.3.1](../ARCHITECTURE.md#931-activeinactive-status)). An inactive category is kept by the suppliers already in it but not offered for new ones or for a change of category. |
| `inserted_by`, `last_updated_by`, timestamps, `deleted_at` | The usual actor stamps; restorable. Every change is in the audit trail (record type *Supplier category*). |

- **Group-wide**: the page works whether or not a company is selected; an edit or delete shows in
  every company.
- **Active / Deleted tabs**, **bulk delete** on both, a deleted category keeps its code and name
  reserved and creating a duplicate says to restore it instead.
- **The list** filters by code (starts with), name (contains) and status; it sorts by code, name,
  status and created date.
- **Deletion guard** ([ARCHITECTURE §9.14](../ARCHITECTURE.md#914-master-data-and-deletion-guards)):
  `suppliers.supplier_category_id` restricts on delete, and `SupplierCategoryService::deletionBlocker()`
  refuses a delete or permanent delete while any supplier, in any company, deleted ones included, is
  in the category — "Used by 3 suppliers. Move them to another category, or deactivate this one
  instead." as a `warning` toast. Bulk delete removes the free categories and names the kept ones.
  **Deactivating a category in use is allowed** (owner, 2026-11-02).
- **No spreadsheet import and no seed** (owner, 2026-11-02): a handful of rows entered by hand; a new
  installation starts with none, so categories must be made before the first supplier.
- **Roles:** `project-manager` holds every `projects.supplier-categories.*` by rule — unlike tender
  types, there is no `ROLE_EXCLUSIONS` entry (owner, 2026-11-02, "mapped like Clients") — `viewer` the
  `.view`; the `admin` role holds none.

Tests: `tests/Feature/ProjectManagement/SupplierCategoryTest.php`. Guide: Projects → Supplier
categories.

### Suppliers — built

*Built 2026-11-02 (owner's decisions of that day).* **Projects → Suppliers** (`projects/suppliers`,
`projects.suppliers.index`) is the firms the company buys from — and **the ERP's one supplier list**.
It was planned as part of Accounting's payables; the owner placed it here, as with Clients, and
Accounting's bills will point at the same rows and add their own billing fields (currency, payment
terms, payable account) when Accounting phase 2 is built ([`accounting.md`](accounting.md)).

| Column | Rule |
| --- | --- |
| `company_id` | The selected company, stamped on create; never a form field ([ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company)). |
| `supplier_category_id` | **Required**, a group-wide [supplier category](#supplier-categories--built). Must be active and not deleted to be chosen; a supplier whose category was later deactivated keeps it on edit. |
| `code`, `name`, `contact_person`, `phone`, `email`, `address`, `description`, `status` | Exactly as on a [client](#clients--built): code and name unique **within the company**, ignoring case, deleted suppliers included; the email not unique. |
| `tin` | Optional, exactly 12 digits. **Not unique** (owner, 2026-11-02): branches of one firm may share it. |
| `bin` | Optional VAT registration (BIN), up to 20: digits optionally joined by single `-`. Not unique. |
| `trade_license_no` | Optional, up to 50, free text. |
| `bank_name`, `bank_branch`, `bank_account_name` | Optional, up to 150 each, trimmed and inner spaces collapsed. |
| `bank_account_number` | Optional, up to 30: letters, digits, spaces and `-` (an IBAN abroad), stored in capitals. |
| `bank_routing_number` | Optional, up to 20 digits. |
| `inserted_by`, `last_updated_by`, timestamps, `deleted_at` | The usual actor stamps; restorable. Every change is in the audit trail (record type *Supplier*). |

- **One bank account per supplier** (owner, 2026-11-02), held as columns on the row; the bank
  details, address, description and trade licence are read in the form, not the list.
- **Company-scoped**, shaped as Clients: another company's supplier is a 404; with no company
  selected the page shows the no-company panel and hides **New supplier**.
- **Active / Deleted tabs**, **bulk delete** on both, a deleted supplier keeps its code and name
  reserved.
- **The list** shows code, name, category, contact person, phone, email, TIN, BIN and status. It
  filters by code, phone, TIN and BIN (starts with), name, contact person and email (contains),
  category and status (dropdowns); it sorts by code and name.
- **No deletion guard yet**: nothing points at a supplier until Accounting's bills (or Projects'
  purchases) are built; that table adds `supplier_id` with `restrictOnDelete()` and a
  `SupplierService::deletionBlocker()` clause.
- **Outward guards:** a company that still has suppliers, deleted ones included, cannot be deleted
  ([`admin.md` §10.1](admin.md#101-the-company-master--admin--companies)); a category suppliers use
  cannot be deleted (above).
- **No spreadsheet import** (owner, 2026-11-02): suppliers are entered by hand.
- **Roles:** `project-manager` holds every `projects.suppliers.*` by rule, `viewer` the `.view`; the
  `admin` role holds none.

Tests: `tests/Feature/ProjectManagement/SupplierTest.php`. Guide: Projects → Suppliers.

### Tender types — built

*Built 2026-11-02 (owner's decisions of that day).* **Projects → Tender Types**
(`projects/tender-types`, `projects.tender-types.index`) is how a project was procured — open
tendering, limited tendering, request for quotation, direct procurement and the like. A procurement
method is a classification, not a fact about one company, so the list is **group-wide**: one list
every company shares, with no `company_id` — the second ERP exception to
[ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company), shaped as HRM → Designations.

| Column | Rule |
| --- | --- |
| `code` | Required, up to 20, letters and digits joined by single `-`/`_`, stored in capitals. Unique **across the whole table**, ignoring case, deleted tender types included. |
| `name` | Required, up to 100, trimmed and inner spaces collapsed. Unique like the code. |
| `status` | `A` / `I` ([§9.3.1](../ARCHITECTURE.md#931-activeinactive-status)). An inactive tender type stays on record but will not be offered for new projects. |
| `inserted_by`, `last_updated_by`, timestamps, `deleted_at` | The usual actor stamps; restorable. Every change is in the audit trail (record type *Tender type*). |

- **Group-wide**: the page works whether or not a company is selected, and every company sees the
  same rows — an edit or delete shows in all of them.
- **Active / Deleted tabs.** Delete moves a tender type to Deleted, where it can be restored or
  deleted for good; a deleted tender type keeps its code and name reserved, and creating a duplicate
  says to restore it instead.
- **Bulk delete** on both tabs ([ARCHITECTURE §8.6](../ARCHITECTURE.md#86-every-list-is-paginated-sortable-and-filtered-per-column)).
- **The list** filters by code (starts with), name (contains) and status; it sorts by code, name,
  status and created date, by code by default.
- **No deletion guard yet**: nothing points at a tender type until projects are built. The projects
  table adds `tender_type_id` with `restrictOnDelete()` and a `TenderTypeService::deletionBlocker()`
  clause ([ARCHITECTURE §9.14](../ARCHITECTURE.md#914-master-data-and-deletion-guards)).
- **No spreadsheet import and no seed** (owner, 2026-11-02): the list is a handful of rows entered by
  hand, so there is no bulk-load path, and a new installation starts with none.
- **Roles:** `project-manager` holds `projects.tender-types.{view,create,update}` but not
  `delete`, `restore` or `force-delete` (`ROLE_EXCLUSIONS`, owner, 2026-11-02): the list is shared by
  every company, so removing a tender type stays with super-admin — the rule HR officers follow for
  designations. `viewer` holds the `.view`; the `admin` role holds none.

Tests: `tests/Feature/ProjectManagement/TenderTypeTest.php`. Guide: Projects → Tender types.

### Units of measure — built

*Built 2026-11-02 (owner's decisions of that day).* **Projects → Units of Measure**
(`projects/units`, `projects.units.index`, model `Unit`) is what a quantity is counted in — pieces,
kilograms, square metres, hours, lump sum and the like — for the BOQ, budget and bill lines that will
point at it. A kilogram is the same in every company, so the list is **group-wide**: one list every
company shares, with no `company_id` — the fourth ERP exception to
[ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company), shaped as Tender Types. Units are not
grouped by what they measure, so there are no conversions between them (owner, 2026-11-02).

| Column | Rule |
| --- | --- |
| `code` | Required, up to 20, letters and digits joined by single `-`/`_`, stored in capitals. Unique **across the whole table**, ignoring case, deleted units included. |
| `name` | Required, up to 100, trimmed and inner spaces collapsed. Unique like the code. |
| `symbol` | Optional, up to 10 — the printed form (`kg`, `m²`). Trimmed only, case kept; **may repeat**. |
| `decimal_places` | Required, 0–6, default 2 — how many decimals a quantity in the unit takes (PCS 0, KG 3). The quantity fields that point at a unit will validate against it. |
| `description` | Optional, up to 255. Shown in the form, not the list. |
| `status` | `A` / `I` ([§9.3.1](../ARCHITECTURE.md#931-activeinactive-status)). An inactive unit stays on record but will not be offered for new entries. |
| `inserted_by`, `last_updated_by`, timestamps, `deleted_at` | The usual actor stamps; restorable. Every change is in the audit trail (record type *Unit*). |

- **Group-wide**: the page works whether or not a company is selected, and every company sees the
  same rows — an edit or delete shows in all of them.
- **Active / Deleted tabs**, bulk delete on both, and a deleted unit keeps its code and name reserved,
  exactly as tender types.
- **The list** filters by code (starts with), name and symbol (contains) and status; it sorts by code,
  name, symbol, decimal places, status and created date, by code by default.
- **Seeded** (owner, 2026-11-02; [ARCHITECTURE §9.14](../ARCHITECTURE.md#914-master-data-and-deletion-guards)):
  `Database\Seeders\ProjectManagement\UnitSeeder`, called from `DatabaseSeeder`, adds 27 standard
  units — PCS, NOS, SET, PAIR, DOZEN, BOX, BAG, ROLL, LS, G, KG, TON, MM, CM, M, KM, RFT, SQM, SFT, CUM,
  ML, L, HR, DAY, WEEK, MONTH, YEAR — each with its symbol and decimal places. Like `CountrySeeder` it
  only adds: a unit whose code or name is taken, a deleted one included, is skipped, so edits are never
  overwritten.
- **No spreadsheet import** (owner, 2026-11-02): units beyond the seeded set are added by hand.
- **No deletion guard yet**: nothing points at a unit. The first table with a `unit_id` adds
  `restrictOnDelete()` and a `UnitService::deletionBlocker()` clause.
- **Roles:** as tender types — `project-manager` holds `projects.units.{view,create,update}` but not
  `delete`, `restore` or `force-delete` (`ROLE_EXCLUSIONS`); `viewer` holds the `.view`; the `admin`
  role holds none.

Tests: `tests/Feature/ProjectManagement/UnitTest.php`. Guide: Projects → Units of measure.

### Certification categories — built

*Built 2026-11-02 (owner's decisions of that day).* **Projects → Certification Categories**
(`projects/certification-categories`, `projects.certification-categories.index`, model
`CertificationCategory`) groups the certifications below — quality management, environmental,
social compliance, textile & apparel, steel & metals, pipes & plastics and the like. **Group-wide**
(the fifth ERP exception to [ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company)), and shaped
exactly as Supplier Categories: `code` (up to 20) and `name` (up to 100) each unique across the table,
ignoring case, deleted categories included; optional `description`; `status`; restorable with
Active / Deleted tabs and bulk delete on both; audited as *Certification category*.

- **Deletion guard** ([ARCHITECTURE §9.14](../ARCHITECTURE.md#914-master-data-and-deletion-guards)):
  `CertificationCategoryService::deletionBlocker()` refuses to delete — or permanently delete — a
  category any certification uses, deleted certifications included ("Used by 3 certifications. Move
  them to another category, or deactivate this one instead.", a warning toast). Bulk delete deletes
  the free ones and names the rest. Deactivating a category in use is allowed: its certifications
  keep it, new ones cannot choose it.
- **Seeded**: `CertificationCategorySeeder` adds 16 categories — QMS, EMS, OHS, ISMS, ENMS, FSMS, LAB,
  SOC, TXT, ELEC, STEEL, PIPE, MACH, RADAR, NAT (BSTI/BDS), OTHER — skipping any whose code or name is
  taken, as `UnitSeeder`.
- **No spreadsheet import.** **Roles:** as tender types — `project-manager` holds
  `projects.certification-categories.{view,create,update}` but not `delete`, `restore` or
  `force-delete` (`ROLE_EXCLUSIONS`); `viewer` holds the `.view`; the `admin` role holds none.

Tests: `tests/Feature/ProjectManagement/CertificationCategoryTest.php`. Guide: Projects →
Certification categories.

### Certifications — built

*Built 2026-11-02 (owner's decisions of that day).* **Projects → Certifications**
(`projects/certifications`, `projects.certifications.index`, model `Certification`) is a **catalogue
of standards** — ISO 9001, ISO 14001, amfori BSCI, OEKO-TEX, CE, the BSTI mark — that a supplier may
hold or a tender may demand. A row is a standard, **not a certificate someone holds**: a held
certificate, with its number, issuer and expiry, will be a table of its own pointing here, on the
screens the owner names later. **Group-wide** (the sixth ERP exception to §9.10), shaped as Tender
Types with a required category as Suppliers have.

| Column | Rule |
| --- | --- |
| `certification_category_id` | Required ("The category field is required." — the request names the field *category*); an **active** category to be chosen, though a certification keeps a category that was deactivated later. `restrictOnDelete()`. |
| `code` | Required, up to 30, letters and digits joined by single `-`/`_`, stored in capitals (`ISO9001-2015`). Unique **across the whole table**, ignoring case, deleted certifications included. |
| `name` | Required, up to 150, trimmed and inner spaces collapsed. Unique **per version**, ignoring case, deleted certifications included: ISO 9001 2008 and 2015 are two rows. A blank version counts as one edition, so two unversioned rows of one name clash (the request enforces this; the `(name, version)` unique index cannot, since NULLs differ). |
| `issuing_body` | Optional, up to 150, free text (ISO, amfori, BSTI). |
| `version` | Optional, up to 20, trimmed (`2015`, `2014/68/EU`). |
| `description` | Optional, up to 5000. Shown in the form, not the list. |
| `status` | `A` / `I`. An inactive certification stays on record but will not be offered for new entries. |
| `inserted_by`, `last_updated_by`, timestamps, `deleted_at` | The usual actor stamps; restorable. Every change is in the audit trail (record type *Certification*). |

- **The list** filters by code and version (starts with), name and issuing body (contains), category
  (a combobox) and status; it sorts by code, name, issuing body, version, status and created date —
  not by category, a joined column.
- **Seeded**: `CertificationSeeder`, after `CertificationCategorySeeder`, adds 48 standards — the ISO
  management systems (9001, 14001, 45001, 27001, 50001, 22000, 17025) and HACCP; garment and textile
  schemes (SA8000, amfori BSCI, WRAP, SMETA, OEKO-TEX 100, GOTS, GRS, OCS, bluesign, Higg FEM);
  electrical (CE, UL, RoHS, IEC 60947, IEC 61439, ISO/IEC 17065); steel and plain sheets (ISO 1461,
  ASTM A615, BS 4449, ISO 3834, AWS D1.1, ASTM A653, JIS G3302, EN 10346); pipes and plastics (ISO
  4427, ISO 1452, ASTM D3035, ISO 15874); machinery (ASME U, PED, ISO 12100, API Q1); radar and
  telecom (ITU-R M.1371, IEC 62388, IEC 60945, FCC, AS9100); and the BSTI mark with BDS ISO 9001 and
  14001. It skips a row whose code, or name in that version, is taken, and a row whose category code
  is gone.
- **No spreadsheet import** (owner, 2026-11-02): standards beyond the seeded set are added by hand.
- **No deletion guard yet**: nothing points at a certification. The first table with a
  `certification_id` adds `restrictOnDelete()` and a `CertificationService::deletionBlocker()` clause.
- **Roles:** as tender types — `project-manager` holds `projects.certifications.{view,create,update}`
  only; `viewer` the `.view`; `admin` none.

Tests: `tests/Feature/ProjectManagement/CertificationTest.php`. Guide: Projects → Certifications.

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

*Draft — not seeded*, except `projects.clients.*`, `projects.product-categories.*`, `projects.supplier-categories.*`,
`projects.suppliers.*`, `projects.tender-types.*`, `projects.units.*`,
`projects.certification-categories.*` and `projects.certifications.*`
(`{view,create,update,delete,restore,force-delete}` each), which shipped with their screens ([§4](#4-main-entities)). Names follow `{module}.{resource}.{action}` ([ARCHITECTURE
§6.2](../ARCHITECTURE.md#62-routes-and-permissions)). Each is added to `RolePermissionSeeder::CATALOGUE`
and `PermissionDescriptions` when its screen is built.

| Permission | Allows |
| --- | --- |
| `projects.clients.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the selected company's clients |
| `projects.product-categories.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the selected company's product categories |
| `projects.supplier-categories.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the supplier categories every company shares |
| `projects.suppliers.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the selected company's suppliers |
| `projects.tender-types.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the tender types every company shares |
| `projects.units.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the units of measure every company shares |
| `projects.certification-categories.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the certification categories every company shares |
| `projects.certifications.{view,create,update,delete,restore,force-delete}` | **Built.** Manage the catalogue of certifications every company shares |
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
| Accounting | Currencies | **Clients** (the one customer list), **Suppliers** (the one supplier list, for bills), billing requests → invoices. Later, project cost as a draft voucher ([ARCHITECTURE §9.12](../ARCHITECTURE.md#912-posting-to-the-ledger-planned)) |

## 8. Open decisions

- Is moving a project between stages part of `projects.projects.update`, or its own permission?
- Is a dated history of stage changes kept, or is the audit trail enough?
- The badge colour of each stage on the list and board.
- Can a member see tasks across the whole project, or only their own?
- Who can see the cost rate and the profitability report? This is salary-derived data, so the
  likely answer is a separate permission.
- Project codes and billing-request numbering: the format, and whether it is per company.
- What points at a product category (an item catalogue, BOQ or purchase lines, projects?) — which
  decides where its deletion guard goes.
- Is there a weekly timesheet lock, and how late can hours be logged?
- Does a project in a foreign currency convert its budget into BDT for reporting? This depends on
  Accounting's exchange-rate source.
