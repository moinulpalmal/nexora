# Nexora — Architecture

The authoritative map of this repository: which module owns each concern, where every kind of class
belongs, how routes and pages are named, and which files must be edited to register a new surface.
**Read it before planning any change.** If the code on disk disagrees with it, the disk is the fact
and this file is the bug — fix it in the same change ([§12](#12-keeping-this-file-in-sync)).

It is a decision log as much as a map: a rule states *why*, and a decision the owner made carries
`(owner, date)` so the next agent inherits it instead of re-litigating it. Nexora was bootstrapped
from the patterns of the sibling project `compozit-suite`; rules marked *(from compozit)* were proven
there first. Section numbers follow compozit's where code comments cite them, so a few numbers are
reserved or skipped on purpose.

---

## 1. What this application is

Nexora is a **general-purpose ERP for small organizations**, not built for any one organization
or industry: one installation serves a single company or a small group of companies (owner,
2026-10-28). **Keep it organization- and industry-neutral** — no client names or industry-specific
wording in code, docs, sample data or fixtures (owner, 2026-10-29). This repository holds its **foundation**: the application shell, authentication, roles and permissions, the audit trail, and
the Admin module (users, roles, the permission catalogue, the audit log, application settings and
sent mail). Business modules are added on top of it, one module per folder in every layer
([§4](#4-the-organizing-rule)).

The first business modules are **Project Management**, **Accounts** and **HRM**. All three are
planned and none is built yet; their agreed scope is in [§5](#5-module-registry) and in their module
docs.

Accounts are **created by administrators only** — there is no public registration — and sign in with
their **email or username** (owner, 2026-10-26).

## 2. Stack

| Concern | Choice | Why |
| --- | --- | --- |
| Framework | Laravel 13, PHP 8.4 | — |
| Frontend | Inertia v3 + React 19 + TypeScript, React Compiler | One codebase, server-driven routing, no API layer |
| UI | Tailwind CSS 4 + **daisyUI 5**, all 35 themes | Replaced the starter kit's shadcn/Radix entirely (owner, 2026-10-26); `@radix-ui/react-slot` is the one Radix package kept, for `asChild` |
| Selects | `downshift` combobox ([§8.5](#85-selects-are-comboboxes)) | No native `<select>` anywhere |
| Routes in JS | Laravel Wayfinder (`formVariants: true`) | Typed, generated route and form helpers ([§8.2](#82-generated-code--never-edited)) |
| Auth | Laravel Fortify — `resetPasswords()` only | No registration, no email verification, no 2FA (owner, 2026-10-26) |
| RBAC | `spatie/laravel-permission` v8, teams off | Permission-gated routes, super-admin `Gate::before` ([§9.1](#91-rbac-roles--permissions)) |
| Audit | `owen-it/laravel-auditing` 14.0.6 | Same package and model shape as compozit ([§9.3](#93-audit-logging)) |
| Live push | Laravel Reverb + `@laravel/echo-react` | The notification bell ([§9.7](#97-notifications--broadcasting)) |
| Spreadsheets | `phpoffice/phpspreadsheet` (read), `openspout/openspout` (write) | The mapped-import engine ([§9.13](#913-spreadsheet-imports)) — reading every worksheet of an `.xlsx`/`.xls`, and writing the template and rejected-rows workbooks (owner, 2026-10-28) |
| Database | MySQL (development, `nexora`), in-memory SQLite (tests) | [§13.1](#131-the-suite-never-touches-the-development-database) |
| Build | Vite 8, ESLint 9, Prettier 3 | Standard Vite rather than the starter's vite-plus, to match compozit (owner, 2026-10-26) |
| Tests | Pest 5 (+ browser plugin), PHPStan level 7 (Larastan), Pint | [§13](#13-commands) |

## 3. Top-level layout

```
app/
  Actions/Fortify/            ResetUserPassword
  Concerns/                   Model & request traits: Audited, HasStatus, Listable, BelongsToCompany
                              (§9.10), ProvidesOptions, *ValidationRules (incl. MasterDataValidationRules),
                              RoleAssignmentRules
  Console/Commands/           SyncEnums (`php artisan enums:sync`), TrainingGaps (`training:gaps`)
  Contracts/                  Auditable, IdentifiedEnum, LabelledEnum
  Enums/                      FilterType, RecordStatus, Theme, EnumRegistry (module-less)
  Enums/Admin/                AuditEvent, MailEncryption, MailTransport
  Enums/Imports/              DuplicateAction, ImportStatus, SheetFileType (§9.13)
  Exceptions/Admin/           MailNotConfiguredException
  Exceptions/Imports/         SheetImportException
  Http/Controllers/           HomeController, NotificationController (module-less)
  Http/Controllers/Admin/     User, Role, Company, Permission, AuditLog, ApplicationSetting,
                              MailSetting, QuickLoginSetting, BrandingSetting, SentMail
  Http/Controllers/Settings/  Profile, Security, Appearance, CompanySelection (the user's own account)
  Http/Controllers/Imports/   SheetImportController — the abstract base every importer extends
  Http/Controllers/Training/  TrainingController (index, show, panel)
  Http/Controllers/ProjectManagement/, Accounting/, Hrm/
                              DashboardController each — the 🧪 POC overviews (§5);
                              Hrm/ also Department, DepartmentImport, Designation, DesignationImport
  Http/Middleware/            EnsureUserIsActive, EnsurePasswordIsChanged, HandleAppearance, HandleInertiaRequests
  Http/Requests/              ListRequest, BulkRequest (module-less) + Admin/, Settings/, Hrm/,
                              Imports/ (ImportConfirmRequest, shared by every importer)
  Listeners/                  CollapseNavGroupsOnLogin, StampLastLogin (module-less)
  Listeners/Admin/            RecordLogin, RecordLogout, RecordFailedLogin, RecordSentMail
  Mail/Admin/                 UserWelcomeMail, UserPasswordResetMail, MailSettingTestMail
  Models/User.php             (at the root — Laravel's auth convention)
  Models/SheetImport.php, ImportLimit.php   (at the root — the import engine's own records, §9.13)
  Models/Admin/               Role, Permission, Company, AuditLog, MailSetting, QuickLoginSetting,
                              BrandingSetting, SentMail
  Models/Hrm/                 Department (company-scoped, §9.10), Designation (group-wide, §9.10)
  Notifications/Admin/        AccountChanged
  Observers/                  ActorObserver
  Providers/                  AppServiceProvider, FortifyServiceProvider
  Services/Admin/             UserService, RoleService, CompanyService, SelectedCompany (§9.10),
                              PermissionService, PermissionDescriptions, AuditRecorder,
                              AuditLogService, MailSettingService, QuickLoginService,
                              BrandingService, UserCredentialMailer
  Services/Hrm/               DepartmentService, DepartmentImportBlueprint (the first real importer),
                              DesignationService, DesignationImportBlueprint
  Services/Training/          TrainingRegistry (reads config/training.php), TrainingGapFinder
  Services/Imports/           the mapped-import engine (§9.13): ImportRunner, SheetReader,
                              ImportBlueprint, ImportRegistry, the DTOs ImportLimits/Outcome/Preview
config/                       audit.php, permission.php, reverb.php, broadcasting.php,
                              training.php (the guide registry), sheet-import.php (+ Laravel's)
database/
  migrations/                 flat — the migrator does not recurse
  seeders/Admin/              RolePermissionSeeder, SuperAdminSeeder, DemoUserSeeder
public/
  images/training/            guide screenshots, {key}/{shot}.png, committed. Never public/training/:
                              Apache would serve that folder instead of the /training route
resources/
  css/app.css                 Tailwind + daisyUI, field-grid utilities, the Nikosh @font-face
  fonts/nikosh.woff2          Bangla font — a repaired copy; never replace with a fresh download (§9.5)
  views/app.blade.php         root template (theme before first paint)
  views/mail/                 layout.blade.php + admin/*.blade.php
  js/
    app.tsx                   Inertia entry, layout resolution (§8.1)
    actions/ routes/ wayfinder/   GENERATED by Wayfinder — never edit (§8.2)
    components/ui/            daisyUI primitives (button, dialog, combobox, sidebar, …)
    components/shared/        used by two or more modules (list toolbar, pagination, …)
    components/admin/         Admin-only dialogs and panels
    components/notifications/ the bell
    components/{projects,accounting,hrm}/   sample-data.ts for each 🧪 POC overview;
                              hrm/ also the department and designation form dialogs and the
                              no-company panel
    components/training/      the manual: vocabulary, guide loader, language toggle, the header's ?,
                              table of contents; guides/{general,admin,settings,hrm,shared}/ (§5)
    components/*.tsx          the app shell (sidebar, header, nav, logo, theme picker, …)
    hooks/                    use-can, use-list-filters, use-form-dialog, use-appearance, …
    layouts/                  app, auth, settings
    lib/                      utils (cn), daisy-class-groups, themes, echo, enums (GENERATED)
    pages/                    one file per Inertia page, path mirrors the URL (§6.4)
    types/                    index.ts re-exports auth, admin, hrm, imports, navigation, training, ui
routes/
  web.php                     `/`, dashboard, notifications; requires admin.php, settings.php
  admin.php                   the Admin module
  settings.php                the user's own account settings
  training.php                the in-app manual (ungated)
  projects.php, accounting.php, hrm.php   the ERP modules (🧪 POC: an overview each; hrm.php also
                              departments, designations, employees and their imports)
  channels.php                private broadcast channels
tests/
  Feature/                    root: apparatus tests (Listable, EnumRegistry, PasswordPolicy, …)
  Feature/Admin/ Auth/ Settings/ Training/ ProjectManagement/ Accounting/ Hrm/
  Feature/Imports/            the import engine (§9.13), proved against Fixtures/Imports/
  Fixtures/Imports/           stand-in importers (model, blueprint, controller, routes) for those tests
  Browser/                    Pest browser tests (§13.2); Browser/Training/ holds the help-dialog
                              test and the screenshot recipes
  Pest.php, TestCase.php      safety guards and helpers
documentation/                module reference docs (§14)
.claude/                      settings.json (hooks), hooks/, skills/
.serena/                      Serena project config and memories
```

## 4. The organizing rule

**Layer first, module second** *(from compozit)*. There is no `app/Modules/`. Every layer folder
(`Http/Controllers`, `Http/Requests`, `Models`, `Services`, `Enums`, `Listeners`, `Mail`,
`Notifications`, `resources/js/pages`, `resources/js/components`, `database/seeders`,
`tests/Feature`) is split by module, **one level deep**:

```
app/Http/Controllers/Admin/UserController.php
app/Services/Admin/UserService.php
resources/js/pages/admin/users/index.tsx
tests/Feature/Admin/UserTest.php
```

Anything that belongs to no module sits **at the root of its layer**: `ListRequest`, `Listable`,
`FilterType`, `RecordStatus`, `Theme`, `HomeController`, `NotificationController`,
`CollapseNavGroupsOnLogin`. A file moves into a module folder only when exactly one module owns it.

**`Imports/` is the one infrastructure folder that looks like a module and is not** *(from compozit;
owner, 2026-10-28)*. The spreadsheet-import engine ([§9.13](#913-spreadsheet-imports)) serves every
module and belongs to none, but it is a dozen classes, so it gets an `Imports/` folder in each layer it
touches (`Services`, `Enums`, `Exceptions`, `Http/Controllers`, `Http/Requests`) instead of crowding
the roots. It has no route file, no permissions and no pages of its own: each importer's routes,
permission and blueprint live in the module that owns the table. Its two models sit at the root of
`Models/`, and its value objects live beside it in `Services/Imports/` rather than in a separate
DTO layer.

`App\Models\User` stays at the root of `Models/` because the auth stack, Fortify and spatie expect it
there; it is administered by the Admin module.

## 5. Module registry

| Module | Status | Namespace | Route file | Name prefix | URL prefix | Pages |
| --- | --- | --- | --- | --- | --- | --- |
| Platform (shell) | ✅ | root of each layer | `routes/web.php` | — | `/` | `pages/dashboard.tsx`, `pages/auth/*` |
| **Admin** | ✅ | `Admin` | `routes/admin.php` | `admin.` | `admin/` | `pages/admin/*` |
| **Settings** (own account) | ✅ | `Settings` | `routes/settings.php` | `profile.`, `security.`, `appearance.`, `user-password.`, `company-selection.` | `settings/` | `pages/settings/*` |
| **Training** (in-app manual) | ✅ | `Training` | `routes/training.php` | `training.` | `training/` | `pages/training/*` |
| **Projects** (Project Management) | 🧪 POC | `ProjectManagement` | `routes/projects.php` | `projects.` | `projects/` | `pages/projects/*` |
| **Accounting** (menu: Accounts) | 🧪 POC | `Accounting` | `routes/accounting.php` | `accounting.` | `accounting/` | `pages/accounting/*` |
| **HRM** | 🧪 POC | `Hrm` | `routes/hrm.php` | `hrm.` | `hrm/` | `pages/hrm/*` |

📋 **Planned** means the scope and names are agreed but **nothing exists on disk yet**. 🧪 **POC**
(owner, 2026-10-28) means the module has its route file, its `{module}.dashboard.view` permission,
an invokable `DashboardController` and one overview page, `pages/{module}/dashboard.tsx`, which shows
**sample data** from `components/{module}/sample-data.ts` under a "Preview" banner
(`components/shared/preview-banner.tsx`). The route, permission, role and controller stay; the page
body and the sample data are **throwaway**, replaced when the first real slice lands. At that point,
follow [§11](#11-adding-a-new-module) for the rest and switch the status to ✅.

**Admin** owns: users (create, edit, deactivate, soft delete, restore, force delete, set password,
assign roles, grant companies, avatar), roles (with the permission picker), the Company master
([§9.10](#910-multi-company)), the read-only permission catalogue, the
audit log viewer, the Application Settings hub (Mail Server, Quick Login, Branding, Import Limits tabs)
and Sent Mails. Full
reference: [`documentation/admin.md`](documentation/admin.md).

**Settings** is the signed-in user's own account — profile (photo, name, phone, username, email),
password, theme and the selected company (the header switcher, [§9.10](#910-multi-company)). It is not a place for application configuration; that is Admin → Application
Settings. Reference: [`documentation/settings.md`](documentation/settings.md).

**Training** is the in-app manual: one guide per screen in English and Bangla, read at `/training`,
`/training/{key}` and in the header's **?** dialog. It serves every module and belongs to none, so it
is its own module (owner, 2026-10-27, ported from compozit). It owns no models, migrations or
permissions — **any signed-in user reads any guide**, including the guide to a screen they cannot open
yet. Guides are **TSX files in git**, not database rows (both languages are required props, so a
one-language guide fails the type check); the registry is `config/training.php`; screenshots are
captured from invented data by `composer training:screens`. **Every GET screen must be registered**
(`pending` is enough) or `TrainingGapsTest` fails the suite. Reference:
[`documentation/training.md`](documentation/training.md).

### The ERP modules (🧪 POC — an overview each, the rest planned)

**Build order: Company (Admin, [§9.10](#910-multi-company)) → Projects → Accounting → HRM**
(owner, 2026-10-28). Projects comes first, so two small slices of the later modules ship with it:
**HRM-lite** (Employees, Departments, Designations) and **Accounting → Customers**.

**Projects** runs client projects from contract to billing request: projects for a customer,
milestones, tasks, members (Employees, each with a per-contract billing rate), approved timesheets,
and budget against actual. It raises billing requests; it never issues invoices. Namespace
`ProjectManagement`, prefix `projects.` so permission names stay short. Reference:
[`documentation/projects.md`](documentation/projects.md).

**Accounting** keeps each company's double-entry ledger: chart of accounts, fiscal years (default
July–June), BDT plus foreign-currency vouchers with approval, and statements. It also owns
**customers and sales invoices**, so the ERP has one customer list. It is named *Accounting*, not
*Accounts*, because "account" already means a sign-in account here; the menu may still say Accounts.
Reference: [`documentation/accounting.md`](documentation/accounting.md).

**HRM** holds each company's workforce: org structure, employees, shifts, attendance and leave
(phase 1), payroll under the Bangladesh Labour Act (phase 2), and recruitment, appraisal and
separation (phase 3). **An Employee may be an application user** (owner, 2026-10-29, reversing the
2026-10-28 rule that creating an employee never creates an account): `employees.user_id` is an
optional, unique link, and ticking *Application user* on the form (`hrm.employees.create-account`)
makes `EmployeeService` open a sign-in account through `UserService` — username the lowercased
employee ID, no role, the employee's company granted, a temporary password that must be changed at
first sign-in (§9.2). There is no stored flag: an application user is a linked account that is
active. The account follows the employee (details copied on every save; closed, never deleted, when
unticked, deactivated or deleted); a User still need not be an employee, and an import never opens
an account. The employee's monthly salary sits behind `hrm.employees.view-compensation`.
**Built so far: Departments** (`hrm/departments`, with import) — the first company-scoped master
table — **Designations** (`hrm/designations`, with import), the first group-wide one (§9.10), and
**Employees** (`hrm/employees`, with import), company-scoped with an employee ID and email unique
across the group. The module stays 🧪 while its overview still shows sample data; the sidebar group is already
a list of links, each behind its own `useCan`. Reference: [`documentation/hrm.md`](documentation/hrm.md).

## 6. Naming conventions

### 6.1 Backend classes

| Kind | Name | Place |
| --- | --- | --- |
| Controller | `{Resource}Controller`, singular | `Http/Controllers/{Module}/` |
| Form request | `{Resource}{Action}Request` (`UserStoreRequest`); a list's is `{Resource}IndexRequest extends ListRequest` | `Http/Requests/{Module}/` |
| Service | `{Resource}Service` — every write and its `*Blocker()` guards | `Services/{Module}/` |
| Model | singular | `Models/{Module}/` |
| Enum | singular, `implements IdentifiedEnum` (or `LabelledEnum`), registered in `EnumRegistry` | `Enums/` or `Enums/{Module}/` |
| Listener | `Record{Thing}`, `{Verb}{Thing}` | `Listeners/` or `Listeners/{Module}/` |
| Validation rules shared by requests | `{Topic}ValidationRules` trait | `Concerns/` |

**Exceptions to "one level deep":** `ListRequest`, `BulkRequest`, `HomeController` and `NotificationController` live
at the layer root because they belong to no module. **There are no Policy classes**: authorization is
route middleware plus service guards ([§9.1](#91-rbac-roles--permissions)) (owner, 2026-10-26).

Create files with `php artisan make:* --no-interaction`.

### 6.2 Routes and permissions

- One route file per module, required from `routes/web.php`; every route in it shares the module's
  name prefix and URL prefix.
- Resource routes use `Route::resource()->only([...])->middlewareFor([...], 'permission:...')`;
  extra verbs are named `{resource}.{verb}` (`admin.users.restore`, `admin.users.force-delete`).
- **Permission names are `{module}.{resource}.{action}`, kebab-case** — `admin.users.reset-password`.
  The format is enforced by `RbacTest`.
- URL segments are kebab-case plurals (`admin/audit-logs`, `admin/sent-mails`).

### 6.3 Database

- Tables are snake_case plurals; the audit table is `audit_logs` (not the package's `audits`).
- Every user-editable table gets `inserted_by` / `last_updated_by` (nullable FKs to `users`,
  `nullOnDelete`) stamped by `ActorObserver` ([§9.3](#93-audit-logging)).
- An active/inactive flag is `status char(1) default 'A'` cast to `RecordStatus`
  ([§9.3.1](#931-activeinactive-status)), never a boolean.
- Migrations stay flat in `database/migrations/`. The starter's `users` migration was edited in
  place while the project had no deployed data (owner, 2026-10-26); **from now on, schema changes are
  new migrations**.

### 6.4 Frontend pages

The page path mirrors the URL: `admin/users` → `pages/admin/users/index.tsx`. A single-record screen
with no collection is a flat file (`pages/admin/application-settings.tsx`). One page per list; create,
edit and every other action are dialogs on it ([§8.6](#86-every-list-is-paginated-sortable-and-filtered-per-column)).

### 6.5 Components

`components/ui/` holds primitives; `components/{module}/` holds what one module uses;
`components/shared/` holds what **two or more modules** use. **A component is promoted to `shared/`
when a second module imports it**, not when a second screen in the same module does *(from
compozit)*.

### 6.6 TypeScript types

`types/index.ts` re-exports one file per area (`auth.ts`, `admin.ts`, `hrm.ts`, `imports.ts`,
`navigation.ts`, `training.ts`, `ui.ts`).
Shared Inertia props are typed in `types/global.d.ts` (`InertiaConfig.sharedPageProps`).

## 7. Request lifecycle

| Step | Owns | Never does |
| --- | --- | --- |
| Route middleware | `auth`, `permission:` | business rules |
| Form request | validation, input normalisation (`prepareForValidation`) | authorization beyond the route |
| Controller | orchestration only — call a blocker, refuse with a toast, call the service, flash, `back()` | write models directly |
| Service | every write, its audit side-effects, and the `*Blocker()` guards | read the request |
| Model | casts, scopes, relations, `FILTERABLE`/`SORTABLE` | business rules |

Controllers stay short (~20 lines per action). A refusal is `Inertia::flash('toast', [...])` +
`back()`, never an exception ([§8.8](#88-toasts-carry-severity-and-they-clear-themselves)).

## 8. Frontend wiring

### 8.1 Layout resolution — `resources/js/app.tsx`

`auth/*` → `AuthLayout`; the exact list `settings/profile|security|appearance` →
`[AppLayout, SettingsLayout]`; everything else → `AppLayout`. A new account page joins by being added
to that list, deliberately.

### 8.2 Generated code — never edited

- **Wayfinder** writes `resources/js/actions/`, `routes/` and `wayfinder/` (git-ignored, regenerated
  by the Vite plugin and by `php artisan wayfinder:generate --with-form`). Import controller actions
  from `@/actions/App/Http/Controllers/...` and named routes from `@/routes/...`.
- **`php artisan enums:sync`** writes `resources/js/lib/enums.ts` from `EnumRegistry`
  ([§9.8](#98-the-enum-registry)). `EnumRegistryTest` fails when it drifts.

### 8.3 Navigation

`components/app-sidebar.tsx` builds one group per module (`Platform`, `Admin`, `Projects`, `Accounts`,
`HRM`); each link is included
only when `useCan('{module}.{resource}.view')` passes, and an empty group is not rendered. **Hiding a
link is convenience, not authorization.** The one exception is **Training** (Platform, under
Dashboard), which has no `useCan` because the manual is open to everyone. The header's **?**
(`TrainingHelpButton`, beside the bell in `app-sidebar-header.tsx`) is not a nav item; it renders only
when the `training` prop names a guide for the screen. Groups collapse; the state lives in the `sidebar_groups`
cookie (unencrypted, read server-side into `collapsedNavGroups`) and every group starts collapsed after
sign-in (`CollapseNavGroupsOnLogin`).

### 8.4 Inertia v3 notes

- Inertia v3 ships no Axios. A read that is not a page visit (live uniqueness check, record history,
  the bell's list) is a plain `fetch` with an `AbortController` and the
  `X-Requested-With: XMLHttpRequest` header — without it `StartSession` records the JSON URL as the
  "previous URL" and a later `back()` lands there.
- Forms use `<Form {...Controller.action.form()}>` with uncontrolled fields (`name`/`defaultValue`).
  A form with a file goes multipart automatically; Wayfinder spoofs `PUT` as `POST` + `_method`.

### 8.5 Selects are comboboxes

There is no native `<select>`: `components/ui/combobox.tsx` (downshift) is the only picker. A
disabled combobox renders no hidden input — **disabled means absent, not empty** *(from compozit)*.
Values are compared as strings.

### 8.6 Every list is paginated, sortable and filtered per column

- **Backend:** the model declares `FILTERABLE` (`array<literal-string, FilterType>`) and `SORTABLE`
  and uses `Concerns\Listable`; the request extends `ListRequest`. Wire format:
  `?filter[col]=&sort=&direction=&per_page=&page=`. Unknown sort or filter keys are validation errors;
  page sizes are an allow-list (10/25/50/100).
- **`LIKE` filters escape with an explicit `ESCAPE '!'`**, because SQLite has no default escape
  character and a backslash-escaped `%` matched every row there (found 2026-10-26).
- **Frontend:** `hooks/use-list-filters.ts` (400 ms debounce, reset to page 1, refresh on focus) with
  `components/shared/{list-toolbar,column-filter-row,sortable-header,pagination}.tsx`. The reference
  page is `pages/admin/users/index.tsx`.
- A list that reads newest first overrides `ListRequest::defaultDirection()`; core keys (`sort`,
  `direction`, `per_page`, `filter`) always win over a surface's extra `filterValues()`.
- **Bulk actions** *(owner, 2026-10-29; first on Companies and Departments)*: a tick-box column, a
  header box that ticks **every row on this page** — never "everything matching the filter" — and
  `components/shared/bulk-action-bar.tsx` with the action, confirmed through `ConfirmActionDialog`
  `fields` (hidden `ids[]`). `hooks/use-row-selection.ts` drops the selection on any page or filter
  change; its `selectable` callback keeps a row the action cannot take out of the selection. The
  server takes `Http\Requests\BulkRequest` (`ids`, at most one page), re-runs each row's guard and
  writes **row by row through the service**, so every row is audited; a refused row is reported in a
  `warning` toast, the rest still go. A restorable list offers bulk delete on Active and bulk
  permanent delete on Deleted, each behind its single-row permission. Routes `{x}/bulk` and
  `{x}/bulk/force` are declared before the resource.

### 8.7 Modals never light-dismiss; menus always do

`components/ui/dialog.tsx` (native `<dialog>`) refuses backdrop clicks and Escape; the X is always
shown. A closed dialog unmounts its children, so reopening re-seeds from props — Cancel really
cancels. Menus (`dropdown-menu.tsx`, the combobox) keep light dismiss *(from compozit)*.

### 8.8 Toasts carry severity, and they clear themselves

Every outcome is `Inertia::flash('toast', ['type' => ..., 'message' => ...])`, shown by
`hooks/use-flash-toast.ts`. `success` — it worked. `warning` — refused, and the actor can clear the
cause (a role still assigned to users). `error` — refused by a rule no work lifts (yourself, the last
super admin, the super-admin role). `info` — something the user did not ask for (a live push). All
dismiss after 5 s. Tests assert the type with `assertToast($response, 'warning')`.

### 8.9 (reserved — compozit's detail-page back navigation; not used here)

### 8.10 A form modal has three buttons, and each one means something

Every create/edit dialog wears `components/shared/form-dialog-footer.tsx`: **Cancel**, **Save & add
another** (create only — clears the form, stays open), **Save & close**. `hooks/use-form-dialog.ts`
supplies the remount key, the save intent and focus-on-first-error. A dialog that acts on one named
record (password, roles) has no "add another". Reference: `components/admin/user-form-dialog.tsx`.

### 8.11 Nested dialogs

A confirmation raised from inside another dialog closes its parent through `onSuccess`
(`ConfirmActionDialog`). A form and its read-only twin place grouped blocks with `fieldRowSpan()`.

### 8.12 A form dialog lays its fields out in columns

`@utility field-grid` (one column, two from `md`, each ≤ 24rem), `field-wide`, `field-span-2..4` in
`resources/css/app.css`. Two columns is a ceiling, not a target.

## 9. Cross-cutting concerns

### 9.1 RBAC (roles & permissions)

- **The catalogue is code**: `database/seeders/Admin/RolePermissionSeeder::CATALOGUE`. There is no UI
  to create, rename or delete a permission — one no code checks does nothing (owner, 2026-10-26).
  Adding one = catalogue entry + its sentence in `Services/Admin/PermissionDescriptions` (a test
  fails otherwise) + the check that uses it. On a deployed database, ship it in a migration that runs
  the seeder.
- **Roles are rules over the catalogue**: `super-admin` (everything), `admin` (`admin.` and
  `settings.` prefixes), `viewer` (every `.view`), and one business role per ERP module —
  `project-manager` (`projects.`), `accountant` (`accounting.`), `hr-officer` (`hrm.`) (owner,
  2026-10-28). `admin` is the system-administration role and gets no module prefix. Each seeded role
  has a demo account (`DemoUserSeeder`: `admin`, `viewer`, `pm`, `accountant`, `hr`) outside
  production. `SUPER_ADMIN_ONLY` keeps the audit log, mail
  settings, quick login and sent mails from being handed out by rule. `ROLE_EXCLUSIONS` takes named
  permissions back from one role after its rules match: `hr-officer` gets no
  `hrm.designations.{delete,restore,force-delete}`, because a designation is shared by every company
  and an officer works for one (owner, 2026-10-29).
- **`super-admin` passes every check** through `Gate::before` in `AppServiceProvider`. Routes and the
  UI check **permissions, never role names**.
- **Authorization = route `permission:` middleware + service `*Blocker()` guards. No Policy classes**
  (owner, 2026-10-26). The guards live in services because `Gate::before` would let a super admin
  through any gate, and "not yourself" / "not the last active super admin" must hold for them too.
- Shared to the frontend as `auth.permissions` (names, or `['*']` for a super admin); read with
  `useCan()` from `hooks/use-can.ts`.
- The morph map is **enforced** (`Relation::enforceMorphMap(AppServiceProvider::MORPH_MAP)`), so
  `model_has_roles.model_type` stores `user`, and adding an audited model means adding its alias.

### 9.2 Authentication & password policy

- Fortify, `resetPasswords()` only. **One `login` field** resolved by `Fortify::authenticateUsing()`
  in `FortifyServiceProvider`: email or username, case-insensitive (both stored lowercased by `User`
  mutators *and* normalised before validation in `ProfileValidationRules::prepareForValidation()`).
- A deactivated account is refused with its own message **only after the password matched**; trashed
  users never match. `EnsureUserIsActive` signs out a user deactivated mid-session.
- `Fortify::confirmPasswordsUsing()` is set because the default re-runs the guard with the virtual
  `login` field.
- **Password policy** is `config/auth.php → password_policy`, the same in every environment, applied
  by `AppServiceProvider::passwordPolicy()` and shared as `passwordPolicy` for the live checklist
  (`components/shared/password-policy-checklist.tsx`). Tests fake Have I Been Pwned suite-wide.
- `StampLastLogin` records `last_login_at` / `last_login_ip` quietly (no audit row, no actor stamp).
- **A password someone else set is temporary** (owner, 2026-10-29). `users.must_change_password` is
  set by `UserService::create()` (Admin → Users and an HRM employee's account) and by
  `resetPassword()` unless the actor reset their own; `EnsurePasswordIsChanged` (web group, after
  `EnsureUserIsActive`) then sends every request to Settings → Security, marking the session's
  password as confirmed so the page does not ask twice, until `SecurityController::update()` clears
  the flag. Sign-out, the bell and the help panel stay reachable.

### 9.3 Audit logging

- `owen-it/laravel-auditing`, implementation `Models\Admin\AuditLog`, table `audit_logs`, kept
  forever, not queued, **console writes are not audited** (`audit.console = false`).
- A model opts in with `implements App\Contracts\Auditable` + `use App\Concerns\Audited` and a morph
  alias. `Audited` stamps `actor_name` / `actor_username` at write time (so a deleted user's history
  stays readable) and always excludes `inserted_by` / `last_updated_by`.
- **Secrets are excluded per model** (`$auditExclude`): strict mode is off, so `$hidden` is not
  honoured. `User` excludes `password`, `remember_token`, and the sign-in stamps.
- **`Services\Admin\AuditRecorder` is the only writer** for what Eloquent cannot see: role and
  permission pivot changes, password resets, force deletes, mails, sign-in/out/failure
  (`Listeners/Admin/Record*`). `recordChange()` writes nothing for a no-op. Event names are
  `Enums\Admin\AuditEvent`.
- The viewer (Admin → Audit Log) is super-admin only by default; its model-type filter is the morph
  map.

### 9.3.1 Active/inactive status

`Enums\RecordStatus` (`'A'`/`'I'`) via `Concerns\HasStatus` (cast, `active()` / `inactive()` scopes,
`isActive()`). **Status is not deletion**: an inactive user cannot sign in but keeps their account;
deleting is a separate, soft, restorable verb.

### 9.4 Mail

- **The mail server lives in the database** (`mail_settings`, one row, password encrypted with
  APP_KEY), edited on Admin → Application Settings → Mail Server. The default mailer is `database`,
  built by `MailSettingService::transport()` on first use and purged after every save; "Log only"
  writes to `storage/logs/mail.log`. Outside tests `MAIL_MAILER` only seeds the row once.
- **Every sent mail is logged** (`sent_mails`) by `Listeners\Admin\RecordSentMail`; a `password` in
  the mail's view data is masked in the stored copy. Credential mails (`UserWelcomeMail`,
  `UserPasswordResetMail`) are queued on the **`mail`** queue and `ShouldBeEncrypted`.

### 9.5 Shared props

`HandleInertiaRequests::share()`: `name`, `branding` (always: `{logo, favicon}` URLs of the images
uploaded on Application Settings → Branding, `null` for the default; cached by
`BrandingService::urls()`), `auth` (always: `user`, `permissions`), `sidebarOpen`,
`collapsedNavGroups`, `theme`, `passwordPolicy`, `notifications` (always: `unread_count`),
`broadcasting` (Reverb key/host/port/scheme or `null`), `training` (always: `{key, title_en,
title_bn, status}` of the guide covering this route, or `null` — the guide body itself is fetched
from `training.panel` only when the **?** dialog opens). **A prop that affects rendering after a
partial reload must be `Inertia::always()`.**

**Bangla text** renders in **Nikosh**, self-hosted at `resources/fonts/nikosh.woff2` and second in
`--font-sans`, limited by `unicode-range` to the Bengali block so English stays Instrument Sans and a
page with no Bangla never downloads it (owner, 2026-10-27). The file is compozit's repaired copy: the
widely circulated TTF has a `name` table Chrome's font sanitiser rejects. **It is referenced by a
relative URL so Vite owns it** (served in dev, fingerprinted into `public/build`): compozit's
`public/fonts/` + absolute `/fonts/…` resolves against the Vite dev server, which 404s, and dev
silently fell back to the system font (found 2026-10-27).

**Theme:** `App\Enums\Theme` (35 daisyUI themes + `system`). `users.theme` is authoritative;
`HandleAppearance` re-plants the unencrypted `theme` cookie on every authenticated request so guest
screens on the same host match, and the root template sets `data-theme` before first paint.
`ThemeSync` repaints when the server disagrees.

### 9.6 Users: lifecycle and avatars

Admin-created only. Soft delete keeps the username and email reserved (unique indexes include trashed
rows); force delete records who the user was first and removes the avatar. Avatars are jpg/png/webp
≤ 2 MB on the `public` disk at `avatars/{uuid}.{ext}` (needs `php artisan storage:link`); the old
file is deleted on replace; no avatar → initials. `User::avatar_url` is appended.

**Uploaded images are raster only** (owner, 2026-10-28): avatars, the logo and the favicon accept no
SVG, because an SVG served from our `public` disk and opened directly runs its scripts on this domain.
The logo and favicon (Application Settings → Branding) live at `branding/{uuid}.{ext}` on the same
disk; `components/app-logo-icon.tsx` draws the uploaded logo, or the theme-following placeholder mark
when there is none, and `app.blade.php` links the uploaded favicon (falling back to the logo, then to
`public/favicon.*`).

### 9.7 Notifications & broadcasting

- Database notifications + the bell (`components/notifications/notification-bell.tsx`). A
  notification's data is generic — `title`, `body`, `url`, `tone` — so the bell needs nothing per type.
- Live push: Reverb, private channel `App.Models.User.{id}` (`routes/channels.php`). The Echo client
  (`lib/echo.ts`) is configured **at runtime from the `broadcasting` prop**, not from `VITE_*`, so
  moving Reverb needs no rebuild. A push reloads only `notifications` and shows a toast.
- `AccountChanged` tells a user when an administrator changes their roles or resets their password —
  best effort, never undoing the change.
- Development ports: Reverb listens on **6001** (`REVERB_SERVER_PORT`); 8080 is Laragon's Apache.

### 9.8 The enum registry

`App\Enums\EnumRegistry::CLASSES` lists every enum (hand-maintained); `EnumRegistryTest` scans
`app/Enums/` and fails on a missing one and on a stale `lib/enums.ts`. Run `php artisan enums:sync`
after changing an enum.

### 9.9 Development quick login

One-click sign-in buttons on `/login` for the seeded super admin and demo accounts. **Two locks:** the
switch on Application Settings → Quick Login *and* `APP_ENV` local/testing — production never renders
them (owner, 2026-10-26). The buttons submit the real login form.

### 9.10 Multi-company

*(owner, 2026-10-28; built 2026-10-29)* One installation serves one organization's companies — one or several.
**Every Projects, Accounting and HRM record carries its company**, and each company has its own
ledger, fiscal years and payroll. **The one exception is HRM → Designations**: a group-wide list
with no `company_id`, the same job titles for every company — the master-crud *global* shape
(restorable, unique across the table, no `BelongsToCompany`, no no-company panel; owner, 2026-10-29).
Add a second group-wide table only on the owner's word, and name it here. The company dimension is set up before the first ERP table so that
no table needs a later migration to add it. Reference:
[`documentation/admin.md` §10](documentation/admin.md#10-companies).

- **The Company master** is Admin's (`Models/Admin/Company`, Admin → Companies): name and short code,
  each unique across every company **soft-deleted ones included**, status, restorable. Deactivating
  is the normal way to retire one; `CompanyService::deletionBlocker()` refuses a delete while any
  record belongs to it ([§9.14](#914-master-data-and-deletion-guards)).
- **User ↔ company access** is the `company_user` pivot, granted on the user's Companies dialog
  (`admin.users.assign-companies`, audited as `companies-changed`; nobody re-grants their own). **A
  super admin needs no grant** and may work in any active company (owner, 2026-10-29).
- **One selected company at a time**, chosen in the header switcher and remembered in
  `users.selected_company_id` across sessions and devices. **`Services/Admin/SelectedCompany` is the
  only place that answers "which company?"**: it checks the remembered choice against the user's
  *usable* companies (active, undeleted, granted) on every request, clears it when it stops being
  usable, and picks the company automatically when exactly one is usable.
- **Server-side scope.** A company-scoped model uses `Concerns\BelongsToCompany`: a `company` global
  scope confines every read (lists, options, route-model binding — another company's row is a 404)
  whenever someone is signed in, and the selected company is stamped on create. `company_id` is never
  fillable and never a form field, so a browser-sent value is ignored, not validated. Console code has
  no actor and is not scoped. A guard that counts rows drops the scope
  (`withoutGlobalScope('company')` / `withoutGlobalScopes()`).
- **No company is a state, not an error.** An ERP page receives `company: null` and draws
  `components/hrm/no-company-panel.tsx` in place of its table (promoted to `shared/` when a second
  module needs it, §6.5), hiding create and import; Admin and Settings are unaffected. The switcher is
  `components/company-switcher.tsx`, in the app header.
- **Uniqueness is per company** and `Rule::unique` does not know it: the rule names the company
  itself. Master tables compare names and codes ignoring case and keep a deleted row's values
  reserved — `Concerns\MasterDataValidationRules` (owner, 2026-10-29).

`hrm/departments` is the reference company-scoped surface.

### 9.11 Approvals (planned)

*(owner, 2026-10-28)* Vouchers, invoices, timesheets, leave and payroll runs all need approval.
**Each module implements it the same way; there is no generic workflow engine:**

- A status enum: draft → submitted → approved or rejected (plus whatever states follow, such as
  posted or invoiced).
- An `{module}.{resource}.approve` permission, separate from `create`/`update`.
- A `*Blocker()` guard in the service: no approving your own record, and no editing once approved.
  It is a guard and not a gate, so it binds a super admin too ([§9.1](#91-rbac-roles--permissions)).

Multi-level approval (by amount, by department) is deferred until a module truly needs it.

### 9.12 Posting to the ledger (planned)

*(owner, 2026-10-28)* Only Accounting writes the ledger. A module whose work has a financial effect
(HRM payroll, later Projects cost) creates a **draft voucher** linked back to its source record. It
reaches the ledger only when an accountant approves it, and **the source record is locked once its
voucher is posted**. Billing works the same way in the other direction: Projects raises a *billing
request*, and Accounting turns it into a sales invoice.

### 9.13 Spreadsheet imports

*(ported from compozit; owner, 2026-10-28)* One module-less engine, `app/Services/Imports/`, turns an
uploaded `.xlsx`/`.xls` into rows of any master table: **upload → map → confirm**. A table opts in
with one `{Model}ImportBlueprint` (next to its service) registered in `ImportRegistry::BLUEPRINTS`, a
`{Model}ImportController extends Imports\SheetImportController` (a key, a list route, a three-line
`store()`), its own upload request, and its routes. No importer writes its own dialog: the list page
renders `components/shared/sheet-import-dialog.tsx` and `pending-import-banner.tsx`.

- **Every worksheet is read; row 1 of each tab is that tab's header.** A tab with no header row or no
  data is skipped with a warning; two identical headers, or a tab past the column cap, refuse the
  file. Each tab is **mapped separately** by the uploader, and the posted map is keyed by the
  workbook's own worksheet index (`map[index][field]`).
- **Confirming is all-or-nothing across every selected tab**, in one transaction: one bad row refuses
  the file and the import stays `pending` for another try. In-file duplicates are detected across tabs.
  The one exception is a blueprint implementing **`ImportRowWriter`**: it reviews first (a dry run),
  writes the good rows itself and reports the rest, with a downloadable workbook of the rejects.
- **Existing rows are found with one query, written with `upsert()`** in chunks — so the engine stamps
  `inserted_by` / `last_updated_by` itself and suppresses per-row audits (`withoutAuditing()`, declared
  on `App\Contracts\Auditable` for that reason). **The audit records the decision, not the rows**:
  `sheet-import` `created` (staged), `updated` (confirmed with its `column_map`, or cancelled).
- **Only the uploader confirms**; anyone with the importer's permission may cancel a pending import.
  Both guards live in `ImportConfirmRequest::authorize()` / the controller, and both answer 404.
- **Each importer has its own `{module}.{resource}.import` permission** — writing a thousand rows from
  a file is a different decision from creating one on a form. Routes, declared **before** the resource
  route so `import` is not captured as `{x}`: `POST {x}/import` (`.import.store`),
  `POST {x}/imports/{sheetImport}/confirm`, `DELETE {x}/imports/{sheetImport}`, `GET {x}/import/template`,
  and for a row writer `POST …/review` and `GET …/rejects`. The two GET routes are not screens and go
  in `config/training.php → ignore_routes`.
- **Company:** `sheet_imports.company_id` is nullable and deliberately has **no foreign key** — an
  import row is a log of what happened, and the engine's own tests stage against fixture companies
  that do not exist ([§9.10](#910-multi-company)). A blueprint answering `isCompanyScoped()` gets
  `company_id` in its uniqueness check, its written rows and its upsert key; its controller resolves
  the **selected** company in `companyIdFor()` — never from the request body — and staging refuses
  without one. A staged file keeps the company it was uploaded for, so its controller refuses review
  and confirm while the uploader works in another (`Hrm\DepartmentImportController`). A second
  scope (`scopeField()`) is generic.
- **Limits:** rows (workbook-wide), columns (per tab) and worksheets are read from the `import_limits`
  row (Admin → Application Settings → Import Limits, super-admin only), falling back to
  `config/sheet-import.php`; `max_file_size_kb` stays in config because PHP's upload limit bounds it.
  Uploads are kept on the `local` disk under `sheet-imports/`.
- **The reference importer is HRM → Departments** (`Services/Hrm/DepartmentImportBlueprint`, a
  company-scoped row writer that matches a row on its code **or** its name). The engine itself is
  proved in `tests/Feature/Imports/` against fixture importers in `tests/Fixtures/Imports/`.

### 9.14 Master data and deletion guards

*(owner, 2026-10-28)* A **master table** is reference data other records point at — companies,
departments, designations, customers, the chart of accounts. Each belongs to the module that owns its
subject ([§5](#5-module-registry)); there is no shared "master data" module. The `master-crud` skill
builds them.

**A foreign key into master data owes a guard, and it is always the same pair:**

- `restrictOnDelete()` on the foreign key, so the database refuses the delete whatever happens above
  it; **and**
- a clause in the referenced table's service `deletionBlocker(): ?string`, returning a sentence that
  names what still uses the row ("Used by 3 employees. Move them first.") — so a person is told which
  table to go and fix, as a `warning` toast, instead of being shown a constraint error.

The blocker counts with any company scope removed: whether a row is in use is a question about the
database, not about what the actor can see, and a hidden row blocks the delete just as hard. A child
that is *part of* its parent (a parent's code rows) cascades instead and owes no guard. A table that
nothing references yet ships **without** a blocker, and says so in its service's docblock — a blocker
that can only return `null` reads as a guard while guarding nothing. Blockers live in the service,
never a policy ([§9.1](#91-rbac-roles--permissions)).

## 10. Adding a feature to an existing module

1. Migration (`php artisan make:migration`), with `inserted_by`/`last_updated_by` and `status` where
   they apply ([§6.3](#63-database)).
2. Model in `Models/{Module}/`: `Audited` + `Auditable`, `HasStatus`, `Listable` + `FILTERABLE`/
   `SORTABLE`, `SoftDeletes` if restorable, `#[ObservedBy(ActorObserver::class)]`.
3. Morph alias in `AppServiceProvider::MORPH_MAP`.
4. Permissions in `RolePermissionSeeder::CATALOGUE` + sentences in `PermissionDescriptions`.
5. Enums registered in `EnumRegistry`; `php artisan enums:sync`.
6. Requests: `{Resource}IndexRequest extends ListRequest`, store/update requests.
7. Service with every write and its `*Blocker()` guards; pivot changes through `AuditRecorder`.
8. Controller: `index` renders the list with `sortable`/`filterable`/`perPageOptions`/`filters`;
   writes answer `back()` with a toast.
9. Routes in the module's route file with `permission:` middleware per action.
10. Page `pages/{module}/{resource}/index.tsx` + form dialog with `FormDialogFooter`.
11. Sidebar link gated by `useCan`.
12. Feature tests (access, list, each write, each guard, audit) — `tests/Feature/{Module}/`.
13. **Training entry**: every new GET screen gets an entry in `config/training.php`, `status =>
    'pending'` until its guide is written; a GET route that is not a screen (JSON, a file, a redirect)
    goes in `ignore_routes`. `TrainingGapsTest` fails the suite otherwise — except for a
    `Route::inertia()` screen, which the gate cannot see and must be remembered by hand. Write the
    guide and its screenshots in the same change or the next ([`documentation/training.md`](documentation/training.md) §7).
14. Update `documentation/{module}.md`; update this file only if a convention changed.

Two Claude skills walk this path (owner, 2026-10-28): **`master-crud`** for a master table in an ERP
module — shape, company scoping, spreadsheet import and the deletion guards of
[§9.14](#914-master-data-and-deletion-guards) — and **`admin-crud`** for an Admin system surface
(users, roles and the like).

## 11. Adding a new module

1. Pick the name (PascalCase namespace, kebab-case URL/permission prefix).
2. `routes/{module}.php`, required from `routes/web.php`, with the module prefix and name prefix.
3. Layer folders as files appear: `Http/Controllers/{Module}`, `Http/Requests/{Module}`,
   `Models/{Module}`, `Services/{Module}`, `pages/{module}`, `components/{module}`,
   `tests/Feature/{Module}`.
4. Its module help in `PermissionDescriptions::MODULES`, its role rules in `RolePermissionSeeder`.
5. A sidebar group in `app-sidebar.tsx`.
6. Each screen registered in `config/training.php` (`pending` is enough), with a section for the
   module in `training.sections` if it needs one.
7. A row in [§5](#5-module-registry) and the tree in [§3](#3-top-level-layout);
   `documentation/{module}.md`.

## 12. Keeping this file in sync

Edit this file **in the same change** when:

| Change | Section |
| --- | --- |
| Module added, renamed, removed, or its status changes | §5, §3, §11 |
| Top-level or module directory created/removed | §3 |
| Route file added, prefix or naming changed | §5, §6.2 |
| Naming/placement convention established or altered | §6 |
| Cross-cutting mechanism chosen or changed (RBAC, audit, mail, theme, notifications) | §9 |
| Architecture-shaping dependency added, removed or upgraded | §2 |
| Layout resolution in `app.tsx` changes | §8.1 |
| A UI standard changes (lists, dialogs, toasts, forms) | §8 |

A PostToolUse hook (`.claude/hooks/architecture-sync.mjs`) nudges when a structural file (route
files, `app.tsx`, `composer.json`/`package.json`, `bootstrap/app.php`/`providers.php`) changes while
this file has no pending edits, or when a file lands in a module folder this file never mentions.

## 13. Commands

| Command | Does |
| --- | --- |
| `composer setup` | install, key, migrate, build |
| `composer dev` → `php artisan dev` | server, default queue, logs, Vite, **mail queue**, scheduler, **Reverb** (registered in `AppServiceProvider::configureDevCommands()` / by Reverb) |
| `php artisan migrate:fresh --seed` | roles + permissions, the super admin (`SUPER_ADMIN_*`), demo `admin`/`viewer` outside production |
| `php artisan test --compact` | the Pest suite |
| `php artisan test -c phpunit.browser.xml` | browser tests (after `npm run build`) |
| `composer ci:check` | ESLint, Prettier, `tsc`, Pint, PHPStan L7, Pest |
| `php artisan enums:sync` | regenerate `lib/enums.ts` |
| `php artisan wayfinder:generate --with-form` | regenerate Wayfinder output |
| `php artisan training:gaps` | list every screen, guide, screenshot or translation the manual is missing (always exits 0; the gate is `TrainingGapsTest`) |
| `composer training:screens` | build, then recapture every guide screenshot (`TRAINING_SCREENS=1`, group `training-screens`) |

### 13.1 The suite never touches the development database

`tests/Pest.php` deletes a cached config/route file before boot (a cached config makes
`phpunit.xml` inert and the suite would `migrate:fresh` the MySQL database), and
`TestCase::createApplication()` refuses any connection that is not SQLite `:memory:` or a `.sqlite`
file under `database/` *(from compozit, where it happened twice)*.

### 13.2 Browser tests

`tests/Browser/` with `pestphp/pest-plugin-browser`, run through `phpunit.browser.xml` (file-backed
SQLite, file sessions). The suite holds only what a feature test cannot see — the shared standards,
each proved once on one surface: `FormDialogStandardTest` (§8.10, on roles), `ListRefreshTest` (§8.6
refresh triggers, on roles), `AppearanceThemeTest` (§9.5 theme persistence and `ThemeSync`),
`Training/TrainingHelpDialogTest` (the **?** dialog, the language toggle, the manual's search).
`Training/CaptureScreenshotsTest` is not a test of behaviour: the screenshot recipes, skipped unless
`TRAINING_SCREENS=1`. Its
Chromium must match the installed `playwright` package — `npx playwright install chromium` after an
npm update, or every test fails with "Playwright is outdated". The Playwright MCP server is used for
manual verification ([CLAUDE.md](CLAUDE.md)).

## 14. Module reference documentation

`documentation/` holds one reference per module (`admin.md`, `settings.md`, `training.md`, and the
planned `projects.md`, `accounting.md`, `hrm.md`, which hold agreed scope until code lands) plus
`frontend.md` (UI standards and the component catalogue) and `setup-and-deployment.md`. **This file
answers "where does it go and what is it called"; a module doc answers "what does it do and why".** A
module doc links here rather than repeating it.

## 15. Deployment

**TBD** — Nexora's deployment architecture will differ from compozit's `deploy/` supervisor and has
not been chosen (owner, 2026-10-26). The processes a deployment must run are listed in
[`documentation/setup-and-deployment.md`](documentation/setup-and-deployment.md).
