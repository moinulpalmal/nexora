# Satellite registrations

The edits outside the new surface's own files. Each is silently broken until somebody notices, so
none of them is optional.

---

## 1. `App\Providers\AppServiceProvider::MORPH_MAP`

One line per new model, kebab-case singular:

```php
'department' => Department::class,
```

`configureMorphMap()` calls `Relation::enforceMorphMap()`, which throws the first time an unmapped
model is audited — loud, but at runtime: a diff looks fine, and a model no test audits will not
surface it until someone uses the screen. The map is also the audit viewer's model-type filter, so a
missing entry hides the model's history there too. A ladder's child model needs one as well if it is
ever audited on its own.

## 2. Permissions — `RolePermissionSeeder` + `PermissionDescriptions`

```php
// database/seeders/Admin/RolePermissionSeeder.php → CATALOGUE
'hrm.departments' => ['view', 'create', 'update', 'delete', 'import'],
```

List only the actions you route (`import` with an importer; `restore`, `force-delete` when
restorable). Then one entry per permission in `App\Services\Admin\PermissionDescriptions::PERMISSIONS`
— `summary`, optional `note`, and `where` ("HRM → Departments"); read the existing entries for tone.
`PermissionDescriptionsTest` fails on a missing sentence and `RbacTest` on a malformed name. A
module's first permissions also need its `MODULES` help entry if it has none.

**Then decide the roles, deliberately.** `ROLES` are rules, not lists: `admin` takes the `admin.` and
`settings.` prefixes, `viewer` takes every `.view`, and each ERP role takes its module's prefix
(`project-manager` → `projects.`, `accountant` → `accounting.`, `hr-officer` → `hrm.`). So a new
`hrm.departments.*` reaches `hr-officer` entirely and `viewer` for `view` without any edit — usually
right; `SUPER_ADMIN_ONLY` is the escape hatch when it is not. Say in your summary which roles received
what and why.

Nexora has no deployed database yet, so the seeder is re-run rather than shipped in a grant migration
(`php artisan db:seed --class=Database\\Seeders\\Admin\\RolePermissionSeeder`). Once there is a
deployed install, §9.1 applies: ship new permissions in a migration that runs the seeder.

## 3. `resources/js/components/app-sidebar.tsx`

Import the generated index route and add the link to the module's group behind its own `useCan`:

```ts
import { index as departmentsIndex } from '@/routes/hrm/departments';

const canViewDepartments = useCan('hrm.departments.view');
// …in the module's links: { title: 'Departments', href: departmentsIndex(), icon: Building2, visible: canViewDepartments }
```

The admin group (`adminNavLinks` → `adminNavItems`) is the shape: a typed list of links with a
`visible` flag, filtered. An ERP group is still a single POC `Overview` link gated on
`{module}.dashboard.view` until its first real screen — turning it into that list is part of the first
screen's change (SKILL.md, Traps). Pick a Lucide icon not already used in the group.

## 4. `resources/js/types/{module}.ts`

```ts
export type DepartmentListItem = {
    id: number;
    // …exactly the keys the controller's ->through() projection emits
    /** `'A'` active, `'I'` retired from the pickers. Not a deletion. */
    status: string;
};

export type DepartmentFilters = ListFilters & {
    filter: { code?: string; name?: string; status?: string };
};
```

`ListItem` mirrors the projection, not the model — a joined name is a `string` with a comment saying it
is not sortable. Filter values are strings even for numeric columns: they come from the query string.
A module's first types file is added to `types/index.ts` (`export type * from './hrm';`) and to the
`ARCHITECTURE.md` §6.6 list.

## 5. `config/training.php`

Every new GET screen gets an entry — `pending` is enough until the guide is written:

```php
'hrm.departments' => [
    'route' => 'hrm.departments.index', 'guide' => 'hrm/departments', 'section' => 'hrm', 'sort' => 20,
    'title_en' => 'Departments', 'title_bn' => 'বিভাগ', 'status' => 'pending', 'shots' => [],
],
```

A module's first screen may need its `sections` entry. Every new GET route that is **not** a screen —
an importer's `hrm.departments.import.template`, a row writer's `…import.rejects`, a JSON `options`
endpoint — goes in `ignore_routes`. `TrainingGapsTest` fails on either omission, and also on an
`ignore_routes` line naming a route that does not exist. Writing the guide now is better
(`documentation/training.md` §7).

## 6. Referenced services' `deletionBlocker()`

For each FK the new table adds into existing master data, the referenced table's service gains a
clause, in sequence with any it already has, each with its own sentence:

```php
$departments = Department::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();

if ($departments > 0) {
    return trans_choice(
        '{1} One department belongs to this company. Deactivate the company instead, or delete that department first.'
        .'|[2,*] :count departments belong to this company. Deactivate the company instead, or delete them first.',
        $departments,
        ['count' => $departments],
    );
}
```

Count without the company scope — a hidden row blocks the delete just as hard (§9.14). The matching
controller already turns a non-null blocker into a `warning` toast; if it does not, that is part of
the change. **These files are outside the feature: name which ones need the clause and ask before
editing them.**

## 7. `App\Services\Imports\ImportRegistry`

One line when import is on — `'hrm.departments' => DepartmentImportBlueprint::class`. See
`references/import.md`.

## 8. Database and generated code

```bash
php artisan migrate && php artisan migrate:status
php artisan enums:sync                    # only if an enum was added or changed
php artisan wayfinder:generate --with-form
```

`migrate` is the step nothing else reminds you about: tests build SQLite from the migration files.
`--with-form` is required (§8.2).

## 9. `documentation/{module}.md`

The table's section: what it is, what the columns mean, uniqueness, who may do what, import, and what
references it. Planned scope in the module doc becomes built fact; edit the planned text in place.

## 10. `ARCHITECTURE.md`

A new table that follows recorded conventions does not touch it. These do:

- **A 🧪 POC module's first real screen** — §5 status to ✅ (and its paragraph), §3 tree entries for
  the module's new layer folders. The architecture-sync hook flags a file in a module folder the map
  never mentions.
- A new statement about company scoping (§9.10), master data or deletion guards (§9.14), imports
  (§9.13), or a convention the surface establishes.

Edit **in place** with the reason; never append a note that contradicts a line above it. If the disk
contradicts the file, the disk is the fact — fix the file and say so. In your summary, say either that
you updated it or why the change did not need it.

## Not registered

**No seeder.** Master tables ship empty; rows arrive by import or by hand. If import was skipped, say
plainly that the table has no bulk-load path.
