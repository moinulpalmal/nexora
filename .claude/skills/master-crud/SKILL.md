---
name: master-crud
description: Build or complete a master-data CRUD surface in one of Nexora's ERP modules (Projects, Accounting, HRM, or Admin's Company master) — migration, model, requests, service with its deletion guards, controller, routes and permissions, Inertia list page with form dialogs, sidebar entry, training entry, factory, tests, the optional spreadsheet import, and every satellite registration. Covers the global, restorable, company-scoped and parent-plus-child (ladder) shapes. Invoke explicitly with /master-crud when adding a new reference/lookup table (departments, designations, customers, chart of accounts, companies…) or finishing a half-built one. For Admin system surfaces (users, roles and the like) use /admin-crud instead.
disable-model-invocation: true
---

# Master CRUD surface

Builds a paginated, sortable, per-column-filtered list of a **master table** — reference data other
records point at — with create, edit and delete in modals, an optional spreadsheet import, and the
deletion guards that keep references intact (`ARCHITECTURE.md` §9.14). Also completes a surface that
is half-built.

The reference implementations in the repository are the source of truth. Nothing in this skill is a
template to paste: every file you write should be traceable to a sibling you actually opened. When
this skill and the code disagree, the code wins and this skill is the bug — say so, and fix the skill.

**Use Serena** (`get_symbols_overview`, `find_symbol`, `replace_symbol_body`, `insert_after_symbol`,
`replace_content`) to read the siblings and edit PHP/TS; built-in tools only for non-code files. If
the Serena tools are not loaded, say so and ask the owner to restart rather than working around it.

---

## Before writing anything

Read, in this order:

1. `ARCHITECTURE.md` — §5 (module registry: the owner and its 🧪/✅ status), §9.14 (master data and
   the deletion-guard rule), §9.10 (multi-company), §9.13 (spreadsheet imports), §6.1–6.6 (naming),
   §8.5 (selects are comboboxes), §8.6 (lists), §8.7–8.8 (modals, toasts), §8.10 (three-button
   footer), §8.12 (`field-grid`), §9.1 (RBAC — no Policy classes), §9.3 / §9.3.1 (audit, status),
   §10 (the path this skill walks), and §11 if the module has no real screen yet.
2. `CLAUDE.md` — the operating contract and project rules. Check for `.ai/rules/` as `AGENTS.md`
   says; when it does not exist, say so once and move on rather than assuming rules you never read.
3. `documentation/{module}.md` — the agreed scope: the table may already be named there, with its
   columns and its permissions drafted.
4. **The nearest sibling surface**, chosen by shape in Step 1, end to end — controller, service,
   model, requests, page, dialog, test. You are going to mirror its structure and cite it in your
   docblocks, and you cannot honestly do either from memory.

---

## Step 1 — Owner and shape

**Who owns it?** Master data belongs to the module that owns its subject (§9.14) — there is no shared
"master data" or Settings module: departments and designations are HRM, customers are Accounting,
companies are Admin (§9.10). `Settings` in this codebase is the user's *own account*, never reference
data. If the subject straddles two modules, ask.

**What shape is it?** This decides the migration, the model traits, the validation, the controller
props and a third of the page. Propose one with your reasoning and let the owner confirm.

| Question | Shape | Sibling to read | Reference |
| --- | --- | --- | --- |
| The same fact for every company; retired by status | **Global** | `admin/roles` | `references/global.md` |
| As global, but a deleted row must be restorable (Active/Deleted tabs) | **Restorable** | `admin/users` | `references/restorable.md` |
| A row means nothing outside the company that keeps it (§9.10: every Projects, Accounting and HRM record) | **Company-scoped** | `hrm/departments` (restorable too) | `references/company-scoped.md` |
| A parent whose form edits a list of child rows with no identity of their own | **Parent + child ladder** | *none yet* | `references/child-table.md` |

The shapes combine: a company-scoped table can also be a ladder parent.

**The ladder shape has no sibling in Nexora yet.** For it, the reference file carries the rules —
proved in compozit, restated here — and the first table you build becomes the sibling. Before
building one, confirm the design with the owner, and record the new reference in this skill when you
are done.

**Company-scoped** follows `hrm/departments`: `Concerns\BelongsToCompany` on the model,
`Services/Admin/SelectedCompany` for "which company?", uniqueness scoped to the company in the
request, the no-company panel on the page (built 2026-10-29, §9.10).

Module paths follow from the owner (§4, layer first, module second):

| Layer | Path |
| --- | --- |
| Model | `app/Models/{Module}/` |
| Controller | `app/Http/Controllers/{Module}/` |
| Requests | `app/Http/Requests/{Module}/` |
| Service | `app/Services/{Module}/` |
| Shared validation rules | `app/Concerns/{Topic}ValidationRules.php` (flat) |
| Factory | `database/factories/{Module}/` |
| Routes | `routes/{module}.php` (`projects.php`, `accounting.php`, `hrm.php`, `admin.php`) |
| Page | `resources/js/pages/{module}/{resource}/index.tsx` |
| Dialog | `resources/js/components/{module}/{resource}-form-dialog.tsx` |
| Types | `resources/js/types/{module}.ts`, re-exported from `types/index.ts` |
| Tests | `tests/Feature/{Module}/` |
| Permissions | `{module}.{resource}.{view,create,update,delete}` (+ `import`, + `restore`/`force-delete`) |

Namespaces and prefixes are §5's: `ProjectManagement` / `projects.`, `Accounting` / `accounting.`,
`Hrm` / `hrm.`, `Admin` / `admin.`.

---

## Step 2 — Interview

Ask once, in a single pass, proposing an answer wherever you can reason to one. The owner confirms or
overrides.

1. **Table and model name**, and the owning module (from Step 1).
2. **Shape** (from Step 1), with your reasoning.
3. **Columns** — name, type, width, required. Match widths to whatever already stores the same fact.
   For each, propose:
   - **Filter type** (`FilterType`): a code read left to right → `Prefix` (stays index-seekable, §8.6);
     a name searched mid-word → `Contains`; an enum or foreign key shown as a combobox → `Equals`; a
     relation → `Scope`. A column with hundreds of distinct values is still `Prefix`, not a dropdown.
   - **Sortability.** Foreign keys normally stay out of `SORTABLE` — the lists do not sort on joined
     columns. Filter instead, and say so in a comment.
4. **Uniqueness** — which columns, and the scope: global, or per company. On a restorable table,
   whether retired rows keep their value reserved.
5. **Normalisation** — which columns are trimmed / uppercased / lowercased.
6. **References** — what this table points at (each FK owes the *referenced* service a guard), and
   what already points at it (§9.14).
7. **Import** — on by default for a master table (§9.13). Ask for the sheet columns and which field
   each maps to, and whether the source workbooks carry several tabs. Skip it only if the owner says
   so, and say in your summary that the table then has no bulk-load path.
8. **Seed data** — none. Master tables ship empty; rows arrive by import or by hand.

---

## Step 3 — Detect what already exists

Build only what is missing; a model without `Listable` and its constants is a normal state to find.

```
database/migrations/*_create_<table>_table.php
app/Models/<Module>/<X>.php                      traits, Fillable, FILTERABLE, SORTABLE, relations
app/Concerns/<X>ValidationRules.php              only when two requests share rules
app/Http/Requests/<Module>/<X>{Index,Store,Update}Request.php   (or one <X>Request, as roles)
app/Services/<Module>/<X>Service.php             writes + deletionBlocker()
app/Http/Controllers/<Module>/<X>Controller.php
routes/<module>.php
database/seeders/Admin/RolePermissionSeeder.php  CATALOGUE entry
app/Services/Admin/PermissionDescriptions.php    one sentence per permission
app/Providers/AppServiceProvider.php             MORPH_MAP alias
resources/js/types/<module>.ts                   <X>ListItem, <X>Filters
resources/js/pages/<module>/<x>/index.tsx
resources/js/components/<module>/<x>-form-dialog.tsx
resources/js/components/app-sidebar.tsx          nav entry
config/training.php                              guide registry entry
database/factories/<Module>/<X>Factory.php
tests/Feature/<Module>/<X>Test.php
# import, when on:
app/Services/<Module>/<X>ImportBlueprint.php, app/Http/Controllers/<Module>/<X>ImportController.php,
app/Http/Requests/<Module>/<X>ImportRequest.php, ImportRegistry entry, tests/Feature/<Module>/<X>ImportTest.php
```

---

## Step 4 — Build

Follow `ARCHITECTURE.md` §10. Generators, always with `--no-interaction` — hand-writing them is how
the wrong stub lands in the wrong namespace:

```bash
php artisan make:migration create_<table>_table --no-interaction
php artisan make:model <Module>/<X> -f --no-interaction
php artisan make:request <Module>/<X>IndexRequest --no-interaction
php artisan make:request <Module>/<X>StoreRequest --no-interaction     # or one <X>Request
php artisan make:request <Module>/<X>UpdateRequest --no-interaction
php artisan make:class Services/<Module>/<X>Service --no-interaction
php artisan make:controller <Module>/<X>Controller --no-interaction
php artisan make:test --pest <Module>/<X>Test --no-interaction
php artisan make:trait <X>ValidationRules --no-interaction             # rooted at app/Concerns already
# after routes:
php artisan wayfinder:generate --with-form
```

Never edit `resources/js/actions|routes|wayfinder/` or `resources/js/lib/enums.ts`. Never nest
`database/migrations/`; a change to an existing table is a new migration.

### Invariants — apply these without asking

They hold for every list surface in the repo, and one that breaks them reads as a bug:

- **Migration:** `inserted_by` / `last_updated_by` (nullable FKs to `users`, `nullOnDelete`);
  `status char(1) default 'A'` (§6.3); `softDeletes()` only for the restorable shape. Every FK into
  another master table is `restrictOnDelete()` (§9.14). Say in the docblock which indexes you
  deliberately did not add — a unique already *is* an index.
- **Model:** `#[ObservedBy(ActorObserver::class)]`, `#[Fillable([...])]`;
  `use Audited, HasFactory, HasStatus, Listable` (+ `SoftDeletes`), `implements Auditable`;
  `FILTERABLE` typed `array<literal-string, FilterType>` and `SORTABLE` on the model (a trait
  constant cannot be overridden); relations `insertedBy()` / `lastUpdatedBy()` where the page shows
  them.
- **Morph alias** in `AppServiceProvider::MORPH_MAP` (kebab-case singular). `enforceMorphMap()` throws
  on the first audit otherwise — at runtime, not in a diff.
- **Index request** extends `ListRequest`: `sortable()` / `filterable()` return the model's constants;
  `filterRules()` validates every `Equals` column so an out-of-range value is an error, not an empty
  list. A newest-first list overrides `defaultDirection()`.
- **Service** owns every write, in a transaction where it touches two tables, and the
  `deletionBlocker(): ?string` guards (below) — never a Policy: `Gate::before` lets a super admin
  through any gate, and these rules must bind them too (§9.1).
- **Controller** stays thin (§7): `index` renders the paginator projected through
  `->through(fn ($x) => [...])` — never the model — plus `filters`, `sortable`, `filterable`,
  `perPageOptions`, `statuses` and the combobox options; every write `back()` with
  `Inertia::flash('toast', [...])`. A blocker's refusal is a toast (`warning` if the actor can clear
  the cause, `error` if no work lifts it), not a validation error — `RoleController::destroy()`.
- **Routes:** `Route::resource(...)->only(['index', 'store', 'update', 'destroy'])` with
  `middlewareFor` — one `permission:` per action, never a role name. A route with a literal segment
  where `{x}` would sit (`<x>/import`, `<x>/options`) goes **before** the resource so it is not
  captured as `{x}`; `<x>/{x}/restore`-style routes cannot collide and follow it, as users' do.
- **A picker another table's form calls** (an `options` route) is gated on either table's `view` —
  `permission:{module}.{x}.view|{module}.{caller}.view` — so the caller's form works for someone not
  given the `{x}` screen.
- **Page** `pages/{module}/{x}/index.tsx`, mirroring `pages/admin/roles/index.tsx`: `useListFilters`
  + `ListToolbar` + `SortableHeader` + `ColumnFilterRow` + `Pagination`; buttons behind `useCan`;
  every action a dialog; an empty result is a row that says so.
- **Form dialog** mirroring `components/admin/role-form-dialog.tsx`: `useFormDialog` +
  `FormDialogFooter` (`addAnother` on create only), `field-grid`, `Combobox` for every choice, the
  first control `autoFocus`, `InputError` + `aria-invalid` on each field.
- **Types** in `types/{module}.ts`: `<X>ListItem` mirroring the `->through()` projection (not the
  model) and `<X>Filters extends ListFilters` (filter values are strings — they come from the query
  string). A module's first types file is also re-exported from `types/index.ts`.
- **Enums** the page uses go in `EnumRegistry`, then `php artisan enums:sync`.

### Traps that do not show up in a diff

- **Normalise in `prepareForValidation()`, never a model mutator.** A mutator runs *after*
  validation, so `ab-1` and `AB-1` both pass `Rule::unique` and then collide at the driver.
- **`Rule::unique` builds a raw query**: it ignores global scopes (so a company-scoped unique must say
  `->where('company_id', …)` itself) and soft deletes (add `->withoutTrashed()` only if a retired
  value should be reusable — users deliberately keep theirs reserved).
- **The `ColumnFilterRow` `cells` array is 1:1 with the `<th>` count**, including `{ type: 'none' }`
  for the actions column. A mismatch silently shifts every filter cell.
- **`php artisan migrate` is the one step the suite cannot catch** — tests build SQLite from the
  migration files, so the table exists in every test whether or not it exists in MySQL.
- **`npm run build` before the feature tests.** `app.blade.php` vites the page by name, so a test that
  renders a never-built page 500s with `Unable to locate file in Vite manifest` — a missing build that
  reads like a broken environment.
- **Console writes are not audited** (§9.3). Never create rows in the development database through
  tinker or SQL; verify through the screens or tests.
- **The first real screen of a 🧪 POC module** (§5) is a structural change: the sidebar group stops
  being one `Overview` link gated on `{module}.dashboard.view` and becomes a filtered list of links
  each behind its own `useCan` (mirror `adminNavLinks` in `app-sidebar.tsx`), the module's status in
  §5 moves to ✅, and the throwaway overview page/sample data are reviewed with the owner — they are
  not yours to delete unasked.

---

## Deletion guards — both directions

`ARCHITECTURE.md` §9.14: a reference into master data owes a guard, and it is always the same pair —
`restrictOnDelete()` so the database refuses, plus a `deletionBlocker()` sentence so a person is told
which table to go and fix instead of being shown a constraint error.

- **Outward.** Every FK your table adds creates an obligation on the *referenced* table's service: a
  clause in its `deletionBlocker()` counting your rows, with its own sentence (`trans_choice` for the
  count), the count taken **without** any company scope — it is a question about the database, not
  about what the actor can see. **Those files are outside your feature: name what needs the clause
  and ask before editing.**
- **Inward.** Your service ships *without* a blocker while nothing references the table, and the
  absence is documented with its reason in the service docblock. A blocker that can only return
  `null` is dead code no test can exercise, and it reads as a guard while guarding nothing.
- A child that is *part of* its parent (ladder rows) cascades and owes no guard.
- `RoleService::deletionBlocker()` + `RoleController::destroy()` are the shape: sentence in the
  service, toast type chosen in the controller.

---

## Registrations — the checklist

Each is silently broken until somebody notices. `references/registrations.md` shows what each edit
looks like.

- [ ] `AppServiceProvider::MORPH_MAP` alias.
- [ ] `RolePermissionSeeder::CATALOGUE` (only the actions you route) + `PermissionDescriptions`
      (one sentence each; `PermissionDescriptionsTest` fails otherwise) — and say which roles the
      prefix/suffix rules hand them to and whether that is right.
- [ ] `EnumRegistry` + `php artisan enums:sync`, if the page uses an enum.
- [ ] `app-sidebar.tsx` entry behind `useCan('{module}.{x}.view')`.
- [ ] `types/{module}.ts` (+ `types/index.ts` re-export for a module's first types file).
- [ ] `config/training.php` entry for the screen (`'status' => 'pending'`, `'shots' => []`), and each
      new non-screen GET route in `ignore_routes` (an importer's `{x}.import.template` and, for a row
      writer, `{x}.import.rejects`) — `TrainingGapsTest` fails otherwise. Better: write the guide now
      (`documentation/training.md` §7).
- [ ] The referenced services' `deletionBlocker()` clauses — after asking.
- [ ] `ImportRegistry::BLUEPRINTS` entry, when import is on.
- [ ] `php artisan migrate && php artisan migrate:status`;
      `php artisan db:seed --class=Database\\Seeders\\Admin\\RolePermissionSeeder`.
- [ ] `php artisan wayfinder:generate --with-form`.
- [ ] `documentation/{module}.md` — the table's section; move it from "planned" to built.
- [ ] `ARCHITECTURE.md` — §3 tree / §5 status for a module's first real screen; §9.14 or §9.10 only if
      the table makes a new statement about them. Edit **in place** with the reason; never append a
      note that contradicts a line above it.

No seeder.

---

## Docblock prose

The codebase's dominant convention, and the thing a generated file most obviously fails at. Every
file argues *why*, cites the `ARCHITECTURE.md` section behind a decision, and names the sibling it
mirrors.

- **Cite only what you actually read this session, and only things that ship with the repo.** A
  fabricated section number or invented sibling is worse than no comment — the next reader follows a
  dead reference. That includes this skill's own `references/*.md`: they brief *you*, not the
  codebase. Cite `ARCHITECTURE.md` and real source files only.
- **Write prose where a real decision was made**, and stay terse where there was no alternative. The
  decision points on a master table are reliably: the uniqueness scope, each filter type, every index
  you deliberately did not add, an absent `deletionBlocker()`, a column left out of `SORTABLE`, and
  anywhere the surface diverges from its sibling. Explaining an absence is as valuable as explaining
  a presence.

---

## Tests

`tests/Feature/{Module}/<X>Test.php`, modelled on `tests/Feature/Admin/RoleTest.php` (or
`UserTest.php` for the restorable shape). Helpers in `tests/Pest.php`: `userWithPermissions(...)`,
`superAdmin()`, `assertToast($response, $type)`, `workbookFixture([...])`, `uploadedWorkbook([...])`.

- Guests redirected; the list forbidden without `view`; each write forbidden without its own permission.
- The list renders the component and props (`assertInertia`), and filters and sorts on the declared keys.
- Validation: required fields, the uniqueness scope (including the case that must be *allowed* — the
  same value in a second company), normalisation.
- Each write succeeds with a `success` toast; each blocker refuses with its toast type — including the
  outward clause you added to a referenced service.
- Audit rows — `config(['audit.console' => true])` **after** the fixtures.
- The shared list engine is proved once in `tests/Feature/ListableTest.php`; do not re-test paging or
  filter mechanics.
- Import, when on: a second `<X>ImportTest.php` (see `references/import.md`).

---

## Completion

```bash
php artisan migrate && php artisan migrate:status
php artisan enums:sync                               # if an enum changed
php artisan wayfinder:generate --with-form
npm run build                                        # required — see Traps
php artisan test --compact --filter=<X>
php artisan test --compact --filter='PermissionDescriptions|Rbac|TrainingGaps|EnumRegistry'
npm run types:check && npm run lint
vendor/bin/pint --dirty --format agent
npm run format
```

Then report what you built, what you registered, anything left out and why (import skipped → no bulk
load path), which roles received the permissions and why, the outward blocker clauses you asked about,
and whether `ARCHITECTURE.md` needed an edit — say so either way. Ask the owner to run the full suite
(`php artisan test --compact` or `composer ci:check`).

**Finish by saying the surface is not yet verified in a browser.** Offer to drive Playwright against
`APP_URL` from `.env` (never hardcoded), signing in through the `login` field as `superadmin` /
`password`. Do not open the browser unprompted; `CLAUDE.md` §6 requires an explicit trigger.

---

## References

Read the one for your shape, plus `import.md` when import is on and `registrations.md` while doing the
checklist:

- `references/global.md` — the roles-shaped surface, file by file.
- `references/restorable.md` — what soft deletes add (the users surface).
- `references/company-scoped.md` — the §9.10 rules, until a sibling exists.
- `references/child-table.md` — the parent + child ladder, until a sibling exists.
- `references/import.md` — blueprint, controller, request, routes, gated props, dialog, tests.
- `references/registrations.md` — the satellite edits, with what each looks like.
