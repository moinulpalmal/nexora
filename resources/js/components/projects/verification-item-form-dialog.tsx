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
import { cn } from '@/lib/utils';
import type { StatusOption, VerificationItemListItem } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Create or edit a verification item — the form-modal standard in
 * ARCHITECTURE.md §8.10, mirroring `projects/product-category-form-dialog.tsx`
 * without a code field (owner, 2026-11-03).
 *
 * **No company field** (§9.10): the item belongs to the company selected in
 * the header, which the server stamps. The name must be unique within that
 * company, deleted items included.
 */
export default function VerificationItemFormDialog({
    submit,
    statuses,
    item,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    item?: VerificationItemListItem;
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
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={item?.name}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={100}
                                        placeholder="Trade licence checked"
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
                                            item?.status ?? RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="verification-item-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive item stays on record but is
                                        not offered for new entries.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>

                                <div className="field-wide grid content-start gap-1.5">
                                    <Label htmlFor="description">
                                        Description
                                    </Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        defaultValue={item?.description ?? ''}
                                        rows={3}
                                        maxLength={5000}
                                        className={cn(
                                            'textarea w-full',
                                            errors.description &&
                                                'textarea-error',
                                        )}
                                        aria-invalid={Boolean(
                                            errors.description,
                                        )}
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </div>

                            <FormDialogFooter
                                processing={processing}
                                addAnother={item === undefined}
                                onIntent={setIntent}
                                saveTestId="save-verification-item"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
