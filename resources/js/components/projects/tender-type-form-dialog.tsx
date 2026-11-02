import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCallback, useState } from 'react';
import InputError from '@/components/input-error';
import FormDialogFooter from '@/components/shared/form-dialog-footer';
import Combobox from '@/components/ui/combobox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFormDialog } from '@/hooks/use-form-dialog';
import { RECORD_STATUS } from '@/lib/enums';
import type { StatusOption, TenderTypeListItem } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Create or edit a tender type — the form-modal standard in ARCHITECTURE.md
 * §8.10, mirroring `hrm/designation-form-dialog.tsx` without its inline-create
 * props (no other form creates a tender type yet).
 *
 * Tender types are group-wide (§9.10): no company, and the code and name
 * must each be unique across every tender type, deleted ones included.
 */
export default function TenderTypeFormDialog({
    submit,
    statuses,
    tenderType,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    tenderType?: TenderTypeListItem;
    title: string;
    description: string;
    /** The trigger. */
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const close = useCallback(() => setOpen(false), []);
    const { formKey, formProps, setIntent } = useFormDialog(close);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>

            <DialogContent className="max-w-4xl">
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    key={formKey}
                    {...submit}
                    {...formProps}
                    options={{ preserveScroll: true }}
                    className="mt-4 space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="field-grid">
                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="code">Code</Label>
                                    <Input
                                        id="code"
                                        name="code"
                                        defaultValue={tenderType?.code}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={20}
                                        placeholder="OTM"
                                        className="font-mono uppercase"
                                        aria-invalid={Boolean(errors.code)}
                                    />
                                    <p className="text-xs text-base-content/60">
                                        Letters and digits, with single hyphens
                                        or underscores. Saved in capitals.
                                    </p>
                                    <InputError message={errors.code} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={tenderType?.name}
                                        required
                                        autoComplete="off"
                                        maxLength={100}
                                        placeholder="Open Tendering Method"
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            tenderType?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="tender-type-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive tender type stays on record
                                        but is not offered for new entries.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            <FormDialogFooter
                                processing={processing}
                                addAnother={tenderType === undefined}
                                onIntent={setIntent}
                                saveTestId="save-tender-type"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
