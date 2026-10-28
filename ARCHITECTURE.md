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

Nexora is an **ERP for Palmal Group**, serving several group companies in one installation (owner,
2026-10-28). This repository holds its **foundation**: the application shell, authentication, roles and permissions, the audit trail, and
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
| Database | MySQL (development, `nexora`), in-memory SQLite (tests) | [§13.1](#131-the-suite-never-touches-the-development-database) |
| Build | Vite 8, ESLint 9, Prettier 3 | Standard Vite rather than the starter's vite-plus, to match compozit (owner, 2026-10-26) |
| Tests | Pest 5 (+ browser plugin), PHPStan level 7 (Larastan), Pint | [§13](#13-commands) |

## 3. Top-level layout

```
app/
  Actions/Fortify/            ResetUserPassword
  Concerns/                   Model & request traits: Audited, HasStatus, Listable,
                              ProvidesOptions, *ValidationRules, RoleAssignmentRules
  Console/Commands/           SyncEnums (`php artisan enums:sync`), TrainingGaps (`training:gaps`)
  Contracts/                  Auditable, IdentifiedEnum, LabelledEnum
  Enums/                      FilterType, RecordStatus, Theme, EnumRegistry (module-less)
  Enums/Admin/                AuditEvent, MailEncryption, MailTransport
  Exceptions/Admin/           MailNotConfiguredException
  Http/Controllers/           HomeController, NotificationController (module-less)
  Http/Controllers/Admin/     User, Role, Permission, AuditLog, ApplicationSetting,
                              MailSetting, QuickLoginSetting, SentMail
  Http/Controllers/Settings/  Profile, Security, Appearance (the user's own account)
  Http/Controllers/Training/  TrainingController (index, show, panel)
  Http/Middleware/            EnsureUserIsActive, HandleAppearance, HandleInertiaRequests
  Http/Requests/              ListRequest (module-less) + Admin/, Settings/
  Listeners/                  CollapseNavGroupsOnLogin, StampLastLogin (module-less)
  Listeners/Admin/            RecordLogin, RecordLogout, RecordFailedLogin, RecordSentMail
  Mail/Admin/                 UserWelcomeMail, UserPasswordResetMail, MailSettingTestMail
  Models/User.php             (the one model at the root — Laravel's auth convention)
  Models/Admin/               Role, Permission, AuditLog, MailSetting, QuickLoginSetting, SentMail
  Notifications/Admin/        AccountChanged
  Observers/                  ActorObserver
  Providers/                  AppServiceProvider, FortifyServiceProvider
  Services/Admin/             UserService, RoleService, PermissionService, PermissionDescriptions,
                              AuditRecorder, AuditLogService, MailSettingService,
                              QuickLoginService, UserCredentialMailer
  Services/Training/          TrainingRegistry (reads config/training.php), TrainingGapFinder
config/                       audit.php, permission.php, reverb.php, broadcasting.php,
                              training.php (the guide registry) (+ Laravel's)
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
    components/training/      the manual: vocabulary, guide loader, language toggle, the header's ?,
                              table of contents; guides/{general,admin,settings,shared}/ (§5)
    components/*.tsx          the app shell (sidebar, header, nav, logo, theme picker, …)
    hooks/                    use-can, use-list-filters, use-form-dialog, use-appearance, …
    layouts/                  app, auth, settings
    lib/                      utils (cn), daisy-class-groups, themes, echo, enums (GENERATED)
    pages/                    one file per Inertia page, path mirrors the URL (§6.4)
    types/                    index.ts re-exports auth, admin, navigation, ui
routes/
  web.php                     `/`, dashboard, notifications; requires admin.php, settings.php
  admin.php                   the Admin module
  settings.php                the user's own account settings
  training.php                the in-app manual (ungated)
  channels.php                private broadcast channels
tests/
  Feature/                    root: apparatus tests (Listable, EnumRegistry, PasswordPolicy, …)
  Feature/Admin/ Auth/ Settings/ Training/
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

`App\Models\User` stays at the root of `Models/` because the auth stack, Fortify and spatie expect it
there; it is administered by the Admin module.

## 5. Module registry

| Module | Status | Namespace | Route file | Name prefix | URL prefix | Pages |
| --- | --- | --- | --- | --- | --- | --- |
| Platform (shell) | ✅ | root of each layer | `routes/web.php` | — | `/` | `pages/dashboard.tsx`, `pages/auth/*` |
| **Admin** | ✅ | `Admin` | `routes/admin.php` | `admin.` | `admin/` | `pages/admin/*` |
| **Settings** (own account) | ✅ | `Settings` | `routes/settings.php` | `profile.`, `security.`, `appearance.`, `user-password.` | `settings/` | `pages/settings/*` |
| **Training** (in-app manual) | ✅ | `Training` | `routes/training.php` | `training.` | `training/` | `pages/training/*` |
| **Projects** (Project Management) | 📋 Planned | `ProjectManagement` | `routes/projects.php` | `projects.` | `projects/` | `pages/projects/*` |
| **Accounting** (menu: Accounts) | 📋 Planned | `Accounting` | `routes/accounting.php` | `accounting.` | `accounting/` | `pages/accounting/*` |
| **HRM** | 📋 Planned | `Hrm` | `routes/hrm.php` | `hrm.` | `hrm/` | `pages/hrm/*` |

📋 **Planned** means the scope and names are agreed but **nothing exists on disk yet**: no route file,
folder or permission. The names in the row are reserved. When the first slice lands, follow
[§11](#11-adding-a-new-module), add the folders to [§3](#3-top-level-layout), and switch the status to ✅.

**Admin** owns: users (create, edit, deactivate, soft delete, restore, force delete, set password,
assign roles, avatar), roles (with the permission picker), the read-only permission catalogue, the
audit log viewer, the Application Settings hub (Mail Server, Quick Login tabs) and Sent Mails. Full
reference: [`documentation/admin.md`](documentation/admin.md).

**Settings** is the signed-in user's own account — profile (photo, name, phone, username, email),
password and theme. It is not a place for application configuration; that is Admin → Application
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

### The ERP modules (planned)

**Build order: Company (Admin, [§9.10](#910-multi-company-planned)) → Projects → Accounting → HRM**
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
separation (phase 3). **An Employee is not a User**: it has an optional link to one, and creating an
employee never creates an account. The employee's **cost rate** is what Projects prices hours with.
Reference: [`documentation/hrm.md`](documentation/hrm.md).

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

**Exceptions to "one level deep":** `ListRequest`, `HomeController` and `NotificationController` live
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

`types/index.ts` re-exports one file per area (`auth.ts`, `admin.ts`, `navigation.ts`, `training.ts`,
`ui.ts`).
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

`components/app-sidebar.tsx` builds one group per module (`Platform`, `Admin`); each link is included
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
  `settings.` prefixes), `viewer` (every `.view`). `SUPER_ADMIN_ONLY` keeps the audit log, mail
  settings, quick login and sent mails from being handed out by rule.
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

`HandleInertiaRequests::share()`: `name`, `auth` (always: `user`, `permissions`), `sidebarOpen`,
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

### 9.10 Multi-company (planned)

*(owner, 2026-10-28; nothing built yet)* One installation serves several Palmal Group companies.
**Every Projects, Accounting and HRM record carries its company**, and each company has its own
ledger, fiscal years and payroll. Admin owns the Company master and **user ↔ company access**. A
user may hold several companies and works in **one selected company at a time** (a header
switcher). Every ERP query is scoped to the selected company **on the server**, so a company id sent
from the browser is never trusted on its own. The company dimension is set up before the first ERP
table so that no table needs a later migration to add it. Reference:
[`documentation/admin.md` §10](documentation/admin.md#10-planned-companies).

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

The `admin-crud` Claude skill (`.claude/skills/admin-crud`) walks this path.

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
