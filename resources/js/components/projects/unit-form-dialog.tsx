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
import type { StatusOption, UnitListItem } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/** Decimal places a quantity may take, 0–6, as `UnitRequest` allows. */
const DECIMAL_OPTIONS = [0, 1, 2, 3, 4, 5, 6].map((places) => ({
    value: String(places),
    label: String(places),
}));

/**
 * Create or edit a unit of measure — the form-modal standard in
 * ARCHITECTURE.md §8.10, mirroring `projects/supplier-category-form-dialog.tsx`
 * plus a symbol and the decimal places.
 *
 * Units are group-wide (§9.10): no company, and the code and name must each
 * be unique across every unit, deleted ones included.
 */
export default function UnitFormDialog({
    submit,
    statuses,
    unit,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    unit?: UnitListItem;
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
                                        defaultValue={unit?.code}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={20}
                                        placeholder="KG"
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
                                        defaultValue={unit?.name}
                                        required
                                        autoComplete="off"
                                        maxLength={100}
                                        placeholder="Kilogram"
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="symbol">Symbol</Label>
                                    <Input
                                        id="symbol"
                                        name="symbol"
                                        defaultValue={unit?.symbol ?? ''}
                                        autoComplete="off"
                                        maxLength={10}
                                        placeholder="kg"
                                        aria-invalid={Boolean(errors.symbol)}
                                    />
                                    <p className="text-xs text-base-content/60">
                                        Optional. How the unit is printed, kept
                                        exactly as typed.
                                    </p>
                                    <InputError message={errors.symbol} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="decimal_places">
                                        Decimal places
                                    </Label>
                                    <Combobox
                                        id="decimal_places"
                                        name="decimal_places"
                                        defaultValue={String(
                                            unit?.decimal_places ?? 2,
                                        )}
                                        options={DECIMAL_OPTIONS}
                                        required
                                        aria-invalid={Boolean(
                                            errors.decimal_places,
                                        )}
                                        data-test="unit-decimal-places"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        How many decimals a quantity in this
                                        unit takes — 0 for pieces, 3 for
                                        kilograms.
                                    </p>
                                    <InputError
                                        message={errors.decimal_places}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            unit?.status ?? RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="unit-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive unit stays on record but is
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
                                        defaultValue={unit?.description ?? ''}
                                        rows={2}
                                        maxLength={255}
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
                                addAnother={unit === undefined}
                                onIntent={setIntent}
                                saveTestId="save-unit"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
