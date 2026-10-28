import { Form } from '@inertiajs/react';
import { Info } from 'lucide-react';
import ImportLimitController from '@/actions/App/Http/Controllers/Admin/ImportLimitController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCan } from '@/hooks/use-can';

type Props = {
    limits: {
        max_rows: number;
        max_columns: number;
        max_sheets: number;
    };
    /** Read-only — see the note this panel renders beside it. */
    maxFileSizeKb: number;
    /** Who last saved them, or `null` while the config defaults apply. */
    updatedBy: string | null;
    updatedAt: string | null;
};

/**
 * The caps every spreadsheet import is read under (ARCHITECTURE.md §9.13).
 * Until the first save the config defaults apply; saving creates the row.
 */
export default function ImportLimitsPanel({
    limits,
    maxFileSizeKb,
    updatedBy,
    updatedAt,
}: Props) {
    const canUpdate = useCan('admin.import-limits.update');
    const maxMb = Math.floor(maxFileSizeKb / 1024);

    return (
        <Form
            {...ImportLimitController.update.form()}
            options={{ preserveScroll: true }}
            className="max-w-xl space-y-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-1.5">
                        <Label htmlFor="max_rows">
                            Maximum rows per workbook
                        </Label>

                        <Input
                            id="max_rows"
                            name="max_rows"
                            type="number"
                            min={1}
                            max={100000}
                            required
                            autoFocus
                            disabled={!canUpdate}
                            defaultValue={limits.max_rows}
                            aria-invalid={Boolean(errors.max_rows)}
                            data-test="import-limit-max-rows"
                        />

                        <p className="text-xs text-base-content/60">
                            Counted across every tab, and blank rows are not
                            counted — the cap stops a workbook with a runaway
                            empty tail, not real work.
                        </p>

                        <InputError message={errors.max_rows} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="max_columns">
                            Maximum columns per sheet
                        </Label>

                        <Input
                            id="max_columns"
                            name="max_columns"
                            type="number"
                            min={1}
                            max={1000}
                            required
                            disabled={!canUpdate}
                            defaultValue={limits.max_columns}
                            aria-invalid={Boolean(errors.max_columns)}
                            data-test="import-limit-max-columns"
                        />

                        <p className="text-xs text-base-content/60">
                            Counted to the last column holding data, so unused
                            columns to the right do not count against it.
                        </p>

                        <InputError message={errors.max_columns} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="max_sheets">
                            Maximum worksheets per workbook
                        </Label>

                        <Input
                            id="max_sheets"
                            name="max_sheets"
                            type="number"
                            min={1}
                            max={100}
                            required
                            disabled={!canUpdate}
                            defaultValue={limits.max_sheets}
                            aria-invalid={Boolean(errors.max_sheets)}
                            data-test="import-limit-max-sheets"
                        />

                        <p className="text-xs text-base-content/60">
                            How many tabs one uploaded file may carry.
                        </p>

                        <InputError message={errors.max_sheets} />
                    </div>

                    {/*
                     * Shown because an administrator raising the row limit will
                     * look for it here — and told plainly why it is not
                     * editable, so its absence reads as a decision.
                     */}
                    <div role="note" className="alert alert-soft">
                        <Info className="size-5" />
                        <span className="text-xs">
                            Uploads are also capped at {maxMb} MB per file. That
                            one is not editable here: PHP&rsquo;s own{' '}
                            <code>upload_max_filesize</code> bounds it, so a
                            larger number set on this screen would be refused by
                            the server anyway. Change it in{' '}
                            <code>config/sheet-import.php</code> and the PHP
                            configuration together.
                        </span>
                    </div>

                    {updatedBy !== null && updatedAt !== null && (
                        <p className="text-xs text-base-content/60">
                            Last changed by {updatedBy} on{' '}
                            {new Date(updatedAt).toLocaleString()}.
                        </p>
                    )}

                    {canUpdate && (
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="save-import-limits"
                        >
                            {processing ? 'Saving…' : 'Save'}
                        </Button>
                    )}
                </>
            )}
        </Form>
    );
}
