# Spreadsheet import

A second, separate way to write the same table, gated by its own permission. The mapped-import engine
(`ARCHITECTURE.md` §9.13) does the work; an importer supplies a blueprint, a three-line controller,
one upload request, its routes and a call to the shared dialog.

**Read first**:

- `app/Services/Imports/ImportBlueprint.php` — the interface you implement (its docblocks are the spec)
- `app/Services/Imports/ImportRegistry.php` — where the key is registered
- `app/Http/Controllers/Imports/SheetImportController.php` — the base your controller extends
- `app/Http/Requests/Imports/ImportConfirmRequest.php` — shared; you do **not** write a confirm request
- `resources/js/components/shared/sheet-import-dialog.tsx`, `pending-import-banner.tsx`,
  `import-result-panel.tsx` — shared; you do **not** write a dialog
- **The current reference implementation: HRM → Departments** — `app/Services/Hrm/
  DepartmentImportBlueprint.php` (company-scoped, a row writer matching on code *or* name),
  `Http/Controllers/Hrm/DepartmentImportController.php` (selected company; refuses review/confirm
  from another company), `Http/Requests/Hrm/DepartmentImportRequest.php`, the routes in
  `routes/hrm.php`, the page `pages/hrm/departments/index.tsx`, and
  `tests/Feature/Hrm/DepartmentImportTest.php`. For a plain all-or-nothing importer the fixture
  `tests/Fixtures/Imports/WidgetImportBlueprint.php` is still the simplest shape.

---

## Why import is its own permission

`{module}.{resource}.import` is not an alias for `create`: writing a thousand rows from a file is a
different decision from creating one on a form. Add `'import'` to the resource's catalogue entry, with
its `PermissionDescriptions` sentence, and decide which roles get it.

## The blueprint

`app/Services/{Module}/<X>ImportBlueprint.php`, next to the service — the engine knows nothing about
your model, so the knowledge lives with the model.

- `key()` — `'{module}.{resource}'` (`'hrm.departments'`); `label()` — what the dialog calls it.
- `modelClass()` — must be an auditable model (it is, by the invariants).
- `isCompanyScoped()` — `true` for a company-scoped table (`references/company-scoped.md`), else
  `false`. `scopeField()` — a second equality scope column, usually `null`.
- `fields()` — `list<ImportField>`: key, label, `required`, `maxLength`, and `transform` — **pass the
  same normalising closure the form request applies**, so a value imported and the same value typed
  into the form land identically. Put that closure somewhere both can call (a static on the
  validation-rules trait or the blueprint), not in two copies.
- `uniqueField()` — what duplicate detection matches on; `guardedFields()` — any *second* unique
  column (without it, a collision there is a raw `QueryException` mid-upsert, a 500). If the table has
  two unique columns and you leave this empty, say why.
- `normaliseRow()` — cross-field defaulting; usually returns the row unchanged.
- `defaultAttributes()` — columns the sheet does not carry, typically `['status' => 'A']`: a source
  list carries no concept of "retired", so every imported row starts active.

The blueprint restates constraints the form request already declares, and that is not worth
"fixing": the request runs over `$this->input()` and cannot validate a sheet row. What keeps them
from drifting is a test that pins them equal (below).

**Opt-ins:** implement `ImportRowWriter` only when each row must go through the service (per-row
audit, side effects) and a partial import serves the uploader better than all-or-nothing — it gains a
review step, `confirmRules()` inputs and a rejects download. Implement `ImportRelatedWriter` when one
row also writes children (`references/child-table.md`).

## Registration

Add the key to `ImportRegistry::BLUEPRINTS`. That map is what the runner and the confirm request
resolve, and what `sheet_imports.import_key` refers to.

## Controller — extend the base, write almost nothing

```php
class XImportController extends SheetImportController
{
    public function importKey(): string { return '{module}.<x>'; }

    protected function indexRoute(): string { return '{module}.<x>.index'; }

    public function store(XImportRequest $request): RedirectResponse
    {
        return $this->stageUpload($request);
    }
}
```

Overrides, only when needed: `companyIdFor()` for a company-scoped table (the **selected** company,
resolved server-side — never a request field), `scopeIdFor()` for a `scopeField()`. `importKey()` is
public on purpose: `ImportConfirmRequest::authorize()` reads it to refuse another importer's staged
file with a 404 before validation runs. Do not move those guards into the controller.

## Upload request — the only request you write

```php
'file' => ['required', 'file', 'max:'.config('sheet-import.limits.max_file_size_kb'), 'mimes:xlsx,xls'],
```

plus a validated scope field if `scopeField()` needs one, with a message for every rule. The file cap
stays in config on purpose (PHP's upload limit bounds it); the row/column/sheet caps are on
Application Settings → Import Limits.

## Routes — declared above the resource

```php
Route::get('<x>/import/template', [XImportController::class, 'template'])
    ->middleware('permission:{module}.<x>.import')->name('<x>.import.template');
Route::post('<x>/import', [XImportController::class, 'store'])
    ->middleware('permission:{module}.<x>.import')->name('<x>.import.store');
Route::post('<x>/imports/{sheetImport}/confirm', [XImportController::class, 'confirm'])
    ->middleware('permission:{module}.<x>.import')->name('<x>.import.confirm');
Route::delete('<x>/imports/{sheetImport}', [XImportController::class, 'cancel'])
    ->middleware('permission:{module}.<x>.import')->name('<x>.import.cancel');
// ImportRowWriter only:
Route::post('<x>/imports/{sheetImport}/review', [XImportController::class, 'review'])
    ->middleware('permission:{module}.<x>.import')->name('<x>.import.review');
Route::get('<x>/imports/{sheetImport}/rejects', [XImportController::class, 'rejects'])
    ->middleware('permission:{module}.<x>.import')->name('<x>.import.rejects');
```

The review route **must** be named `*.import.review` — the confirm request skips the writer's
`confirmRules()` on it by name. Add `{module}.<x>.import.template` (and `.import.rejects`) to
`config/training.php → ignore_routes`: they are GET routes that are not screens.

## List page props — gate them

Build the import props only for someone who can import, so a read-only role pays for none of it:

```php
$canImport = (bool) $request->user()?->can('{module}.<x>.import');

'importFields'     => $canImport ? array_map(fn (ImportField $f) => $f->toArray(),
                          $this->importRegistry->resolve('{module}.<x>')->fields()) : [],
'duplicateActions' => $canImport ? DuplicateAction::options() : [],
'maxFileSizeKb'    => (int) config('sheet-import.limits.max_file_size_kb'),
'pendingImport'    => $canImport ? $this->imports->pendingFor($request->user(), '{module}.<x>') : null,
```

(`$this->imports` is `ImportRunner`.) A row writer's page also renders
`<ImportResultPanel rejectsUrl={(id) => XImportController.rejects.url(id)} testPrefix="…" />`,
which reads the flashed `importResult` itself.

## Dialog — you do not write one

```tsx
<SheetImportDialog
    resourceLabel="departments"
    uploadDescription={<>…the sheet's expected columns…</>}
    duplicateLabel="A row whose code already exists"
    submit={XImportController.store.form()}
    confirmSubmit={(id) => XImportController.confirm.form(id)}
    fields={importFields}
    duplicateActions={duplicateActions}
    maxFileSizeKb={maxFileSizeKb}
    pendingImport={pendingImport}
    open={importOpen}
    onOpenChange={setImportOpen}
    testPrefix="department"
/>

{canImport && pendingImport && !importOpen && (
    <PendingImportBanner
        pendingImport={pendingImport}
        cancelSubmit={XImportController.cancel.form(pendingImport.id)}
        onResume={() => setImportOpen(true)}
        testPrefix="department"
    />
)}
```

- A company-scoped page passes `unavailableReason` when there is no selected company, instead of
  offering an upload that cannot succeed.
- A row writer passes `reviewUrl={(id) => XImportController.review.url(id)}` and, for its
  `confirmRules()` inputs, `confirmFields`.
- A secondary scope is a `scopeFields` render prop holding its own state at the call site — never a
  new boolean flag on the shared dialog.
- Offer the template beside the Import button (`XImportController.template.url()`).
- The dialog sits outside the three-button standard (§8.10) on purpose: "add another" means nothing on
  a mapping step.

## Tests — `<X>ImportTest.php`

The engine (reading, multi-tab rules, duplicates across tabs, all-or-nothing, audit shape, limits) is
proved once in `tests/Feature/Imports/`; do not re-prove it. Cover what is specific to this importer:

- the permission gate on every import route (`assertForbidden` without `{module}.<x>.import`);
- a clean workbook stages with the suggested map you expect from your field labels;
- confirm writes rows with the right defaults and actor stamps;
- **the equivalence test**: a value imported and the same value typed into the form land identically
  (the transform and the request's normalisation agree), and an over-long value is refused the same way;
- a duplicate is skipped or updated as chosen — and, company-scoped, the same code in a second company
  is not a duplicate;
- for a row writer: review counts, a reject reported rather than dropped, the rejects download.

Build workbooks inline with `uploadedWorkbook(['Tab' => [[headers], [row], ...]])` from
`tests/Pest.php` — the sheet a test proves stays next to the assertion about it.
