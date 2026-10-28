# Parent + child ladder

For a master record whose form edits a **list of child rows that have no identity of their own** — a
parent and its codes, a template and its steps, an account group and its lines. The children live in
their own table and are edited as a repeater ("ladder") inside the parent's modal; there is no
separate screen for them.

**There is no sibling in Nexora yet.** The pattern was proved in compozit (concerns with per-buyer
codes, TNA templates with steps) and is restated here. Before building the first one, confirm the
design with the owner; when it ships, **record it here and in SKILL.md's shape table as the sibling**.

Not every "has many" is a ladder. If the children have their own identity, their own lifecycle or
anything that references them, they are a separate master table with an FK to the parent (its own
surface, its own guard) — not a ladder. Ask when unsure.

---

## Child migration

```
id()
foreignId('<parent>_id')->constrained()->cascadeOnDelete()
… the child's columns (and any FK into other master data: restrictOnDelete)
char('status', 1)->default('A')            only if a child row can be retired on its own
inserted_by / last_updated_by / timestamps()
unique([...])                              whatever must resolve unambiguously
```

Three decisions to state in the docblock, because each has a plausible alternative:

- **The parent cascades.** A child is *part of* its parent, so it owes no deletion guard on that side
  (§9.14). Any *other* master table the child points at restricts, and that table's service gains a
  `deletionBlocker()` clause — ask before editing it.
- **The unique runs in the direction lookups need.** compozit's per-buyer codes were
  `unique(buyer_id, code)` and deliberately *not* `unique(parent_id, buyer_id)`: one buyer's code must
  name exactly one parent, while one parent may hold two codes under one buyer. Work out which
  direction your lookups resolve and constrain that one.
- **Codes are strings**, never cast to integers — a workbook hands them back as numbers, and a
  leading zero is significant.

No index beyond the constraints: the unique already is the lookup index, and `constrained()` indexes
the parent key.

## Validation

- `'<children>' => ['present', 'array']` — **`present`, not `required`**: a parent with no children
  yet is a legitimate state, not a broken form.
- Per-child rules on `'<children>.*.<field>'`.
- **Collisions in an `after()` validator**, because they need the whole submitted set at once. Report
  each as a **named field error** on `<children>.{index}.<field>` so it lands under the right input:
  the same key twice within the submitted set, and a key already held by a *different* parent
  (excluding the row being edited). Bail out early if the validator already has errors.
- On a company-scoped parent the children inherit its company; they never carry their own picker.

## Service — replace, don't merge

Both writes in `DB::transaction()`. On update, split the parent's own attributes out, then delete the
children and recreate them:

```php
$parent->update($this->ownAttributes($attributes));
$parent-><children>()->delete();
$this->sync<Children>($parent, $attributes);
```

Replacement rather than merging is deliberate: the form edits rows with **no stable identity**, so a
merge would need identity the UI does not have. The child changes are part of the parent's story, so
record them on the parent's audit trail through `AuditRecorder::recordChange()` (§9.3) — a bulk
delete-and-insert fires no per-row model events worth reading.

## Controller

Eager-load the children (and any name they display) and project them as an array inside each row of
`->through()`:

```php
'<children>' => $parent-><children>->map(fn ($child) => [
    'id' => $child->id,
    '<field>' => $child-><field>,
])->all(),
```

## Dialog — the ladder

A `<X>Ladder` sub-component inside `components/{module}/<x>-form-dialog.tsx`. Four things in it are
load-bearing:

- **A `transform` on the `<Form>`** that defaults the child array to `[]`. A repeater left empty must
  still submit an empty array rather than vanish from the payload — "none yet" is a state the form
  offers and must be submittable.
- **`key` is a stable identity, not the array index.** The inputs are uncontrolled, so keying by
  position makes React reuse a removed row's DOM node for the row that slides into its place. Keep a
  module-level counter and assign a key when a row is added.
- **`formKey` from `useFormDialog`** remounts the form between entries; the rows live in `useState`,
  and only a remount empties them — this is why the three-button contract's remount matters more here
  than on a flat form (§8.10).
- **Input names are `<children>[${index}][<field>]`**, and errors are read from
  `errors[`<children>.${index}.<field>`]` — bracket syntax going out, dot syntax coming back.

Nothing in the UI prevents a duplicate key; the server decides what is a collision. Add a hint line
under the ladder saying which way the constraint runs.

## Import

A ladder imports through `ImportRelatedWriter` (§9.13): the child cell is a **virtual field**
(`virtualFields()`), mapped like any other but never upserted onto the parent, and `afterWrite()`
writes the children inside the same transaction — throwing a `SheetImportException` there rolls the
whole import back.

## Tests

Beyond the flat shape: creating with an empty child array; children replaced, not merged, on update;
each collision producing a field error under the right index; children cascading when the parent is
deleted; and the referenced master table refusing deletion through its `deletionBlocker()`.
