import { router } from '@inertiajs/react';
import { AlertTriangle, Download, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import RejectList from '@/components/shared/import-reject-list';
import { Button } from '@/components/ui/button';
import type { ImportResult } from '@/types';

/**
 * A partial import's outcome on its list page: the
 * counts, the rejected rows and their reasons, and the rejected rows as a
 * workbook to fix and upload again.
 *
 * Driven by the `importResult` flash `Imports\SheetImportController::confirm()`
 * sends only when a row was rejected — so an all-or-nothing importer, which
 * never rejects a row, never shows it. Stays until dismissed: the toast that
 * arrives with it disappears on its own, and someone has to act on these rows.
 */
export default function ImportResultPanel({
    rejectsUrl,
    testPrefix,
}: {
    /** `XImportController.rejects.url(id)`. */
    rejectsUrl: (sheetImportId: number) => string;
    testPrefix: string;
}) {
    const [result, setResult] = useState<ImportResult | null>(null);

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const next = flash?.importResult as ImportResult | undefined;

            if (next) {
                setResult(next);
            }
        });
    }, []);

    if (!result) {
        return null;
    }

    return (
        <div
            role="status"
            className="space-y-3 rounded-box border border-warning/40 bg-warning/5 p-4"
            data-test={`${testPrefix}-import-result`}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-start gap-2">
                    <AlertTriangle className="mt-0.5 size-5 text-warning" />
                    <p className="text-sm">
                        Imported {result.file}: {result.created} created,{' '}
                        {result.updated} updated, {result.skipped} skipped,{' '}
                        <strong>{result.rejected} rejected</strong>. The
                        rejected rows were not written — fix them and upload
                        them again.
                    </p>
                </div>

                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setResult(null)}
                    aria-label="Dismiss"
                >
                    <X />
                </Button>
            </div>

            <RejectList rejects={result.rejects} total={result.rejected} />

            <Button variant="secondary" asChild>
                <a
                    href={rejectsUrl(result.id)}
                    data-test={`${testPrefix}-import-rejects-download`}
                >
                    <Download /> Download rejected rows
                </a>
            </Button>
        </div>
    );
}
