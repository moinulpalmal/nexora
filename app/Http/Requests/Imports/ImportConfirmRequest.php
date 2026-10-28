<?php

namespace App\Http\Requests\Imports;

use App\Enums\Imports\DuplicateAction;
use App\Http\Controllers\Imports\SheetImportController;
use App\Models\SheetImport;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportRegistry;
use App\Services\Imports\ImportRowWriter;
use App\Services\Imports\WorkbookMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the confirmed column mapping — step two of the import, for every
 * blueprint.
 *
 * **One class for every importer.** It reads the import key off the
 * route-bound `SheetImport` rather than hardcoding one. Module-less for the
 * reason the engine is (ARCHITECTURE.md §4): it serves every module that
 * imports a spreadsheet and belongs to none of them.
 *
 * **The allow-lists are read from the blueprint, not hardcoded**, so this stays
 * correct when a blueprint's `fields()` change: `map` may only name a field the
 * blueprint declares, may only name a worksheet the upload actually staged, and
 * a column index may only point at a column *that tab* has. Each is a
 * validation error rather than a silent no-op, the same posture §8.6 takes for
 * a list's filter allow-lists.
 *
 * **Whether every *required* field was mapped is checked in the runner, not
 * here.** `WorkbookMap::validate()` is the one place that rule lives, so the
 * confirm route and any future direct caller of `ImportRunner::confirm()`
 * enforce it identically.
 */
class ImportConfirmRequest extends FormRequest
{
    /**
     * Both guards on confirming an import, run **before** validation.
     *
     * They cannot live in `SheetImportController::confirm()`: `rules()` resolves the blueprint
     * from the *staged row's* own key, so posting one importer's file to
     * another's endpoint fails field validation ("name is not a field this
     * import accepts") before any controller code runs — turning a deliberate
     * 404 into a 302 carrying errors that describe the wrong problem and, worse,
     * confirm the row exists. `authorize()` is the only hook that precedes
     * `rules()`, so a guard that must precede validation belongs here.
     *
     * **404, not 403**, for both — the same posture the rest of the application
     * takes towards a record the actor has no business knowing about.
     *
     * - *Only the uploader may confirm.* `SheetImport` is not itself scoped to
     *   one person, so this narrows it: a colleague confirming a mapping over
     *   data they never saw is not a decision anyone should be asked to make.
     * - *Only this importer's own files.* With one shared request class, this
     *   is the only thing tying a confirm route to its blueprint.
     */
    public function authorize(): bool
    {
        $sheetImport = $this->sheetImport();

        abort_unless($sheetImport instanceof SheetImport, 404);
        abort_unless($sheetImport->inserted_by === $this->user()?->id, 404);

        $controller = $this->route()?->getController();

        if ($controller instanceof SheetImportController) {
            abort_unless($sheetImport->import_key === $controller->importKey(), 404);
        }

        return true;
    }

    /**
     * The posted shape is `map[worksheetIndex][fieldKey] = columnIndex`.
     *
     * The per-tab column bound is what replaced
     * `count($sheetImport->staged_rows['headers'])`, an expression that had
     * exactly one answer because an upload had exactly one worksheet. Tabs
     * legitimately differ in width, so each gets its own `max:`.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'map' => ['required', 'array', 'min:1'],
            'map.*' => ['required', 'array'],
            'map.*.*' => ['nullable', 'integer', 'min:0'],
            'on_duplicate' => ['required', Rule::enum(DuplicateAction::class)],
        ];

        foreach ($this->stagedSheets() as $index => $sheet) {
            $columnCount = count($sheet['headers']);

            $rules["map.{$index}.*"] = ['nullable', 'integer', 'min:0', 'max:'.max($columnCount - 1, 0)];
        }

        return [...$rules, ...$this->writerRules()];
    }

    /**
     * An `ImportRowWriter`'s extra confirm inputs, already validated — an
     * initial password, a notify choice. Empty for every other blueprint, and
     * on review.
     *
     * @return array<string, mixed>
     */
    public function writerOptions(): array
    {
        $keys = array_keys($this->writerRules());

        return $keys === [] ? [] : $this->safe()->only(array_map(
            static fn (string $key): string => explode('.', $key)[0],
            $keys,
        ));
    }

    /**
     * {@see ImportRowWriter::confirmRules()} of the staged row's blueprint.
     *
     * **Not on the review route** (named `{resource}.import.review`): a dry run
     * writes nothing, so it must not ask for a password before the uploader
     * has seen what Import would do.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function writerRules(): array
    {
        $sheetImport = $this->sheetImport();

        if (! $sheetImport instanceof SheetImport || $this->routeIs('*.import.review')) {
            return [];
        }

        $blueprint = app(ImportRegistry::class)->resolve($sheetImport->import_key);

        return $blueprint instanceof ImportRowWriter ? $blueprint->confirmRules() : [];
    }

    /**
     * {@inheritDoc}
     *
     * Two checks that array rules cannot express, because Laravel's wildcard
     * rules validate *values* under a key rather than the keys themselves.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sheetImport = $this->sheetImport();

            if (! $sheetImport instanceof SheetImport) {
                return;
            }

            $blueprint = app(ImportRegistry::class)->resolve($sheetImport->import_key);
            $fieldKeys = array_map(fn (ImportField $field): string => $field->key, $blueprint->fields());
            $stagedIndexes = array_keys($this->stagedSheets());

            foreach ((array) $this->input('map', []) as $sheetIndex => $assignments) {
                if (! in_array((int) $sheetIndex, $stagedIndexes, true)) {
                    $validator->errors()->add('map', __('Worksheet :index is not part of this upload.', ['index' => $sheetIndex]));

                    continue;
                }

                foreach (array_keys((array) $assignments) as $key) {
                    if (! in_array($key, $fieldKeys, true)) {
                        $validator->errors()->add('map', __(':key is not a field this import accepts.', ['key' => $key]));
                    }
                }
            }
        });
    }

    /**
     * The uploader's chosen mapping, as the runner consumes it.
     */
    public function workbookMap(): WorkbookMap
    {
        return WorkbookMap::fromInput((array) $this->input('map', []));
    }

    public function duplicateAction(): DuplicateAction
    {
        return DuplicateAction::from((string) $this->input('on_duplicate'));
    }

    /**
     * The staged worksheets, keyed by the workbook's own worksheet index.
     *
     * @return array<int, array{index: int, name: string, headers: list<string>, rows: list<list<string|null>>}>
     */
    protected function stagedSheets(): array
    {
        $sheetImport = $this->sheetImport();

        if (! $sheetImport instanceof SheetImport) {
            return [];
        }

        $sheets = [];

        foreach ($sheetImport->staged_rows['sheets'] ?? [] as $sheet) {
            $sheets[$sheet['index']] = $sheet;
        }

        return $sheets;
    }

    protected function sheetImport(): ?SheetImport
    {
        $sheetImport = $this->route('sheetImport');

        return $sheetImport instanceof SheetImport ? $sheetImport : null;
    }
}
