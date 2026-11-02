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
import type { CountryListItem, StatusOption } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Create or edit a country — the form-modal standard in ARCHITECTURE.md
 * §8.10, mirroring `hrm/designation-form-dialog.tsx` without its inline-create
 * mode (no other form creates a country yet).
 *
 * The name and both ISO codes must each be unique across every country,
 * deleted ones included; the other codes are optional and may repeat.
 */
export default function CountryFormDialog({
    submit,
    statuses,
    country,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    country?: CountryListItem;
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
                                        defaultValue={country?.name}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={100}
                                        placeholder="Bangladesh"
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="iso2">ISO code (2)</Label>
                                    <Input
                                        id="iso2"
                                        name="iso2"
                                        defaultValue={country?.iso2}
                                        required
                                        autoComplete="off"
                                        maxLength={2}
                                        placeholder="BD"
                                        className="font-mono uppercase"
                                        aria-invalid={Boolean(errors.iso2)}
                                    />
                                    <InputError message={errors.iso2} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="iso3">ISO code (3)</Label>
                                    <Input
                                        id="iso3"
                                        name="iso3"
                                        defaultValue={country?.iso3}
                                        required
                                        autoComplete="off"
                                        maxLength={3}
                                        placeholder="BGD"
                                        className="font-mono uppercase"
                                        aria-invalid={Boolean(errors.iso3)}
                                    />
                                    <InputError message={errors.iso3} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="numeric_code">
                                        Numeric code
                                    </Label>
                                    <Input
                                        id="numeric_code"
                                        name="numeric_code"
                                        defaultValue={
                                            country?.numeric_code ?? undefined
                                        }
                                        autoComplete="off"
                                        inputMode="numeric"
                                        maxLength={3}
                                        placeholder="050"
                                        className="font-mono"
                                        aria-invalid={Boolean(
                                            errors.numeric_code,
                                        )}
                                    />
                                    <InputError message={errors.numeric_code} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="dial_code">Dial code</Label>
                                    <Input
                                        id="dial_code"
                                        name="dial_code"
                                        defaultValue={
                                            country?.dial_code ?? undefined
                                        }
                                        autoComplete="off"
                                        maxLength={10}
                                        placeholder="+880"
                                        className="font-mono"
                                        aria-invalid={Boolean(errors.dial_code)}
                                    />
                                    <InputError message={errors.dial_code} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="currency_code">
                                        Currency
                                    </Label>
                                    <Input
                                        id="currency_code"
                                        name="currency_code"
                                        defaultValue={
                                            country?.currency_code ?? undefined
                                        }
                                        autoComplete="off"
                                        maxLength={3}
                                        placeholder="BDT"
                                        className="font-mono uppercase"
                                        aria-invalid={Boolean(
                                            errors.currency_code,
                                        )}
                                    />
                                    <InputError
                                        message={errors.currency_code}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="nationality">
                                        Nationality
                                    </Label>
                                    <Input
                                        id="nationality"
                                        name="nationality"
                                        defaultValue={
                                            country?.nationality ?? undefined
                                        }
                                        autoComplete="off"
                                        maxLength={100}
                                        placeholder="Bangladeshi"
                                        aria-invalid={Boolean(
                                            errors.nationality,
                                        )}
                                    />
                                    <InputError message={errors.nationality} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            country?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="country-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive country stays on record but
                                        is not offered for new entries.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            <FormDialogFooter
                                processing={processing}
                                addAnother={country === undefined}
                                onIntent={setIntent}
                                saveTestId="save-country"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
