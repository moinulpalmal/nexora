<?php

namespace App\Services\Imports;

use App\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * What a target model tells the import engine about itself.
 *
 * The engine's one seam: it never knows what a department or a customer is —
 * only that some model has fields, a natural key, and maybe a company. A module
 * opts in by implementing this once, next to its service, named
 * `{Model}ImportBlueprint` (ARCHITECTURE.md §9.13).
 *
 * Register the blueprint with {@see ImportRegistry}; nothing else wires it in.
 */
interface ImportBlueprint
{
    /**
     * The key this blueprint is registered and looked up under — `hrm.departments`.
     *
     * Stored on `sheet_imports.import_key`. **Not a morph alias** — it identifies
     * a *definition*, not a model instance, so it stays stable even if the model
     * behind it is ever renamed.
     */
    public function key(): string;

    /**
     * What the import dialog calls the target — "Departments".
     */
    public function label(): string;

    /**
     * The target model. It must be auditable, like every user-editable model
     * (ARCHITECTURE.md §9.3): the runner suppresses its per-row audits around
     * the bulk write, which only an auditable model can do.
     *
     * @return class-string<Model&Auditable>
     */
    public function modelClass(): string;

    /**
     * Whether every imported row belongs to the uploader's selected company.
     *
     * **Pre-wired, and `false` for every blueprint until the Company master
     * exists** (ARCHITECTURE.md §9.10). When true, the controller must resolve a
     * company before staging — {@see ImportRunner::stage()} refuses without one —
     * and the runner adds `company_id` to the uniqueness check, the written row
     * and the `upsert()` conflict target. A blueprint answering `false` writes
     * rows with no company at all; there is no state where some rows carry one
     * and some do not.
     */
    public function isCompanyScoped(): bool;

    /**
     * An optional second equality scope, besides `company_id`, that bounds a
     * row's uniqueness and is written on every imported row.
     *
     * Return `null` and uniqueness is scoped by `company_id` alone (or nothing,
     * when {@see self::isCompanyScoped()} is `false`).
     *
     * Return a column name and {@see ImportRunner}:
     *  - reads its value from `sheet_imports.scope_id` (set at
     *    {@see ImportRunner::stage()} time from a field the controller validated);
     *  - filters the existing-key and guarded-field reads by it;
     *  - writes it onto every row;
     *  - uses `[scopeField, uniqueField]` as the `upsert()` conflict target **in
     *    place of** `['company_id', uniqueField]`, matching the target model's own
     *    unique index. `company_id` is still written when `isCompanyScoped()` is
     *    `true`; it is just not part of the key.
     */
    public function scopeField(): ?string;

    /**
     * The fields a sheet row may be mapped onto, in the order the mapping step
     * should offer them.
     *
     * @return list<ImportField>
     */
    public function fields(): array;

    /**
     * The field whose value identifies a row, for both in-file and
     * against-the-database duplicate detection.
     *
     * Must name a key present in {@see self::fields()}. Uniqueness is evaluated
     * *within one company* when {@see self::isCompanyScoped()} is true — the
     * same code recurring under a second company is not a collision.
     */
    public function uniqueField(): string;

    /**
     * Adjust a mapped row after every field's transform has run and before the
     * {@see self::uniqueField()} value is read, for cross-field defaulting a
     * per-field {@see ImportField} transform cannot express — filling a blank
     * key column from another column, say. Most blueprints return the row
     * unchanged.
     *
     * The per-field required and max-length checks in the runner run *before*
     * this, so a value this method produces is the blueprint's own
     * responsibility to keep within bounds.
     *
     * @param  array<string, mixed>  $values  field key => transformed value
     * @return array<string, mixed>
     */
    public function normaliseRow(array $values): array;

    /**
     * Field keys, besides {@see self::uniqueField()}, that carry a unique
     * constraint the engine must pre-check so a collision surfaces as a
     * named-row error rather than a raw driver exception mid-upsert.
     *
     * Empty unless the table has a second unique column; a blueprint whose
     * table has one and leaves this empty should say why.
     *
     * @return list<string>
     */
    public function guardedFields(): array;

    /**
     * Attributes every imported row gets that the sheet never supplies —
     * typically a default `status`.
     *
     * @return array<string, mixed>
     */
    public function defaultAttributes(): array;
}
