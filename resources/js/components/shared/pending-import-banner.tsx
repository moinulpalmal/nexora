import { AlertTriangle } from 'lucide-react';
import ConfirmActionDialog from '@/components/shared/confirm-action-dialog';
import { Button } from '@/components/ui/button';
import type { SheetImportPending } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    pendingImport: SheetImportPending;

    /** `XImportController.cancel.form(pendingImport.id)`. */
    cancelSubmit: RouteFormDefinition<'post'>;

    /** Reopens the import dialog on its mapping step. */
    onResume: () => void;

    /**
     * `data-test` prefix — "department", "customer". Produces
     * `pending-{prefix}-import`, `resume-{prefix}-import` and
     * `cancel-{prefix}-import`, which is what every existing feature test
     * already asserts on.
     */
    testPrefix: string;
};

/**
 * An import that was uploaded but never mapped, offered back on the list page.
 *
 * **Deliberately a banner rather than a modal thrown on arrival.** The reader
 * did not open it, and a staged import survives on the server indefinitely, so
 * it is announced and they choose — the treatment compozit's import screens
 * proved.
 *
 * The cancel action is a terminal human decision ("never import this file"), so
 * it moves the row to `ImportStatus::Cancelled` rather than deleting it; the
 * staged rows and the retained workbook are kept (ARCHITECTURE.md §9.13).
 *
 * **This replaces six copies of the identical block**, one pasted into each
 * consumer's index page, which is a seventh duplication on top of the six
 * dialogs `sheet-import-dialog.tsx` replaces.
 */
export default function PendingImportBanner({
    pendingImport,
    cancelSubmit,
    onResume,
    testPrefix,
}: Props) {
    return (
        <div
            role="status"
            className="alert alert-soft alert-warning"
            data-test={`pending-${testPrefix}-import`}
        >
            <AlertTriangle className="size-5" />

            <span>
                {pendingImport.source_file_name} is waiting on its column
                mapping.
            </span>

            <Button
                variant="ghost"
                size="sm"
                onClick={onResume}
                data-test={`resume-${testPrefix}-import`}
            >
                Continue
            </Button>

            <ConfirmActionDialog
                submit={cancelSubmit}
                title="Cancel import"
                description={`"${pendingImport.source_file_name}" will not be imported. The upload is kept in the audit trail, but its column mapping is abandoned and the banner is dismissed.`}
                confirmLabel="Cancel import"
                testId={`cancel-${testPrefix}-import`}
            >
                <Button
                    variant="ghost"
                    size="sm"
                    data-test={`cancel-${testPrefix}-import`}
                >
                    Cancel import
                </Button>
            </ConfirmActionDialog>
        </div>
    );
}
