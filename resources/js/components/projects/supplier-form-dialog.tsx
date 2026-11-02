import { Form } from '@inertiajs/react';
import type { ComponentProps, ReactNode } from 'react';
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
import type {
    StatusOption,
    SupplierCategoryOption,
    SupplierListItem,
} from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Only the active categories, plus the one the supplier already holds — so a
 * supplier in a category that was later deactivated still shows it, and
 * nobody newly chooses an inactive one (the server refuses that too), as
 * `hrm/employee-form-dialog.tsx`'s `choosable()`.
 */
function choosable(options: SupplierCategoryOption[], current?: number) {
    return options
        .filter(
            (option) =>
                option.status === RECORD_STATUS.Active ||
                option.value === current,
        )
        .map(({ value, label, hint }) => ({ value, label, hint }));
}

/**
 * One optional text field: label, input, error. The supplier form has a dozen
 * of them, each shaped as the client form's.
 */
function TextField({
    name,
    label,
    error,
    hint,
    ...input
}: {
    name: keyof SupplierListItem & string;
    label: string;
    error?: string;
    hint?: string;
} & Omit<ComponentProps<typeof Input>, 'name' | 'id'>) {
    return (
        <div className="grid content-start gap-1.5">
            <Label htmlFor={name}>{label}</Label>
            <Input
                id={name}
                name={name}
                autoComplete="off"
                aria-invalid={Boolean(error)}
                {...input}
            />
            {hint && <p className="text-xs text-base-content/60">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

/** A heading across both columns, between the form's groups of fields. */
function FieldGroupHeading({ children }: { children: ReactNode }) {
    return (
        <h3 className="field-wide mt-2 border-b border-base-300/70 pb-1 text-sm font-semibold">
            {children}
        </h3>
    );
}

/**
 * Create or edit a supplier — the form-modal standard in ARCHITECTURE.md
 * §8.10, mirroring `projects/client-form-dialog.tsx`, in three groups: the
 * supplier, its tax and licence numbers, and its bank account.
 *
 * **No company field** (§9.10): the supplier belongs to the company selected
 * in the header, which the server stamps. **The category** is group-wide and
 * required; only active categories are offered.
 */
export default function SupplierFormDialog({
    submit,
    statuses,
    categories,
    supplier,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    /** Every supplier category, inactive ones marked. */
    categories: SupplierCategoryOption[];
    supplier?: SupplierListItem;
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
                                <TextField
                                    name="code"
                                    label="Code"
                                    defaultValue={supplier?.code}
                                    required
                                    autoFocus
                                    maxLength={20}
                                    placeholder="BM-01"
                                    className="font-mono uppercase"
                                    hint="Letters and digits, with single hyphens or underscores. Saved in capitals."
                                    error={errors.code}
                                />

                                <TextField
                                    name="name"
                                    label="Name"
                                    defaultValue={supplier?.name}
                                    required
                                    maxLength={150}
                                    placeholder="Buildmart Supplies Ltd."
                                    error={errors.name}
                                />

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="supplier_category_id">
                                        Category
                                    </Label>
                                    <Combobox
                                        id="supplier_category_id"
                                        name="supplier_category_id"
                                        defaultValue={
                                            supplier?.supplier_category_id
                                        }
                                        options={choosable(
                                            categories,
                                            supplier?.supplier_category_id,
                                        )}
                                        required
                                        aria-invalid={Boolean(
                                            errors.supplier_category_id,
                                        )}
                                        data-test="supplier-category"
                                    />
                                    <InputError
                                        message={errors.supplier_category_id}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            supplier?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="supplier-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive supplier stays on record but
                                        is not offered for new purchases.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>

                                <TextField
                                    name="contact_person"
                                    label="Contact person"
                                    defaultValue={
                                        supplier?.contact_person ?? ''
                                    }
                                    maxLength={150}
                                    error={errors.contact_person}
                                />

                                <TextField
                                    name="phone"
                                    label="Phone"
                                    type="tel"
                                    defaultValue={supplier?.phone ?? ''}
                                    maxLength={30}
                                    placeholder="+880 1812 345678"
                                    error={errors.phone}
                                />

                                <TextField
                                    name="email"
                                    label="Email"
                                    type="email"
                                    defaultValue={supplier?.email ?? ''}
                                    maxLength={255}
                                    error={errors.email}
                                />

                                <div className="field-wide grid content-start gap-1.5">
                                    <Label htmlFor="address">Address</Label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        defaultValue={supplier?.address ?? ''}
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

                                <FieldGroupHeading>
                                    Tax and licence
                                </FieldGroupHeading>

                                <TextField
                                    name="tin"
                                    label="TIN"
                                    defaultValue={supplier?.tin ?? ''}
                                    inputMode="numeric"
                                    maxLength={12}
                                    placeholder="123456789012"
                                    className="font-mono"
                                    hint="12 digits."
                                    error={errors.tin}
                                />

                                <TextField
                                    name="bin"
                                    label="BIN (VAT registration)"
                                    defaultValue={supplier?.bin ?? ''}
                                    maxLength={20}
                                    placeholder="000123456-0101"
                                    className="font-mono"
                                    hint="Digits, with or without hyphens."
                                    error={errors.bin}
                                />

                                <TextField
                                    name="trade_license_no"
                                    label="Trade licence no."
                                    defaultValue={
                                        supplier?.trade_license_no ?? ''
                                    }
                                    maxLength={50}
                                    error={errors.trade_license_no}
                                />

                                <FieldGroupHeading>
                                    Bank account
                                </FieldGroupHeading>

                                <TextField
                                    name="bank_name"
                                    label="Bank"
                                    defaultValue={supplier?.bank_name ?? ''}
                                    maxLength={150}
                                    error={errors.bank_name}
                                />

                                <TextField
                                    name="bank_branch"
                                    label="Branch"
                                    defaultValue={supplier?.bank_branch ?? ''}
                                    maxLength={150}
                                    error={errors.bank_branch}
                                />

                                <TextField
                                    name="bank_account_name"
                                    label="Account name"
                                    defaultValue={
                                        supplier?.bank_account_name ?? ''
                                    }
                                    maxLength={150}
                                    error={errors.bank_account_name}
                                />

                                <TextField
                                    name="bank_account_number"
                                    label="Account number"
                                    defaultValue={
                                        supplier?.bank_account_number ?? ''
                                    }
                                    maxLength={30}
                                    className="font-mono"
                                    error={errors.bank_account_number}
                                />

                                <TextField
                                    name="bank_routing_number"
                                    label="Routing number"
                                    defaultValue={
                                        supplier?.bank_routing_number ?? ''
                                    }
                                    inputMode="numeric"
                                    maxLength={20}
                                    className="font-mono"
                                    hint="Digits only."
                                    error={errors.bank_routing_number}
                                />

                                <div className="field-wide grid content-start gap-1.5">
                                    <Label htmlFor="description">
                                        Description
                                    </Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        defaultValue={
                                            supplier?.description ?? ''
                                        }
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
                                addAnother={supplier === undefined}
                                onIntent={setIntent}
                                saveTestId="save-supplier"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
