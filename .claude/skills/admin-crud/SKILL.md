---
name: admin-crud
description: Build or complete a CRUD surface in Nexora — migration, model, form requests, service with its guards, controller, routes and permissions, Inertia list page with form dialogs, sidebar entry, factory, tests and every satellite registration — matching the Admin users/roles surfaces. Invoke explicitly with /admin-crud when adding a new managed table (in Admin or any later module) or finishing a half-built one.
disable-model-invocation: true
---

# CRUD surface

Builds a paginated, sortable, per-column-filtered list with create, edit and delete in modals — the
shape `admin/users` and `admin/roles` have — or completes one that is half-built.

The reference implementations in the repository are the source of truth. Nothing here is a template
to paste: every file you write should be traceable to a sibling you actually opened. When this skill
and the code disagree, the code wins and this skill is the bug — say so, and fix the skill.

**Use Serena** (`get_symbols_overview`, `find_symbol`, `replace_symbol_body`,
`insert_after_symbol`) to read the siblings and edit PHP/TS; built-in tools only for non-code files.

---

## Before writing anything

Read, in this order:

1. `ARCHITECTURE.md` — §5 (find the owning module), §6.1–6.6 (naming), §8.5 (selects are
   comboboxes), §8.6 (every list is paginated, sortable, filtered per column), §8.7 (modals never
   light-dismiss), §8.8 (toast severity), §8.10 (three-button footer), §8.12 (`field-grid`), §9.1
   (RBAC — no Policy classes), §9.3 / §9.3.1 (audit, status), §10 (the path this skill walks).
2. `CLAUDE.md` — the operating contract and project rules.
3. **The nearest sibling**, end to end:
   - a plain managed table → `Admin\RoleController`, `Admin\RoleService`, `Models\Admin\Role`,
     `pages/admin/roles/index.tsx`, `components/admin/role-form-dialog.tsx`,
     `tests/Feature/Admin/RoleTest.php`;
   - a restorable table (Active/Deleted tabs) → the users surface: `Admin\UserController`,
     `Admin\UserService`, `Models\User`, `pages/admin/users/index.tsx`,
     `components/admin/user-form-dialog.tsx`, `tests/Feature/Admin/UserTest.php`;
   - a singleton setting (one row, one form) → `Admin\MailSettingController`,
     `Models\Admin\MailSetting` (`SINGLETON_ID`, `current()`), a tab in
     `pages/admin/application-settings.tsx` — **not** this skill's shape; see `documentation/admin.md` §8.

---

## Step 1 — Interview

Ask once, in a single pass, proposing an answer wherever you can reason to one:

1. **Module, table and model name.** Module paths follow §4 (layer first, module second):
   `Models/{Module}/`, `Http/Controllers/{Module}/`, `Http/Requests/{Module}/`, `Services/{Module}/`,
   `routes/{module}.php`, `pages/{module}/{resource}/index.tsx`, `components/{module}/`,
   `types/{module}.ts`, `tests/Feature/{Module}/`. A new module is §11 first.
2. **Columns** — name, type, length, required, unique (and whether uniqueness includes soft-deleted
   rows — on users it does, so a retired code stays reserved). For each, propose:
   - **Filter type** (`FilterType`): a code read left to right → `Prefix` (stays index-seekable); a
     name searched mid-word → `Contains`; an enum or FK shown as a combobox → `Equals`; a relation
     → `Scope` (the value is handed to a model scope of that name — `User`'s `role` filter uses
     spatie's `role()` scope).
   - **Sortability.** FKs are normally left out of `SORTABLE` (the lists do not sort on joined
     columns) — filter instead, and say so in a comment.
3. **Lifecycle.** Status only (`HasStatus`, retired by `I`), or also restorable (`SoftDeletes` →
   Active/Deleted tabs, restore and force-delete actions and permissions)?
4. **Permissions** — `{module}.{resource}.{view,create,update,delete}` plus `restore`/`force-delete`
   when restorable, plus any named action. Which roles get them: the `ROLES` rules are prefixes and
   suffixes, so `admin.` reaches `admin` and `.view` reaches `viewer` automatically — decide whether
   that is right, and use `SUPER_ADMIN_ONLY` if it is not.
5. **Guards** — what must be refused even for a super admin (in use elsewhere, the last of
   something, yourself)? Each becomes a `*Blocker()` in the service. None is a valid answer.
6. **Normalisation** — which columns are trimmed / lowercased / uppercased.

---

## Step 2 — Detect what already exists

Check each layer and build only what is missing:

```
database/migrations/*_create_<table>_table.php
app/Models/<Module>/<X>.php                      traits, Fillable, FILTERABLE, SORTABLE, casts
app/Http/Requests/<Module>/<X>{Index,Store,Update}Request.php
app/Services/<Module>/<X>Service.php
app/Http/Controllers/<Module>/<X>Controller.php
routes/<module>.php
database/seeders/Admin/RolePermissionSeeder.php  CATALOGUE entry
app/Services/Admin/PermissionDescriptions.php    one sentence per permission
app/Providers/AppServiceProvider.php             MORPH_MAP alias
resources/js/types/<module>.ts                   <X>ListItem, <X>Filters
resources/js/pages/<module>/<x>/index.tsx
resources/js/components/<module>/<x>-form-dialog.tsx
resources/js/components/app-sidebar.tsx          nav entry
config/training.php                              guide registry entry (pending until written)
database/factories/<Module>/<X>Factory.php
tests/Feature/<Module>/<X>Test.php
```

---

## Step 3 — Build

Follow `ARCHITECTURE.md` §10. Use the generators with `--no-interaction`:

```bash
php artisan make:migration create_<table>_table --no-interaction
php artisan make:model <Module>/<X> -f --no-interaction
php artisan make:request <Module>/<X>IndexRequest --no-interaction
php artisan make:request <Module>/<X>StoreRequest --no-interaction
php artisan make:request <Module>/<X>UpdateRequest --no-interaction
php artisan make:class Services/<Module>/<X>Service --no-interaction
php artisan make:controller <Module>/<X>Controller --no-interaction
php artisan make:test --pest <Module>/<X>Test --no-interaction
# after routes:
php artisan wayfinder:generate --with-form
```

### Invariants — apply without asking

- **Migration:** `inserted_by` / `last_updated_by` — nullable FKs to `users`, `nullOnDelete`;
  `status` `char(1)` default `'A'` where the row can be retired; `softDeletes()` when restorable.
  Flat in `database/migrations/`, never nested; a change to an existing table is a new migration.
- **Model:** `#[ObservedBy(ActorObserver::class)]`, `#[Fillable([...])]`;
  `use Audited, HasFactory, HasStatus, Listable` (+ `SoftDeletes`), `implements Auditable`;
  `FILTERABLE` typed `array<literal-string, FilterType>` and `SORTABLE`; exclude secrets from the
  audit (`$auditExclude`).
- **Morph alias** in `AppServiceProvider::MORPH_MAP` (kebab-case). `enforceMorphMap()` throws on the
  first audit otherwise — at runtime, not in a diff.
- **Permissions** in `RolePermissionSeeder::CATALOGUE` **and** `PermissionDescriptions::PERMISSIONS`
  (`PermissionDescriptionsTest` fails on a missing sentence). A new module also needs its
  `MODULES` help entry. On a deployed database, ship them with a migration that calls the seeder.
- **Enums** used by the page registered in `EnumRegistry`, then `php artisan enums:sync`; never edit
  `resources/js/lib/enums.ts`.
- **Index request** extends `ListRequest` and returns the model's `SORTABLE` / `FILTERABLE`; a
  newest-first list overrides `defaultDirection()`.
- **Service** owns every write, inside a transaction where it touches two tables. Self-protection is
  a `*Blocker(): ?string` returning the sentence to show — **never a Policy**: `Gate::before` lets a
  super admin through any gate, and these rules must hold for them too. Pivot/relation changes are
  recorded through `AuditRecorder` (they are not model events).
- **Controller** stays thin: `index` renders `{resource}` (projected through
  `->through(fn ($x) => [...])` — never the model), `filters`, `sortable`, `filterable`,
  `perPageOptions`, and the combobox options; every write `back()` with
  `Inertia::flash('toast', ['type' => ..., 'message' => ...])`. A refusal from a blocker is a toast
  (`warning` if the actor can clear the cause, `error` if no work lifts it), not a validation error.
- **Routes:** `Route::resource(...)->only(['index', 'store', 'update', 'destroy'])` with
  `middlewareFor` — one `permission:` per action, never a role name. Extra routes (`restore`,
  `force`, named actions) go **before** the resource so a literal segment is not captured as `{x}`.
- **Page** `pages/<module>/<x>/index.tsx`: `useListFilters` + `ListToolbar` + `SortableHeader` +
  `ColumnFilterRow` + `Pagination`; buttons behind `useCan`; every action a dialog. The
  `ColumnFilterRow` `cells` array is 1:1 with the `<th>` count, including `{ type: 'none' }` for the
  actions column — a mismatch shifts every cell silently.
- **Form dialog:** `useFormDialog` + `FormDialogFooter` (Cancel / Save & add another — create only /
  Save & close); `field-grid` layout; `Combobox` for every choice — no native `<select>`; the first
  control `autoFocus`; `InputError` under each field and `aria-invalid` on it.
- **Types** in `types/<module>.ts`: `<X>ListItem`, `<X>Filters extends ListFilters`, re-exported from
  `types/index.ts`.

### Traps

- **Normalise in `prepareForValidation()`, not a mutator** — a mutator runs after validation, so
  `JDoe` and `jdoe` both pass `Rule::unique` and then collide in the database.
- **`Rule::unique(...)` ignores soft deletes** unless you add `->withoutTrashed()` — decide which you
  want (users deliberately keep retired identifiers reserved).
- **`npm run build` before the feature tests.** `app.blade.php` vites the page by name, so a test that
  renders a never-built page 500s with `Unable to locate file in Vite manifest`.
- **`php artisan migrate` is the step the suite cannot catch:** tests build SQLite from the migration
  files, so the table exists in every test whether or not it exists in MySQL.
- **Console writes are not audited.** Do not create test data in the development database through
  tinker or SQL; verify through the screens.

---

## Step 4 — Tests

`tests/Feature/<Module>/<X>Test.php`, modelled on `RoleTest.php` / `UserTest.php`. Helpers from
`tests/Pest.php`: `userWithPermissions(...)`, `superAdmin()`, `assertToast($response, $type)`.

- Guests redirected; the list forbidden without `view`; each write forbidden without its permission.
- The list renders the component and props (`assertInertia`), filters and sorts on the declared keys.
- Validation: required fields, uniqueness, normalisation.
- Each write succeeds with a `success` toast; each blocker refuses with its toast type.
- Audit rows — turn auditing on with `config(['audit.console' => true])` **after** the fixtures.
- The shared list engine is proved once in `tests/Feature/ListableTest.php`; do not re-test it.

---

## Registrations checklist

- [ ] `MORPH_MAP` alias.
- [ ] `RolePermissionSeeder::CATALOGUE` + `PermissionDescriptions` (+ `MODULES` for a new module).
- [ ] `EnumRegistry` + `php artisan enums:sync` (if the page uses an enum).
- [ ] `app-sidebar.tsx` nav entry behind `useCan('<module>.<x>.view')`.
- [ ] `types/<module>.ts` and the `types/index.ts` re-export.
- [ ] `config/training.php` entry for the screen (`'status' => 'pending'`, `'shots' => []`), and any
      new non-screen GET route (JSON, file, redirect) in `ignore_routes` — `TrainingGapsTest` fails
      the suite otherwise. Better: write the guide and its screenshot recipe now
      (`documentation/training.md` §7).
- [ ] `php artisan migrate` and `php artisan db:seed --class=Database\\Seeders\\Admin\\RolePermissionSeeder`.
- [ ] `php artisan wayfinder:generate --with-form`.
- [ ] `documentation/<module>.md` — the surface's section.
- [ ] `ARCHITECTURE.md` — only if a convention changed or a module/route file was added (§12).

---

## Completion

```bash
php artisan migrate && php artisan migrate:status
php artisan wayfinder:generate --with-form
npm run build
php artisan test --compact --filter=<X>
npm run types:check && npm run lint
vendor/bin/pint --dirty --format agent
npm run format
```

Report what you built, what you registered, anything left out and why, and whether `ARCHITECTURE.md`
needed an edit (say so either way). Finish by saying the surface is **not yet verified in a browser**
and offer to drive Playwright against `APP_URL` from `.env`, signing in as `superadmin` / `password`
through the `login` field — do not open the browser unprompted.

## Docblocks

Every file here explains *why* and cites the `ARCHITECTURE.md` section behind a decision. Cite only
sections and siblings you actually read this session; never cite this skill's own files. Write prose
where a real decision was made (a filter type, a column left out of `SORTABLE`, an absent blocker),
and stay terse where there was no alternative.
