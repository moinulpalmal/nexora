<?php

namespace App\Services\Imports;

use App\Enums\Imports\DuplicateAction;
use App\Enums\Imports\ImportStatus;
use App\Exceptions\Imports\SheetImportException;
use App\Models\ImportLimit;
use App\Models\SheetImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The mapped-import engine's one entry point (ARCHITECTURE.md §4, §9.13).
 *
 * Two calls, matching the two-step dialog every consumer wears:
 *
 * 1. {@see self::stage()} — read the sheet, store it as a {@see SheetImport}
 *    row, suggest a mapping. Nothing is written to the target model yet.
 * 2. {@see self::confirm()} — apply the uploader's chosen mapping and
 *    duplicate decision, validate every row **in memory, with no queries**,
 *    and write once. All-or-nothing: any row that fails refuses the whole
 *    sheet and nothing lands.
 *
 * Everything else in `app/Services/Imports/` is reached only from here.
 */
class ImportRunner
{
    public function __construct(
        protected SheetReader $reader,
        protected ImportRegistry $registry,
    ) {}

    /**
     * Read an uploaded spreadsheet and stage it against a blueprint.
     *
     * `$companyId` is the uploader's selected company, required when the
     * blueprint {@see ImportBlueprint::isCompanyScoped()} and ignored otherwise.
     * `$scopeId` is the value for the blueprint's optional
     * {@see ImportBlueprint::scopeField()}, `null` when it has none. The caller
     * has already validated both.
     *
     * @throws SheetImportException when the file cannot be read at all, or a
     *                              company-scoped import has no company
     */
    public function stage(UploadedFile $file, string $importKey, ?int $companyId, ?int $scopeId = null): SheetImport
    {
        $blueprint = $this->registry->resolve($importKey);

        if ($blueprint->isCompanyScoped() && $companyId === null) {
            throw SheetImportException::noCompany();
        }

        $storedPath = $this->storeFile($file);
        $absolutePath = $storedPath === null
            ? $file->getRealPath()
            : Storage::disk($this->disk())->path($storedPath);

        try {
            $workbook = $this->reader->read(
                $absolutePath,
                $file->getClientOriginalName(),
                /*
                 * Read from the `import_limits` row, not config — the caps are
                 * administered on Application Settings → Import Limits
                 * (ARCHITECTURE.md §9.13). The model is read directly rather
                 * than through `Admin\ImportLimitService`, which owns the write
                 * path only: injecting a module's service into the module-less
                 * engine is the coupling §4 exists to prevent.
                 */
                ImportLimit::current()->limits(),
            );
        } catch (SheetImportException $exception) {
            $this->discard($storedPath);

            throw $exception;
        }

        /*
         * One preview per staged tab. `suggestMap()` runs per tab rather than
         * once for the workbook, because tabs legitimately spell the same field
         * differently — that is the reason each is mapped separately at all.
         */
        $previews = array_map(
            fn (array $sheet): ImportPreview => ImportPreview::fromSheet(
                $sheet,
                $this->reader->suggestMap($sheet['headers'], $blueprint->fields()),
                self::SAMPLE_ROWS,
            ),
            $workbook['sheets'],
        );

        $rowCount = array_sum(array_map(
            static fn (array $sheet): int => count($sheet['rows']),
            $workbook['sheets'],
        ));

        return SheetImport::query()->create([
            'import_key' => $importKey,
            'company_id' => $blueprint->isCompanyScoped() ? $companyId : null,
            'scope_id' => $scopeId,
            'source_file_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'detected_file_type' => $workbook['file_type'],
            'sheet_count' => count($workbook['sheets']),
            /*
             * Hashed from `$absolutePath`, not the PHP temp upload. Same bytes
             * either way, but `source_hash` describes the copy that was
             * *retained* — and the temp file is about to vanish, so hashing it
             * describes something nothing can read back.
             */
            'source_hash' => hash_file('sha256', $absolutePath),
            'status' => ImportStatus::Pending,
            /* The workbook's total across every staged tab. */
            'row_count' => $rowCount,
            'payload' => [
                'sheets' => array_map(static fn (ImportPreview $p): array => $p->toArray(), $previews),
                'skipped_sheets' => $workbook['skipped'],
                /* Sentences about the tabs that were auto-skipped. */
                'warnings' => $this->reader->warningsFor($workbook['skipped']),
            ],
            'staged_rows' => [
                /*
                 * Versioned (2 is the multi-worksheet shape this engine was
                 * ported from compozit at) so a later change of shape can be
                 * told apart from rows already staged. Anything reading this
                 * should check.
                 */
                'version' => 2,
                'sheets' => array_map(static fn (array $sheet): array => [
                    'index' => $sheet['index'],
                    'name' => $sheet['name'],
                    'headers' => $sheet['headers'],
                    'rows' => $sheet['rows'],
                ], $workbook['sheets']),
            ],
        ]);
    }

    /** Rows shown beside the mapping step, never the whole sheet. */
    private const int SAMPLE_ROWS = 5;

    /**
     * Apply the uploader's mapping and duplicate decision, and write.
     *
     * **All-or-nothing, and it now spans every selected tab.** Every row of
     * every chosen worksheet is validated before anything is written; one bad
     * row on tab 3 refuses tab 1's rows too, inside one transaction, and the
     * import stays `Pending` so the uploader can confirm again with a corrected
     * mapping — see `ImportStatus`'s own docblock for why there is no separate
     * `Failed` state. A partial import is a state someone has to reconcile, and
     * spanning tabs is the only way that promise survives the workbook becoming
     * plural.
     *
     * **The one exception is an {@see ImportRowWriter}**,
     * which takes {@see self::confirmRowByRow()} instead: bad rows are rejected
     * and reported, the rest are written by the blueprint itself.
     *
     * **The consequence that matters most is that in-file duplicate state is
     * shared across tabs.** Every tab upserts into the same table on the same
     * key, so the same code appearing on two tabs is a collision. Were the
     * duplicate maps rebuilt per tab, both rows would land and the later tab
     * would silently overwrite the earlier — no error, and nothing to notice.
     * `$seenAt` and the guarded-field state are therefore threaded through the
     * whole workbook rather than scoped to one call.
     *
     * `existingKeys()` and the guarded-field reads are hoisted **above** the tab
     * loop: one query for the workbook, not one per tab, preserving the "one
     * query, not one per row" property §9.13 states.
     *
     * **`ImportRelatedWriter::afterWrite()` runs last, inside the same
     * transaction as the upsert.** A blueprint that opts in gets one more
     * chance to refuse the sheet — throwing there rolls the upsert back too,
     * so a row whose problem only shows up once its sibling write is
     * attempted (an unknown child code in a cell, say) is still all-or-nothing.
     *
     * @param  array<string, mixed>  $options  an `ImportRowWriter`'s validated confirm inputs; ignored otherwise
     *
     * @throws SheetImportException
     */
    public function confirm(SheetImport $import, WorkbookMap $map, DuplicateAction $onDuplicate, array $options = []): ImportOutcome
    {
        $blueprint = $this->registry->resolve($import->import_key);

        [$mapped, $errors] = $this->mapWorkbook($import, $blueprint, $map);

        if ($blueprint instanceof ImportRowWriter) {
            return $this->confirmRowByRow($import, $blueprint, $map, $onDuplicate, $options, $mapped, $errors);
        }

        if ($errors !== []) {
            throw SheetImportException::invalidRows($errors);
        }

        $guardErrors = $this->guardedFieldClashes($blueprint, $import, $mapped);

        if ($guardErrors !== []) {
            throw SheetImportException::invalidRows($guardErrors);
        }

        $existing = $this->existingKeys($blueprint, $import);
        $actor = Auth::user();
        $actorId = $actor instanceof User ? $actor->id : null;

        $written = [];
        $toWrite = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($mapped as $row) {
            $isExisting = in_array($row['values'][$blueprint->uniqueField()], $existing, true);

            if ($isExisting && $onDuplicate === DuplicateAction::Skip) {
                $skipped++;

                continue;
            }

            /* The location rode along only to make errors nameable; the
               writer wants the row itself. `$written` keeps the whole row,
               location included, for `ImportRelatedWriter::afterWrite()` —
               the one caller besides `mapRows()` that can still name a sheet
               and line once something about the row turns out to be wrong. */
            $written[] = $row;
            $toWrite[] = $row['values'];
            $isExisting ? $updated++ : $created++;
        }

        $outcome = new ImportOutcome($created, $updated, $skipped);

        DB::transaction(function () use ($blueprint, $toWrite, $written, $actorId, $import): void {
            $this->write($blueprint, $toWrite, $actorId, $import);

            if ($blueprint instanceof ImportRelatedWriter) {
                $blueprint->afterWrite($written, $import);
            }
        });

        $this->markConfirmed($import, $map, $onDuplicate, $outcome);

        return $outcome;
    }

    /**
     * Dry-run a mapping for an {@see ImportRowWriter}: what Import would
     * create, update, skip and reject, and anything else worth knowing first
     * — without writing a row or changing the import.
     *
     * @throws SheetImportException when the mapping itself is unusable, or the blueprint is not a row writer
     */
    public function review(SheetImport $import, WorkbookMap $map, DuplicateAction $onDuplicate): ImportOutcome
    {
        $blueprint = $this->registry->resolve($import->import_key);

        if (! $blueprint instanceof ImportRowWriter) {
            throw new RuntimeException("The [{$import->import_key}] import has no review step.");
        }

        [$mapped, $errors] = $this->mapWorkbook($import, $blueprint, $map);
        [$clean, $rejected] = $this->splitRejected($mapped, $errors);

        $outcome = $blueprint->writeRows($clean, $onDuplicate, [], $import, dryRun: true);

        return $this->withRejected($outcome, $rejected);
    }

    /**
     * Validate the mapping and map every selected tab's rows — the half of
     * confirming shared by every blueprint and by {@see self::review()}.
     *
     * @return array{0: list<array{sheet: string, line: int, values: array<string, mixed>}>, 1: list<array{sheet: string, line: int, message: string}>}
     *
     * @throws SheetImportException
     */
    private function mapWorkbook(SheetImport $import, ImportBlueprint $blueprint, WorkbookMap $map): array
    {
        $sheets = $this->stagedSheets($import);

        $map->validate($blueprint->fields(), array_map(
            static fn (array $sheet): string => $sheet['name'],
            $sheets,
        ));

        $mapped = [];
        $errors = [];
        /* Shared across every tab — see confirm()'s docblock. */
        $seenAt = [];

        foreach ($map->sheetIndexes() as $sheetIndex) {
            [$sheetRows, $sheetErrors] = $this->mapRows(
                $sheets[$sheetIndex]['rows'],
                $sheets[$sheetIndex]['name'],
                $blueprint,
                $map->forSheet($sheetIndex),
                $seenAt,
            );

            $mapped = [...$mapped, ...$sheetRows];
            $errors = [...$errors, ...$sheetErrors];
        }

        return [$mapped, $errors];
    }

    /**
     * Confirm for an {@see ImportRowWriter}: reject the rows the per-field
     * checks failed, let the blueprint write the rest itself, and record both.
     *
     * No surrounding transaction — the blueprint writes each row on its own,
     * so one row failing never takes another with it, which is the point.
     *
     * @param  array<string, mixed>  $options
     * @param  list<array{sheet: string, line: int, values: array<string, mixed>}>  $mapped
     * @param  list<array{sheet: string, line: int, message: string}>  $errors
     */
    private function confirmRowByRow(
        SheetImport $import,
        ImportRowWriter $blueprint,
        WorkbookMap $map,
        DuplicateAction $onDuplicate,
        array $options,
        array $mapped,
        array $errors,
    ): ImportOutcome {
        [$clean, $rejected] = $this->splitRejected($mapped, $errors);

        $outcome = $this->withRejected(
            $blueprint->writeRows($clean, $onDuplicate, $options, $import, dryRun: false),
            $rejected,
        );

        $this->markConfirmed($import, $map, $onDuplicate, $outcome);

        return $outcome;
    }

    /**
     * Separate the rows with a per-field error from those without, folding
     * each failing row's messages into one rejection.
     *
     * @param  list<array{sheet: string, line: int, values: array<string, mixed>}>  $mapped
     * @param  list<array{sheet: string, line: int, message: string}>  $errors
     * @return array{0: list<array{sheet: string, line: int, values: array<string, mixed>}>, 1: list<array{sheet: string, line: int, message: string}>}
     */
    private function splitRejected(array $mapped, array $errors): array
    {
        $messages = [];

        foreach ($errors as $error) {
            $messages[$error['sheet']."\0".$error['line']][] = $error['message'];
        }

        $clean = [];
        $rejected = [];

        foreach ($mapped as $row) {
            $location = $row['sheet']."\0".$row['line'];

            if (isset($messages[$location])) {
                $rejected[] = ['sheet' => $row['sheet'], 'line' => $row['line'], 'message' => implode(' ', $messages[$location])];

                continue;
            }

            $clean[] = $row;
        }

        return [$clean, $rejected];
    }

    /**
     * The blueprint's outcome with the engine's own rejects added, in sheet order.
     *
     * @param  list<array{sheet: string, line: int, message: string}>  $rejected
     */
    private function withRejected(ImportOutcome $outcome, array $rejected): ImportOutcome
    {
        $all = [...$rejected, ...$outcome->rejected];

        $order = [];

        foreach ($all as $reject) {
            $order[$reject['sheet']] ??= count($order);
        }

        usort($all, static fn (array $a, array $b): int => [$order[$a['sheet']], $a['line']] <=> [$order[$b['sheet']], $b['line']]);

        return new ImportOutcome($outcome->created, $outcome->updated, $outcome->skipped, $all, $outcome->notes);
    }

    /**
     * Record a confirmed import's decision and counts — the `sheet-import`
     * `updated` audit is written from this save.
     */
    private function markConfirmed(SheetImport $import, WorkbookMap $map, DuplicateAction $onDuplicate, ImportOutcome $outcome): void
    {
        $import->forceFill([
            'status' => ImportStatus::Confirmed,
            'column_map' => $map->toArray(),
            'on_duplicate' => $onDuplicate->value,
            'created_count' => $outcome->created,
            'updated_count' => $outcome->updated,
            'skipped_count' => $outcome->skipped,
            'rejected_count' => count($outcome->rejected),
            'rejected_rows' => $outcome->rejected === [] ? null : $outcome->rejected,
        ])->save();
    }

    /**
     * Abandon a staged import that will never be mapped.
     *
     * The pending banner's "Cancel import" (ARCHITECTURE.md §9.13) — a terminal
     * human decision, so the row moves to {@see ImportStatus::Cancelled} rather
     * than being deleted, and `scopePending()` stops returning it so the banner
     * clears. `forceFill()->save()` rather than `upsert()` so `ActorObserver`
     * stamps `last_updated_by` and the package writes the `updated` audit that
     * records who cancelled. The staged rows and the retained workbook are left
     * untouched: `staged_rows` is the history, and the original file is kept
     * deliberately for its formatting and formulas, which the flattened rows
     * lose.
     */
    public function cancel(SheetImport $import): void
    {
        $import->forceFill(['status' => ImportStatus::Cancelled])->save();
    }

    /**
     * The uploader's own pending import for a blueprint, if there is one.
     *
     * **Only the uploader's**, so the dialog reopens for the person who chose
     * the file and for nobody else: a staged upload is one person's work in
     * progress.
     *
     * @return array<string, mixed>|null
     */
    public function pendingFor(?User $user, string $importKey): ?array
    {
        if ($user === null) {
            return null;
        }

        $import = SheetImport::query()
            ->pending()
            ->where('import_key', $importKey)
            ->where('inserted_by', $user->id)
            ->latest('id')
            ->first();

        if (! $import instanceof SheetImport) {
            return null;
        }

        /** @var array{sheets: list<array<string, mixed>>, skipped_sheets: list<array<string, mixed>>, warnings: list<string>} $payload */
        $payload = $import->payload;

        return [
            'id' => $import->id,
            'source_file_name' => $import->source_file_name,
            'row_count' => $import->row_count,
            /*
             * One entry per staged tab, each carrying its own headers, its own
             * suggested map and its own sample rows — the mapping step renders
             * a panel per entry. `warnings` stays a flat list of sentences
             * about the tabs that were *not* staged.
             */
            'sheets' => $payload['sheets'],
            'warnings' => $payload['warnings'],
        ];
    }

    /**
     * The staged worksheets, keyed by the workbook's own worksheet index.
     *
     * Keyed rather than listed because that index is what the posted map, the
     * stored `column_map` and every error message refer to, and the list's
     * positions do not match it once a tab has been skipped.
     *
     * @return array<int, array{index: int, name: string, headers: list<string>, rows: list<list<string|null>>}>
     */
    private function stagedSheets(SheetImport $import): array
    {
        /** @var array{version?: int, sheets?: list<array{index: int, name: string, headers: list<string>, rows: list<list<string|null>>}>} $staged */
        $staged = $import->staged_rows ?? [];

        $sheets = [];

        foreach ($staged['sheets'] ?? [] as $sheet) {
            $sheets[$sheet['index']] = $sheet;
        }

        return $sheets;
    }

    /**
     * Apply one tab's map, and each field's transform and validation, to its rows.
     *
     * **No queries.** Format validation (required, max length) is pure PHP
     * over the staged array, and in-file duplicates on the unique field are
     * caught in the same pass — the reason this can validate 3,725 rows in
     * milliseconds rather than minutes.
     *
     * **`$seenAt` is passed by reference and lives for the whole workbook**, not
     * this call. Every tab upserts into the same table on the same key, so a
     * value repeated on two tabs is a genuine collision; rebuilding the map per
     * tab would let both rows through and let the later tab silently overwrite
     * the earlier. Holding it here also means the refusal can name *both*
     * places — `"Plant B" row 4 (duplicates "Plant A" row 7)`.
     *
     * Each mapped row carries where it came from. That is not decoration: the
     * guarded-field pass used to recompute a line number from its own index
     * into the flattened `$mapped` list, which was already only accidentally
     * right and is flatly wrong once tabs are concatenated. Carrying the
     * location keeps the `+ 2` arithmetic in exactly one place — here.
     *
     * @param  list<list<string|null>>  $rows
     * @param  array<array-key, array{sheet: string, line: int}>  $seenAt
     * @return array{0: list<array{sheet: string, line: int, values: array<string, mixed>}>, 1: list<array{sheet: string, line: int, message: string}>}
     */
    private function mapRows(array $rows, string $sheetName, ImportBlueprint $blueprint, ColumnMap $map, array &$seenAt): array
    {
        $fields = $blueprint->fields();
        $uniqueField = $blueprint->uniqueField();

        $mapped = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            /* +1 for the header row, +1 for one-based counting. Tab-local:
               the Excel row on *this* tab, which is what the person holding
               the file needs in order to go and look at it. */
            $line = $index + 2;
            $values = [];

            foreach ($fields as $field) {
                $column = $map->columnFor($field->key);
                $raw = $column === null ? null : ($row[$column] ?? null);
                $value = $field->apply($raw);

                if ($field->required && ($value === null || $value === '')) {
                    $errors[] = ['sheet' => $sheetName, 'line' => $line, 'message' => __(':field is required.', ['field' => $field->label])];
                }

                if ($field->maxLength !== null && is_string($value) && mb_strlen($value) > $field->maxLength) {
                    $errors[] = ['sheet' => $sheetName, 'line' => $line, 'message' => __(':field is longer than :max characters.', ['field' => $field->label, 'max' => $field->maxLength])];
                }

                $values[$field->key] = $value;
            }

            // Cross-field defaulting the per-field transforms above cannot do —
            // a no-op for most blueprints.
            $values = $blueprint->normaliseRow($values);

            $key = $values[$uniqueField] ?? null;

            if ($key !== null) {
                if (isset($seenAt[$key])) {
                    $first = $seenAt[$key];
                    $errors[] = [
                        'sheet' => $sheetName,
                        'line' => $line,
                        'message' => __('duplicates ":sheet" row :first', ['sheet' => $first['sheet'], 'first' => $first['line']]),
                    ];
                } else {
                    $seenAt[$key] = ['sheet' => $sheetName, 'line' => $line];
                }
            }

            $mapped[] = ['sheet' => $sheetName, 'line' => $line, 'values' => $values];
        }

        return [$mapped, $errors];
    }

    /**
     * Apply the blueprint's scopes — `company_id`, and the optional
     * {@see ImportBlueprint::scopeField()} — to a query over the target model.
     *
     * Any company global scope on the target model is deliberately *not*
     * relied on here: these reads are questions about the database, and the
     * caller has already resolved the company the import writes to.
     *
     * @param  Builder<covariant Model>  $query
     */
    private function applyScopes(ImportBlueprint $blueprint, SheetImport $import, Builder $query): void
    {
        if ($blueprint->isCompanyScoped()) {
            $query->where('company_id', $import->company_id);
        }

        if ($blueprint->scopeField() !== null) {
            $query->where($blueprint->scopeField(), $import->scope_id);
        }
    }

    /**
     * Every value of the blueprint's unique field already on file — one
     * query, not one per row.
     *
     * @return list<mixed>
     */
    private function existingKeys(ImportBlueprint $blueprint, SheetImport $import): array
    {
        $modelClass = $blueprint->modelClass();
        $query = $modelClass::query();
        $this->applyScopes($blueprint, $import, $query);

        return array_values($query->pluck($blueprint->uniqueField())->all());
    }

    /**
     * Refuse the sheet when a {@see ImportBlueprint::guardedFields()} value
     * collides with a *different* row — one already on file under another key,
     * or an earlier row in this same sheet.
     *
     * A guarded field is a second unique column — a name unique alongside the
     * code the rows are keyed on. Without this pre-check the collision
     * would land as a raw `QueryException` from `upsert()` inside the write
     * transaction — a 500, not the named-row refusal every other import failure
     * gets. A row whose guarded value matches its *own* existing row by key is
     * fine: that is the ordinary skip/update case the caller handles next.
     *
     * **The `$seen` map spans the whole workbook**, for the reason
     * {@see self::mapRows()} gives about `$seenAt`: a guarded value repeated on
     * two tabs is the same collision as one repeated twice on one tab, because
     * both end up in the same unique index.
     *
     * @param  list<array{sheet: string, line: int, values: array<string, mixed>}>  $mapped
     * @return list<array{sheet: string, line: int, message: string}>
     */
    private function guardedFieldClashes(ImportBlueprint $blueprint, SheetImport $import, array $mapped): array
    {
        $guarded = $blueprint->guardedFields();

        if ($guarded === []) {
            return [];
        }

        $modelClass = $blueprint->modelClass();
        $key = $blueprint->uniqueField();
        $errors = [];

        foreach ($guarded as $field) {
            $query = $modelClass::query();
            $this->applyScopes($blueprint, $import, $query);
            /** @var array<string, mixed> $onFile guarded value => the key of the row that holds it */
            $onFile = $query->pluck($key, $field)->all();

            $seen = [];

            foreach ($mapped as $row) {
                $value = $row['values'][$field] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $identity = $row['values'][$key] ?? null;
                $owner = $onFile[$value] ?? $seen[$value] ?? null;

                if ($owner !== null && (string) $owner !== (string) $identity) {
                    $errors[] = [
                        /* Read off the row: an index into the flattened
                           `$mapped` list names the wrong tab once tabs are
                           concatenated. */
                        'sheet' => $row['sheet'],
                        'line' => $row['line'],
                        'message' => __(':field ":value" already belongs to a different entry', [
                            'field' => $field,
                            'value' => $value,
                        ]),
                    ];
                }

                $seen[$value] ??= $identity;
            }
        }

        return $errors;
    }

    /**
     * Write every row, unaudited and stamped by hand.
     *
     * **Not audited row by row** (ARCHITECTURE.md §9.3, §9.13) — wrapped once here so
     * it holds for every model that ever uses this engine. `upsert()` fires no
     * model events, which is *why* it needs suppressing and also why
     * `ActorObserver` never runs over it — `inserted_by` / `last_updated_by`
     * are therefore set on every row by hand rather than left to stamp
     * themselves. `last_updated_by` and `updated_at` are the only columns
     * updated on conflict, so a skipped-then-later-updated row's original
     * `inserted_by` survives.
     *
     * When the blueprint declares a {@see ImportBlueprint::scopeField()}, that
     * column is written on every row and **replaces** `company_id` in the
     * `upsert()` conflict target — the target model's unique index is then
     * `(scopeField, uniqueField)`, not `(company_id, uniqueField)`. `company_id`
     * is still written when `isCompanyScoped()` is true; it is simply not part
     * of the key.
     *
     * **An {@see ImportRelatedWriter::virtualFields()} entry is never part of
     * this row or its update-column list.** Such a field is mappable and
     * readable by `afterWrite()` but names no column on `modelClass()`.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function write(ImportBlueprint $blueprint, array $rows, ?int $actorId, SheetImport $import): void
    {
        if ($rows === []) {
            return;
        }

        $modelClass = $blueprint->modelClass();
        $now = now();
        $scopeField = $blueprint->scopeField();
        $virtual = $blueprint instanceof ImportRelatedWriter ? $blueprint->virtualFields() : [];

        $scopeAttributes = [
            ...($blueprint->isCompanyScoped() ? ['company_id' => $import->company_id] : []),
            ...($scopeField !== null ? [$scopeField => $import->scope_id] : []),
        ];

        $payload = array_map(function (array $row) use ($blueprint, $actorId, $scopeAttributes, $now, $virtual): array {
            return [
                ...$blueprint->defaultAttributes(),
                ...$scopeAttributes,
                ...array_diff_key($row, array_flip($virtual)),
                'inserted_by' => $actorId,
                'last_updated_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $rows);

        $uniqueBy = match (true) {
            $scopeField !== null => [$scopeField, $blueprint->uniqueField()],
            $blueprint->isCompanyScoped() => ['company_id', $blueprint->uniqueField()],
            default => [$blueprint->uniqueField()],
        };

        $updateColumns = [
            ...array_diff(
                array_map(fn (ImportField $field): string => $field->key, $blueprint->fields()),
                [$blueprint->uniqueField(), ...$virtual],
            ),
            'last_updated_by', 'updated_at',
        ];

        $modelClass::withoutAuditing(function () use ($modelClass, $payload, $uniqueBy, $updateColumns): void {
            foreach (array_chunk($payload, 500) as $chunk) {
                $modelClass::query()->upsert($chunk, $uniqueBy, $updateColumns);
            }
        }, globally: true);
    }

    /**
     * Keep the uploaded spreadsheet, unless configuration says not to.
     */
    private function storeFile(UploadedFile $file): ?string
    {
        if (! config('sheet-import.storage.retain_original')) {
            return null;
        }

        $path = $file->store((string) config('sheet-import.storage.upload'), $this->disk());

        return $path === false ? null : $path;
    }

    private function discard(?string $storedPath): void
    {
        if ($storedPath !== null) {
            Storage::disk($this->disk())->delete($storedPath);
        }
    }

    private function disk(): string
    {
        return (string) config('sheet-import.storage.disk');
    }
}
