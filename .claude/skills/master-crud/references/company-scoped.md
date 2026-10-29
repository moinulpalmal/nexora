# Company-scoped shape

For a table whose rows belong to exactly one company, and whose values legitimately recur in another
company — a department code, a customer code, an account in the chart. §9.10: **every Projects,
Accounting and HRM record carries its company**, so this is the default shape for an ERP table.

**The sibling is `hrm/departments`** (built 2026-10-29) — read it end to end; it wins over this
file. What it established, named after what ships:

- `Models/Admin/Company`, the `company_user` grants, `users.selected_company_id`, and
  **`Services/Admin/SelectedCompany`** — `current()`, `id()`, `usable($user)` — the only answer to
  "which company?" (§9.10).
- **`Concerns\BelongsToCompany`** on the model: the `company` global scope (signed-in requests only)
  and the stamp on create. `company_id` stays out of `#[Fillable]`.
- **`Concerns\MasterDataValidationRules`**: `normaliseName()` / `normaliseCode()` (also the import
  fields' transforms) and `uniqueIgnoringCase()`, which counts deleted rows and says "restore it
  instead" — the owner's duplicate rule for master tables (2026-10-29).
- `components/hrm/no-company-panel.tsx` for rule 5 below (promote it to `shared/` when a second
  module needs it).
- The test helper shape: `departmentActor($company, ...$permissions)` in `DepartmentTest.php`.

---

## The rules (from §9.10, and proved in compozit's buyer scoping)

1. **`company_id` comes from the selected company, on the server — never from the form.** The form has
   no company picker; the service stamps it on create. A browser-sent `company_id` is ignored, not
   validated: validating it would only prove the client chose *a* company it holds, while §9.10 says
   the user works in *one selected* company at a time.
2. **Reads are scoped on the server.** A global scope (or an equivalent the Company work establishes)
   restricts every query to the selected company, so a list, a route-model binding and an options
   endpoint cannot leak another company's rows. Route-model binding of another company's row is a 404.
3. **Uniqueness is per company, and `Rule::unique` does not know that.** It builds a raw query that
   ignores global scopes, so the rule must say it: `Rule::unique('<table>', '<code>')
   ->where('company_id', <selected company id>)->ignore($id)`. The database backs it with
   `unique(['company_id', '<code>'])`. The unique message says "already exists in this company" —
   the same value in another company is legal, and a message that hid that would read as a bug.
4. **Blocker counts bypass the scope.** Whether a row is in use is a question about the database, not
   about what the actor can see; a hidden row blocks the delete just as hard (§9.14).
5. **No selected company is a state, not an error.** A user may hold no company yet (§9.10: they can
   reach no ERP module). The page renders a panel saying so instead of the table — "no access" must
   never read as "no data" — and hides the create and import buttons.
6. **Import** uses the engine's pre-wired company support (§9.13): the blueprint answers
   `isCompanyScoped(): true`, the import controller overrides `companyIdFor()` to return the selected
   company (never a request field), and staging refuses without one.

## Migration

```
id()
foreignId('company_id')->constrained()->restrictOnDelete()
… the business columns
char('status', 1)->default('A')
inserted_by / last_updated_by / timestamps()
unique(['company_id', '<code>'])
```

`restrictOnDelete` because a row here is a *fact* about a company — and that FK puts a clause in the
Company service's `deletionBlocker()` (ask before editing it). The composite unique leads with
`company_id`, the column every scoped query filters on, so it doubles as that index; say so rather
than adding a second one.

## Model, list and tests

As `references/global.md`, plus: the company scope; `company_id` out of `FILTERABLE` (the list is
already one company) and out of the `->through()` projection; and in the tests, an actor helper that
grants the permission **and** selects a company — an actor missing either fails every test for the
wrong reason. Cover scoping both ways: the list and a route binding hide another company's rows, and a
create lands in the selected company whatever `company_id` the request body carries.
