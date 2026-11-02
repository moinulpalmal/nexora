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
import type { ClientListItem, StatusOption } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Create or edit a client — the form-modal standard in ARCHITECTURE.md §8.10,
 * mirroring `hrm/department-form-dialog.tsx`.
 *
 * **No company field** (§9.10): the client belongs to the company selected in
 * the header, which the server stamps. The code and name must each be unique
 * within that company, deleted clients included. Address and description are
 * free text, so they take the whole row (`field-wide`, §8.12); there is no
 * textarea primitive, so they are daisyUI's `textarea` directly.
 */
export default function ClientFormDialog({
    submit,
    statuses,
    client,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    client?: ClientListItem;
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
                                        defaultValue={client?.code}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={20}
                                        placeholder="ACME-01"
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
                                        defaultValue={client?.name}
                                        required
                                        autoComplete="off"
                                        maxLength={150}
                                        placeholder="Acme Trading Ltd."
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="contact_person">
                                        Contact person
                                    </Label>
                                    <Input
                                        id="contact_person"
                                        name="contact_person"
                                        defaultValue={
                                            client?.contact_person ?? ''
                                        }
                                        autoComplete="off"
                                        maxLength={150}
                                        aria-invalid={Boolean(
                                            errors.contact_person,
                                        )}
                                    />
                                    <InputError
                                        message={errors.contact_person}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        defaultValue={client?.phone ?? ''}
                                        autoComplete="off"
                                        maxLength={30}
                                        placeholder="+880 1712 345678"
                                        aria-invalid={Boolean(errors.phone)}
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={client?.email ?? ''}
                                        autoComplete="off"
                                        maxLength={255}
                                        aria-invalid={Boolean(errors.email)}
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            client?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="client-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive client stays on record but
                                        is not offered for new projects.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>

                                <div className="field-wide grid content-start gap-1.5">
                                    <Label htmlFor="address">Address</Label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        defaultValue={client?.address ?? ''}
                                        rows={2}
                                        maxLength={500}
                                        className={cn(
                                            'textarea w-full',
                                            errors.address && 'textarea-error',
                                        )}
                                        aria-invalid={Boolean(errors.address)}
                                    />
                                    <InputError message={errors.address} />
                                </div>

                                <div className="field-wide grid content-start gap-1.5">
                                    <Label htmlFor="description">
                                        Description
                                    </Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        defaultValue={client?.description ?? ''}
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
                                addAnother={client === undefined}
                                onIntent={setIntent}
                                saveTestId="save-client"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
