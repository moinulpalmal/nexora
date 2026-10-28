import { Form, useHttp } from '@inertiajs/react';
import { AlertTriangle, FileUp } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import RejectList from '@/components/shared/import-reject-list';
import { Button } from '@/components/ui/button';
import Combobox from '@/components/ui/combobox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { DUPLICATE_ACTION } from '@/lib/enums';
import type {
    DuplicateActionOption,
    ImportField,
    ImportReview,
    SheetImportPending,
    SheetImportSheet,
} from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/** The `errors` bag a Wayfinder `<Form>` hands its children. */
type FormErrors = Record<string, string>;

/** What the review endpoint reads — the confirm form less its extra fields. */
type ReviewPayload = {
    map: Record<string, Record<string, string>>;
    on_duplicate: string;
};

export type SheetImportDialogProps = {
    /**
     * Lower-case plural noun — "departments", "customers". Used
     * for the step-one title and the duplicate radio group's accessible name,
     * which is why it is a bare noun rather than a sentence.
     */
    resourceLabel: string;

    /**
     * Step one's description. Passed rather than derived: it varies more than a
     * noun substitution can express — one importer asks for a scope first,
     * another for nothing — and a template covering all of them would read
     * worse than each one's own sentence.
     */
    uploadDescription: ReactNode;

    /**
     * The duplicate question's label — "A row whose code already exists".
     * Passed for the same reason: the subject differs per blueprint and so does
     * its scope ("in this company" for a company-scoped table, nothing for a
     * global one).
     */
    duplicateLabel: ReactNode;

    /** `XImportController.store.form()` — spread onto step one's `<Form>`. */
    submit: RouteFormDefinition<'post'>;

    /** `XImportController.confirm.form(id)` — step two needs the staged id. */
    confirmSubmit: (sheetImportId: number) => RouteFormDefinition<'post'>;

    fields: ImportField[];
    duplicateActions: DuplicateActionOption[];
    maxFileSizeKb: number;
    pendingImport: SheetImportPending | null;

    /**
     * Controlled by the list page: its Import button opens step one, and its
     * pending-import alert reopens step two.
     */
    open: boolean;
    onOpenChange: (open: boolean) => void;

    /**
     * `data-test` prefix — "department", "customer". Every hook the browser
     * tests assert on is built from this.
     */
    testPrefix: string;

    /**
     * Extra step-one controls, rendered inside the upload `<Form>` above the
     * file input.
     *
     * **A render prop rather than `children`** because these controls need the
     * `errors` object, which only exists inside the `<Form>`'s children
     * function, and they must post alongside the file so they cannot live
     * outside it.
     *
     * Omitted by an importer whose rows need nothing besides the file. One
     * with a secondary scope (`ImportBlueprint::scopeField()`) passes its own
     * picker, keeping any dependent state at the call site — this component
     * must never learn what the scope is. If you find yourself wanting a
     * boolean flag here instead, write a scope component.
     */
    scopeFields?: (form: { errors: FormErrors }) => ReactNode;

    /**
     * `XImportController.review.url(id)` — **only for an `Imports\ImportRowWriter`
     * blueprint**. When given, the mapping
     * step gains a Review button that dry-runs the mapping and lists what
     * Import would create, update, skip and reject; Import waits for it.
     * Omitted for every all-or-nothing importer, which has no partial outcome
     * to preview.
     */
    reviewUrl?: (sheetImportId: number) => string;

    /**
     * Extra confirm-step controls, posted with the mapping — a row writer's
     * `confirmRules()` inputs. A render prop for `scopeFields`' reason, and
     * handed the latest review so a control can depend on it (required only
     * once a review says rows will be created, say).
     */
    confirmFields?: (form: {
        errors: FormErrors;
        review: ImportReview | null;
    }) => ReactNode;

    /**
     * Why this import cannot start — for a company-scoped importer, that the
     * uploader has no selected company (ARCHITECTURE.md §9.10). Step one then
     * explains that instead of offering a form that cannot succeed.
     *
     * Omitted whenever the upload can proceed, which is every importer whose
     * rows belong to no company.
     */
    unavailableReason?: ReactNode;
};

/**
 * Upload a spreadsheet, map its columns onto a target model's fields, and
 * import it — the one dialog every consumer of the mapped-import engine wears
 * (ARCHITECTURE.md §9.13).
 *
 * **Never copy it per importer.** In compozit, per-importer copies drifted from
 * each other in ways nobody chose. Everything that genuinely differs between
 * consumers is a prop above; nothing else may become one without a reason
 * written down here.
 *
 * **Step two is not a fixed question.** It renders one row per
 * {@link ImportField} the server sends, so a blueprint with different fields
 * needs no component of its own — only its controller, its routes and a call
 * site.
 *
 * **Deliberately outside the three-button standard** (ARCHITECTURE.md §8.10):
 * neither "add another" nor "clear the form" describes anything on a mapping
 * step.
 */
export default function SheetImportDialog({
    resourceLabel,
    uploadDescription,
    duplicateLabel,
    submit,
    confirmSubmit,
    fields,
    duplicateActions,
    maxFileSizeKb,
    pendingImport,
    open,
    onOpenChange,
    testPrefix,
    scopeFields,
    reviewUrl,
    confirmFields,
    unavailableReason,
}: SheetImportDialogProps) {
    const maxMb = Math.floor(maxFileSizeKb / 1024);

    /*
     * Reopen on the mapping step when an upload comes back staged. Seeded with
     * whatever is already pending on first render, so arriving at a page with
     * an unresolved import does not throw a modal at the reader — the list
     * shows the pending banner and they choose.
     */
    const lastSeen = useRef<number | null>(pendingImport?.id ?? null);

    useEffect(() => {
        const id = pendingImport?.id ?? null;

        if (id !== null && id !== lastSeen.current) {
            onOpenChange(true);
        }

        lastSeen.current = id;
    }, [pendingImport?.id, onOpenChange]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl">
                {pendingImport ? (
                    <MappingStep
                        pendingImport={pendingImport}
                        fields={fields}
                        duplicateActions={duplicateActions}
                        duplicateLabel={duplicateLabel}
                        resourceLabel={resourceLabel}
                        confirmSubmit={confirmSubmit}
                        testPrefix={testPrefix}
                        reviewUrl={reviewUrl}
                        confirmFields={confirmFields}
                        onDone={() => onOpenChange(false)}
                    />
                ) : (
                    <UploadStep
                        resourceLabel={resourceLabel}
                        uploadDescription={uploadDescription}
                        submit={submit}
                        maxMb={maxMb}
                        testPrefix={testPrefix}
                        scopeFields={scopeFields}
                        unavailableReason={unavailableReason}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

/** Step one: whatever the blueprint scopes rows by, and the spreadsheet. */
function UploadStep({
    resourceLabel,
    uploadDescription,
    submit,
    maxMb,
    testPrefix,
    scopeFields,
    unavailableReason,
}: {
    resourceLabel: string;
    uploadDescription: ReactNode;
    submit: RouteFormDefinition<'post'>;
    maxMb: number;
    testPrefix: string;
    scopeFields?: (form: { errors: FormErrors }) => ReactNode;
    unavailableReason?: ReactNode;
}) {
    return (
        <>
            <DialogTitle>Import {resourceLabel}</DialogTitle>
            <DialogDescription>{uploadDescription}</DialogDescription>

            {unavailableReason ? (
                <div
                    role="status"
                    className="mt-4 alert alert-soft alert-warning"
                >
                    <AlertTriangle className="size-5" />
                    <span>{unavailableReason}</span>
                </div>
            ) : (
                <Form
                    {...submit}
                    options={{ preserveScroll: true }}
                    className="mt-4 space-y-4"
                >
                    {({
                        processing,
                        errors,
                    }: {
                        processing: boolean;
                        errors: FormErrors;
                    }) => (
                        <>
                            {scopeFields?.({ errors })}

                            <div className="grid gap-1.5">
                                <Label htmlFor="file">Spreadsheet</Label>

                                <input
                                    id="file"
                                    name="file"
                                    type="file"
                                    accept=".xlsx,.xls"
                                    required
                                    className="file-input-bordered file-input w-full"
                                    data-test={`${testPrefix}-import-file`}
                                />

                                <p className="text-xs text-base-content/60">
                                    .xlsx or .xls up to {maxMb} MB. The first
                                    row must be the column headers.
                                </p>

                                <InputError message={errors.file} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    data-test={`submit-${testPrefix}-import`}
                                >
                                    <FileUp />
                                    {processing
                                        ? 'Reading the sheet…'
                                        : 'Continue'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            )}
        </>
    );
}
/**
 * Step two: choose which worksheets to import, map each one's columns onto the
 * target's fields, decide what happens to a duplicate, and confirm.
 *
 * **One panel per tab, because tabs are mapped separately.** A workbook's tabs
 * legitimately carry different header layouts — that is the whole reason the
 * engine stages them individually — so a single shared mapping would be wrong
 * for every workbook that is not homogeneous.
 *
 * Every combobox is uncontrolled — `defaultValue` seeds it from the server's
 * own guess for *that tab*, and the uploader overrides freely before
 * submitting. There is no client-side validation of the mapping beyond
 * `required` on the control itself; `Imports\ImportConfirmRequest` is the rule
 * that matters, and its errors render in the same `InputError` slots.
 *
 * **A deselected tab's controls are unmounted, never merely hidden.** An input
 * that is still in the DOM is an input that still posts, and a tab would then
 * import despite the uploader having unticked it, with nothing on screen to
 * suggest why. This is the one thing in the feature a green PHP suite cannot
 * prove, because it is a fact about what the browser submits.
 */
function MappingStep({
    pendingImport,
    fields,
    duplicateActions,
    duplicateLabel,
    resourceLabel,
    confirmSubmit,
    testPrefix,
    reviewUrl,
    confirmFields,
    onDone,
}: {
    pendingImport: SheetImportPending;
    fields: ImportField[];
    duplicateActions: DuplicateActionOption[];
    duplicateLabel: ReactNode;
    resourceLabel: string;
    confirmSubmit: (sheetImportId: number) => RouteFormDefinition<'post'>;
    testPrefix: string;
    reviewUrl?: (sheetImportId: number) => string;
    confirmFields?: SheetImportDialogProps['confirmFields'];
    onDone: () => void;
}) {
    const [onDuplicate, setOnDuplicate] = useState<string>(
        duplicateActions[0]?.value ?? DUPLICATE_ACTION.Skip,
    );

    const sheets = pendingImport.sheets;
    const multiSheet = sheets.length > 1;

    /* Every staged tab starts selected: the uploader chose this file in order
       to import it, so opting out is the deliberate act, not opting in. */
    const [selected, setSelected] = useState<Record<number, boolean>>(() =>
        Object.fromEntries(sheets.map((sheet) => [sheet.index, true])),
    );

    const chosen = sheets.filter((sheet) => selected[sheet.index]);
    const chosenRows = chosen.reduce(
        (total, sheet) => total + sheet.row_count,
        0,
    );

    /*
     * The review — a dry run of the mapping, for a row-writing blueprint only.
     * Cleared by any change to the mapping, the tab selection or the duplicate
     * choice, so what it says is always about what Import would post; Import
     * waits for one.
     */
    const [review, setReview] = useState<ImportReview | null>(null);
    const [reviewError, setReviewError] = useState<string | null>(null);
    const mappingRef = useRef<HTMLDivElement>(null);
    const http = useHttp<ReviewPayload, ImportReview>({
        map: {},
        on_duplicate: '',
    });

    const runReview = () => {
        const form = mappingRef.current?.closest('form');

        if (!reviewUrl || !form) {
            return;
        }

        setReviewError(null);
        http.transform(() => mappingPayload(new FormData(form)));
        http.post(reviewUrl(pendingImport.id), {
            onSuccess: (response) => setReview(response),
            onError: (errors) =>
                setReviewError(Object.values(errors).join(' ')),
            onHttpException: () => {
                setReviewError(
                    'The mapping could not be reviewed. Check every required field has a column.',
                );

                return false;
            },
        });
    };

    const invalidateReview = () => {
        setReview(null);
        setReviewError(null);
    };

    const needsReview = reviewUrl !== undefined && review === null;

    return (
        <>
            <DialogTitle>Map the columns</DialogTitle>
            <DialogDescription>
                {pendingImport.source_file_name} has {pendingImport.row_count}{' '}
                row{pendingImport.row_count === 1 ? '' : 's'}
                {multiSheet
                    ? ` across ${sheets.length} worksheets. Each tab is mapped on its own.`
                    : '. Say which of its columns holds each field below.'}
            </DialogDescription>

            {pendingImport.warnings.length > 0 && (
                <div
                    role="status"
                    className="mt-4 alert alert-soft alert-warning"
                    data-test={`${testPrefix}-import-warnings`}
                >
                    <AlertTriangle className="size-5" />
                    <span className="text-xs">
                        {pendingImport.warnings.join(' ')}
                    </span>
                </div>
            )}

            <Form
                {...confirmSubmit(pendingImport.id)}
                options={{ preserveScroll: true }}
                onSuccess={onDone}
                className="mt-4 space-y-4"
            >
                {({
                    processing,
                    errors,
                }: {
                    processing: boolean;
                    errors: FormErrors;
                }) => (
                    <>
                        <div
                            ref={mappingRef}
                            onChange={invalidateReview}
                            className="space-y-4"
                        >
                            <div className="max-h-96 space-y-4 overflow-y-auto">
                                {sheets.map((sheet, sheetPosition) => (
                                    <SheetPanel
                                        key={sheet.index}
                                        sheet={sheet}
                                        fields={fields}
                                        errors={errors}
                                        testPrefix={testPrefix}
                                        showToggle={multiSheet}
                                        selected={selected[sheet.index] ?? true}
                                        onToggle={(next) => {
                                            invalidateReview();
                                            setSelected((current) => ({
                                                ...current,
                                                [sheet.index]: next,
                                            }));
                                        }}
                                        onMappingChange={invalidateReview}
                                        autoFocus={sheetPosition === 0}
                                    />
                                ))}
                            </div>

                            <InputError message={errors.map} />

                            {multiSheet && (
                                <p
                                    className="text-xs text-base-content/60"
                                    data-test={`${testPrefix}-import-selection-summary`}
                                >
                                    Importing {chosen.length} of {sheets.length}{' '}
                                    worksheets — {chosenRows} row
                                    {chosenRows === 1 ? '' : 's'}.
                                </p>
                            )}

                            <div className="space-y-2">
                                <Label>{duplicateLabel}</Label>

                                <div
                                    className="space-y-2"
                                    role="radiogroup"
                                    aria-label={`What to do with existing ${resourceLabel}`}
                                >
                                    {duplicateActions.map((action) => (
                                        <label
                                            key={action.value}
                                            className={`flex cursor-pointer items-center gap-3 rounded-box border p-3 ${
                                                onDuplicate === action.value
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-base-300/70'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="on_duplicate"
                                                value={action.value}
                                                checked={
                                                    onDuplicate === action.value
                                                }
                                                onChange={() =>
                                                    setOnDuplicate(action.value)
                                                }
                                                className="radio radio-sm"
                                                data-test={`${testPrefix}-import-duplicate-${action.value}`}
                                            />

                                            <span>{action.label}</span>
                                        </label>
                                    ))}
                                </div>

                                <InputError message={errors.on_duplicate} />
                            </div>
                        </div>

                        {reviewUrl && (
                            <ReviewPanel
                                review={review}
                                error={reviewError}
                                testPrefix={testPrefix}
                            />
                        )}

                        {confirmFields?.({ errors, review })}

                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </DialogClose>

                            {reviewUrl && (
                                <Button
                                    variant="secondary"
                                    type="button"
                                    onClick={runReview}
                                    disabled={
                                        http.processing || chosen.length === 0
                                    }
                                    data-test={`${testPrefix}-import-review`}
                                >
                                    {http.processing
                                        ? 'Reviewing…'
                                        : review
                                          ? 'Review again'
                                          : 'Review'}
                                </Button>
                            )}

                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    chosen.length === 0 ||
                                    needsReview
                                }
                                data-test={`submit-${testPrefix}-import-confirm`}
                            >
                                {processing
                                    ? 'Importing…'
                                    : review
                                      ? `Import ${review.created + review.updated} row${review.created + review.updated === 1 ? '' : 's'}`
                                      : 'Import'}
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </Form>
        </>
    );
}

/**
 * The mapping form's fields as the JSON body the review endpoint reads —
 * `map[sheet][field]` nested, as PHP would have parsed the form post.
 * Everything else the form carries (the password above all) is left out:
 * a dry run has no use for it.
 */
function mappingPayload(formData: FormData): ReviewPayload {
    const map: Record<string, Record<string, string>> = {};

    formData.forEach((value, name) => {
        const match = /^map\[(\d+)\]\[([^\]]+)\]$/.exec(name);

        if (match && typeof value === 'string' && value !== '') {
            map[match[1]] = { ...(map[match[1]] ?? {}), [match[2]]: value };
        }
    });

    return { map, on_duplicate: String(formData.get('on_duplicate') ?? '') };
}

/**
 * What the last review said Import would do. Lists up to the server's cap of
 * rejected rows; the full set comes back as a download after Import.
 */
function ReviewPanel({
    review,
    error,
    testPrefix,
}: {
    review: ImportReview | null;
    error: string | null;
    testPrefix: string;
}) {
    if (error) {
        return (
            <div role="alert" className="alert alert-soft alert-error">
                <AlertTriangle className="size-5" />
                <span className="text-xs">{error}</span>
            </div>
        );
    }

    if (!review) {
        return (
            <p className="text-xs text-base-content/60">
                Review checks every row before anything is written — what would
                be created, updated, skipped, and which rows would be rejected
                and why. Rows that fail are left out; the rest are imported.
            </p>
        );
    }

    return (
        <div
            className="space-y-2 rounded-box border border-base-300/70 p-3"
            data-test={`${testPrefix}-import-review-result`}
        >
            <p className="text-sm">
                <strong>{review.created}</strong> to create,{' '}
                <strong>{review.updated}</strong> to update,{' '}
                <strong>{review.skipped}</strong> to skip,{' '}
                <strong
                    className={review.rejected > 0 ? 'text-error' : undefined}
                >
                    {review.rejected}
                </strong>{' '}
                rejected.
            </p>

            {review.notes.map((note) => (
                <p key={note} className="text-xs text-base-content/70">
                    {note}
                </p>
            ))}

            {review.rejects.length > 0 && (
                <RejectList rejects={review.rejects} total={review.rejected} />
            )}
        </div>
    );
}

/**
 * One worksheet's mapping panel: its sample rows, and a column picker per field.
 *
 * The toggle is hidden for a single-tab workbook — the overwhelming majority —
 * because offering to deselect the only tab is a checkbox whose only useful
 * state is the one it starts in.
 */
function SheetPanel({
    sheet,
    fields,
    errors,
    testPrefix,
    showToggle,
    selected,
    onToggle,
    onMappingChange,
    autoFocus,
}: {
    sheet: SheetImportSheet;
    fields: ImportField[];
    errors: FormErrors;
    testPrefix: string;
    showToggle: boolean;
    selected: boolean;
    onToggle: (next: boolean) => void;
    /** Any column pick changed — a review of the old mapping is stale. */
    onMappingChange: () => void;
    autoFocus: boolean;
}) {
    const headerOptions = sheet.headers.map((header, index) => ({
        value: index,
        label: header,
    }));

    return (
        <div
            className={`rounded-box border p-3 ${
                selected
                    ? 'border-base-300/70'
                    : 'border-base-300/40 opacity-60'
            }`}
            data-test={`${testPrefix}-import-sheet-${sheet.index}`}
        >
            <div className="flex items-center justify-between gap-3">
                {showToggle ? (
                    <label className="flex cursor-pointer items-center gap-2">
                        <input
                            type="checkbox"
                            className="checkbox checkbox-sm"
                            checked={selected}
                            onChange={(event) => onToggle(event.target.checked)}
                            data-test={`${testPrefix}-import-sheet-toggle-${sheet.index}`}
                        />
                        <span className="font-medium">{sheet.name}</span>
                    </label>
                ) : (
                    <span className="font-medium">{sheet.name}</span>
                )}

                <span className="text-xs text-base-content/60">
                    {sheet.row_count} row{sheet.row_count === 1 ? '' : 's'}
                </span>
            </div>

            {/*
             * Unmounted when deselected, not hidden — see MappingStep's
             * docblock. A control still in the DOM still posts, and the tab
             * would import despite having been unticked.
             */}
            {selected && (
                <>
                    {sheet.sample_rows.length > 0 && (
                        <div className="mt-3 overflow-x-auto rounded-box border border-base-300/70">
                            <table className="table table-xs">
                                <thead>
                                    <tr>
                                        {sheet.headers.map((header) => (
                                            <th
                                                key={header}
                                                className="whitespace-nowrap"
                                            >
                                                {header}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {sheet.sample_rows.map((row, index) => (
                                        <tr key={index}>
                                            {row.map((cell, cellIndex) => (
                                                <td
                                                    key={cellIndex}
                                                    className="whitespace-nowrap text-base-content/70"
                                                >
                                                    {cell ?? (
                                                        <span className="text-base-content/40">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="mt-3 space-y-3">
                        {fields.map((field, index) => (
                            <div
                                key={field.key}
                                className="grid grid-cols-[1fr_2fr] items-center gap-3"
                            >
                                <Label
                                    htmlFor={`map-${sheet.index}-${field.key}`}
                                    className="flex items-center gap-1"
                                >
                                    {field.label}
                                    {field.required && (
                                        <span
                                            className="text-error"
                                            aria-hidden="true"
                                        >
                                            *
                                        </span>
                                    )}
                                </Label>

                                <div>
                                    <Combobox
                                        id={`map-${sheet.index}-${field.key}`}
                                        name={`map[${sheet.index}][${field.key}]`}
                                        options={headerOptions}
                                        defaultValue={
                                            sheet.suggested_map[field.key] ??
                                            null
                                        }
                                        required={field.required}
                                        onChange={onMappingChange}
                                        autoFocus={autoFocus && index === 0}
                                        aria-invalid={Boolean(
                                            errors[
                                                `map.${sheet.index}.${field.key}`
                                            ],
                                        )}
                                        data-test={`${testPrefix}-import-map-${sheet.index}-${field.key}`}
                                    />

                                    <InputError
                                        message={
                                            errors[
                                                `map.${sheet.index}.${field.key}`
                                            ]
                                        }
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}
