# Global shape

For a table whose rows are the same fact for every company — the Company master itself, a currency,
a unit of measure. No company owns a row, so nothing about the actor's selected company touches the
surface. Retired by status, never deleted softly.

**Read first**, end to end:

- `app/Http/Controllers/Admin/RoleController.php` — the list controller, `->through()` projection, the
  blocker → toast refusal in `destroy()`
- `app/Services/Admin/RoleService.php` — writes, and `deletionBlocker()` with its sentences
- `app/Http/Requests/Admin/RoleIndexRequest.php`, `RoleRequest.php` (one request for store and update)
- `resources/js/pages/admin/roles/index.tsx`, `resources/js/components/admin/role-form-dialog.tsx`
- `tests/Feature/Admin/RoleTest.php`

**Roles is not a perfect sibling, and you need to know where.** `Admin\Role` extends spatie's model:
it has no `status`, no `HasStatus`, no factory and no `ObservedBy` stamping of its own. Take the
*surface* from roles — controller, request, service, page, dialog, test — and take these from the
users surface instead:

- `HasStatus` + `status char(1)` and the `filter.status` rule:
  `UserIndexRequest::filterRules()` (`Rule::enum(RecordStatus::class)`).
- The `statuses` prop (`RecordStatus::options()`) and the status column/filter cell on the page.
- The factory shape (`database/factories/UserFactory.php`, `fake()`) with an `inactive()` state.

---

## Migration

```
id()
… the business columns
char('status', 1)->default('A')
foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete()
foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete()
timestamps()
unique('<code column>')
```

Every FK into another master table: `->constrained()->restrictOnDelete()` (§9.14). Say in the
docblock which indexes you did not add and why — the unique already is an index; `status` has two
values, too low in cardinality to earn one.

## Model

```php
#[ObservedBy(ActorObserver::class)]
#[Fillable([..., 'status'])]
class X extends Model implements Auditable
{
    /** @use HasFactory<XFactory> */
    use Audited, HasFactory, HasStatus, Listable;

    /** @var array<literal-string, FilterType> */
    public const array FILTERABLE = [...];

    /** @var list<string> */
    public const array SORTABLE = [...];
```

## Requests

`<X>IndexRequest extends ListRequest` — `sortable()`, `filterable()` from the model, `filterRules()`
for `filter.status` and every `Equals` column.

Writes: one `<X>Request` for both, as roles do, when the rules are identical apart from `ignore()`;
store + update requests sharing an `app/Concerns/<X>ValidationRules` trait when they genuinely differ.
Normalise in `prepareForValidation()`. The unique message says "already exists" — there is no scope
to name.

## Service

`create`, `update`, `delete`. A `deletionBlocker()` only once something references the table;
otherwise document its absence and the reason in the class docblock (§9.14). Point the blocker's
sentence at the alternative: deactivating retires a row from the pickers while leaving it available
to whatever already holds it.

## Controller

`index()` props: the paginator (projected through `->through()`), `statuses`, `sortable`,
`filterable`, `perPageOptions`, `filters`, plus any combobox options the dialog needs. `destroy()`
asks the blocker first and refuses with a toast, exactly as `RoleController::destroy()` does.

## Routes

```php
Route::resource('<x>', XController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middlewareFor(['index'], 'permission:{module}.<x>.view')
    ->middlewareFor(['store'], 'permission:{module}.<x>.create')
    ->middlewareFor(['update'], 'permission:{module}.<x>.update')
    ->middlewareFor(['destroy'], 'permission:{module}.<x>.delete');
```

Read `routes/admin.php`'s roles block for the exact form this codebase uses. Import routes, if any,
go **above** the resource.

## Page and dialog

Mirror the roles page: heading row with the create button (`useCan('{module}.<x>.create')`), the
toolbar, sortable headers, the filter row (cells 1:1 with `<th>`, `{ type: 'none' }` last), the
empty-state row, pagination, and `X.layout = { breadcrumbs: [...] }`. The dialog is one component for
create and edit; the caller passes `XController.store.form()` or `XController.update.form(id)`.

## When a global table gains a reference

This shape is the one most likely to be referenced later — the Company master by every ERP table.
When the referencing table lands, this service gains its `deletionBlocker()` clause (see SKILL.md,
"Deletion guards"), and the docblock that recorded the absence is rewritten in place.
