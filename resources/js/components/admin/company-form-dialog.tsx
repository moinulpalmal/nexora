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
import type { CompanyListItem, StatusOption } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Create or edit a company — the form-modal standard in ARCHITECTURE.md §8.10,
 * mirroring `role-form-dialog.tsx`.
 *
 * `CompanyRequest` trims the name and uppercases the short code before it
 * checks them, and both must be unique across every company — deleted ones
 * included, so a clash with a deleted company says to restore it instead.
 */
export default function CompanyFormDialog({
    submit,
    statuses,
    company,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    company?: CompanyListItem;
    title: string;
    description: string;
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
                                        defaultValue={company?.name}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={150}
                                        placeholder="Palmal Garments Ltd."
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="short_code">
                                        Short code
                                    </Label>
                                    <Input
                                        id="short_code"
                                        name="short_code"
                                        defaultValue={company?.short_code}
                                        required
                                        autoComplete="off"
                                        maxLength={10}
                                        placeholder="PGL"
                                        className="font-mono uppercase"
                                        aria-invalid={Boolean(
                                            errors.short_code,
                                        )}
                                    />
                                    <p className="text-xs text-base-content/60">
                                        Shown in the header switcher. Saved in
                                        capitals.
                                    </p>
                                    <InputError message={errors.short_code} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            company?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="company-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive company can no longer be
                                        selected in the header.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            <FormDialogFooter
                                processing={processing}
                                addAnother={company === undefined}
                                onIntent={setIntent}
                                saveTestId="save-company"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
