<?php

namespace App\Http\Controllers\Imports;

use App\Enums\Imports\ImportStatus;
use App\Exceptions\Imports\SheetImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\ImportConfirmRequest;
use App\Models\SheetImport;
use App\Services\Imports\ImportBlueprint;
use App\Services\Imports\ImportRegistry;
use App\Services\Imports\ImportRowWriter;
use App\Services\Imports\ImportRunner;
use App\Services\Imports\ImportWorkbookWriter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The endpoints every consumer of the mapped-import engine wears
 * (ARCHITECTURE.md §9.13), written once.
 *
 * A concrete importer supplies {@see self::importKey()}, {@see self::indexRoute()}
 * and a three-line `store()` — concrete only so it can type-hint its own upload
 * request, which is where its file rule (and any scope field) lives. Everything
 * else — the `SheetImportException` → `error` toast, the counted success
 * sentence, the cancel guards, the downloads — lives here so it cannot drift
 * between one importer and the next.
 *
 * Module-less for the reason the engine is (ARCHITECTURE.md §4): it serves every
 * module that imports a spreadsheet and belongs to none of them.
 *
 * **Concrete controllers are still one class each, and that is deliberate.**
 * Wayfinder generates a typed action module per controller, and each is gated
 * by its own `{module}.{resource}.import` permission in its own module's route
 * file, so collapsing them into one would cost both.
 */
abstract class SheetImportController extends Controller
{
    /** How many rejected rows a partial import's result panel lists; the download has them all. */
    protected const int FLASHED_REJECTS = 50;

    public function __construct(protected ImportRunner $imports) {}

    /**
     * The `ImportRegistry` key this controller imports under.
     *
     * **Public because {@see ImportConfirmRequest::authorize()} reads it.** The
     * cross-importer guard has to run *before* validation — see that method for
     * why — and a `FormRequest` can only reach it through the resolved
     * controller.
     */
    abstract public function importKey(): string;

    /** The route name the list lives at — every redirect here targets it. */
    abstract protected function indexRoute(): string;

    /**
     * The company every imported row belongs to — the uploader's **selected**
     * company, resolved on the server, never a value posted from the browser
     * (ARCHITECTURE.md §9.10).
     *
     * `null` until the Company master exists; a company-scoped importer
     * overrides this then. {@see ImportRunner::stage()} refuses a
     * company-scoped blueprint that arrives with `null`.
     */
    protected function companyIdFor(FormRequest $request): ?int
    {
        return null;
    }

    /**
     * The blueprint's optional second scope ({@see ImportBlueprint::scopeField()}),
     * as the importer's own upload request validated it. `null` when it has none.
     */
    protected function scopeIdFor(FormRequest $request): ?int
    {
        return null;
    }

    /**
     * Read an uploaded spreadsheet and stage every worksheet for mapping.
     *
     * Called from each subclass's `store()`, which exists solely to name its
     * own request class.
     */
    protected function stageUpload(FormRequest $request): RedirectResponse
    {
        try {
            $this->imports->stage(
                $request->file('file'),
                $this->importKey(),
                $this->companyIdFor($request),
                $this->scopeIdFor($request),
            );
        } catch (SheetImportException $exception) {
            /*
             * `error`, not `warning` (ARCHITECTURE.md §8.8): the file is not one
             * this reader can use, and no amount of other work changes that.
             */
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        /*
         * Always to the index, not `back()`: the list carries `pendingImport`
         * and the dialog reopens on its mapping step from the redirect alone,
         * which must not depend on a referer being present.
         */
        return to_route($this->indexRoute());
    }

    /**
     * Apply the uploader's confirmed per-worksheet mapping and write the rows.
     *
     * **The uploader-only and cross-importer guards live in
     * {@see ImportConfirmRequest::authorize()}, not here**: the shared request
     * resolves the blueprint from the *staged row's* key, so a guard here would
     * run after validation had already answered with errors describing the
     * wrong problem.
     */
    public function confirm(ImportConfirmRequest $request, SheetImport $sheetImport): RedirectResponse
    {
        try {
            $outcome = $this->imports->confirm(
                $sheetImport,
                $request->workbookMap(),
                $request->duplicateAction(),
                $request->writerOptions(),
            );
        } catch (SheetImportException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route($this->indexRoute());
        }

        $counts = [
            'file' => $sheetImport->source_file_name,
            'created' => $outcome->created,
            'updated' => $outcome->updated,
            'skipped' => $outcome->skipped,
            'rejected' => count($outcome->rejected),
        ];

        if ($outcome->rejected === []) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Imported :file — :created created, :updated updated, :skipped skipped.', $counts),
            ]);

            return to_route($this->indexRoute());
        }

        /*
         * Only an `ImportRowWriter` reaches here: a partial import. `warning`,
         * not `success` — some rows did not land and someone has to act on
         * them — and `importResult` carries the reasons for the list page's
         * result panel, capped so a 1,000-row reject never rides a flash.
         */
        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => __('Imported :file — :created created, :updated updated, :skipped skipped, :rejected rejected.', $counts),
        ]);

        Inertia::flash('importResult', [
            'id' => $sheetImport->id,
            ...$counts,
            'rejects' => array_slice($outcome->rejected, 0, self::FLASHED_REJECTS),
        ]);

        return to_route($this->indexRoute());
    }

    /**
     * What Import would do with this mapping — counts, the rows it would
     * reject and why, and any notes — without writing anything. Only an
     * {@see ImportRowWriter} has a review step; any other importer 404s.
     *
     * JSON rather than an Inertia visit: the mapping step stays open and reads
     * the answer in place (ARCHITECTURE.md §8.4).
     */
    public function review(ImportConfirmRequest $request, SheetImport $sheetImport, ImportRegistry $registry): JsonResponse
    {
        abort_unless($sheetImport->status === ImportStatus::Pending, 404);
        abort_unless($registry->resolve($this->importKey()) instanceof ImportRowWriter, 404);

        try {
            $outcome = $this->imports->review($sheetImport, $request->workbookMap(), $request->duplicateAction());
        } catch (SheetImportException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            ...$outcome->toArray(),
            'rejects' => array_slice($outcome->rejected, 0, self::FLASHED_REJECTS),
            'notes' => $outcome->notes,
        ]);
    }

    /**
     * A confirmed import's rejected rows, with their original cells and the
     * reason, ready to fix and upload again. Only an {@see ImportRowWriter}
     * import ever has any; the guard is the status and key, not the type, so a
     * stale link 404s rather than handing back an empty workbook.
     */
    public function rejects(SheetImport $sheetImport, ImportWorkbookWriter $workbooks): BinaryFileResponse
    {
        abort_unless($sheetImport->import_key === $this->importKey(), 404);
        abort_unless($sheetImport->status === ImportStatus::Confirmed, 404);
        abort_unless($sheetImport->rejected_count > 0, 404);

        $name = pathinfo($sheetImport->source_file_name, PATHINFO_FILENAME).' - rejected rows.xlsx';

        return response()->download($workbooks->rejects($sheetImport), $name)->deleteFileAfterSend();
    }

    /**
     * A blank workbook headed with every importable column, which
     * `SheetReader::suggestMap()` maps on its own when uploaded back.
     */
    public function template(ImportRegistry $registry, ImportWorkbookWriter $workbooks): BinaryFileResponse
    {
        $blueprint = $registry->resolve($this->importKey());

        return response()
            ->download($workbooks->template($blueprint), Str::slug($blueprint->label()).'-import-template.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Abandon a staged import from the pending banner.
     *
     * **No uploader check, unlike `confirm()`.** Confirming a mapping over data
     * you never saw is a decision nobody should be asked to make; cancelling is
     * the opposite — throwing that data away — so anyone the importer's own
     * permission middleware already lets through may clear any pending import,
     * which is also how a row left by someone since gone stops nagging forever.
     *
     * Only a `Pending` row can be cancelled: a `Confirmed` import has already
     * written its rows, and a `Cancelled` one is already done — both 404 rather
     * than silently redirecting, so a stale button surfaces as a bug instead of
     * hiding one.
     */
    public function cancel(SheetImport $sheetImport): RedirectResponse
    {
        abort_unless($sheetImport->import_key === $this->importKey(), 404);
        abort_unless($sheetImport->status === ImportStatus::Pending, 404);

        $this->imports->cancel($sheetImport);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Cancelled the import of :file.', ['file' => $sheetImport->source_file_name]),
        ]);

        return to_route($this->indexRoute());
    }
}
