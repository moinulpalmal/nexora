import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCallback, useRef, useState } from 'react';
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
import type { DesignationListItem, StatusOption } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Create or edit a designation — the form-modal standard in ARCHITECTURE.md
 * §8.10, mirroring `hrm/department-form-dialog.tsx`.
 *
 * Designations are group-wide (§9.10): no company, and the code and name
 * must each be unique across every designation, deleted ones included.
 */
export default function DesignationFormDialog({
    submit,
    statuses,
    designation,
    title,
    description,
    children,
    open: openProp,
    onOpenChange,
    initialValues,
    onCreated,
    idPrefix = '',
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    designation?: DesignationListItem;
    title: string;
    description: string;
    /** The trigger; omitted when the caller controls `open`. */
    children?: ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    /** Prefills a new designation — the inline create from another form. */
    initialValues?: { code: string; name: string };
    /**
     * Called with the saved code (in capitals) after a create, so the calling
     * form can select the new designation once the reloaded options carry it.
     * Turns off "Save & add another" and keeps the page's state.
     */
    onCreated?: (code: string) => void;
    /** Keeps the field ids unique when stacked over another form. */
    idPrefix?: string;
}) {
    const [ownOpen, setOwnOpen] = useState(false);
    const isControlled = openProp !== undefined;
    const open = isControlled ? openProp : ownOpen;
    const setOpen = useCallback(
        (next: boolean) => {
            if (!isControlled) {
                setOwnOpen(next);
            }

            onOpenChange?.(next);
        },
        [isControlled, onOpenChange],
    );
    const close = useCallback(() => setOpen(false), [setOpen]);
    const { formKey, formProps, setIntent } = useFormDialog(close);
    const code = useRef(initialValues?.code ?? designation?.code ?? '');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            {children !== undefined && (
                <DialogTrigger asChild>{children}</DialogTrigger>
            )}

            <DialogContent className="max-w-4xl">
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    key={formKey}
                    {...submit}
                    {...formProps}
                    onSuccess={() => {
                        onCreated?.(code.current.trim().toUpperCase());
                        formProps.onSuccess();
                    }}
                    options={{
                        preserveScroll: true,
                        preserveState: onCreated !== undefined,
                    }}
                    className="mt-4 space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="field-grid">
                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor={`${idPrefix}code`}>
                                        Code
                                    </Label>
                                    <Input
                                        id={`${idPrefix}code`}
                                        name="code"
                                        defaultValue={
                                            designation?.code ??
                                            initialValues?.code
                                        }
                                        onChange={(event) => {
                                            code.current = event.target.value;
                                        }}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={20}
                                        placeholder="SR-OP"
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
                                    <Label htmlFor={`${idPrefix}name`}>
                                        Name
                                    </Label>
                                    <Input
                                        id={`${idPrefix}name`}
                                        name="name"
                                        defaultValue={
                                            designation?.name ??
                                            initialValues?.name
                                        }
                                        required
                                        autoComplete="off"
                                        maxLength={100}
                                        placeholder="Senior Operator"
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor={`${idPrefix}status`}>
                                        Status
                                    </Label>
                                    <Combobox
                                        id={`${idPrefix}status`}
                                        name="status"
                                        defaultValue={
                                            designation?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="designation-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive designation stays on record
                                        but is not offered for new entries.
                                    </p>
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            <FormDialogFooter
                                processing={processing}
                                addAnother={
                                    designation === undefined &&
                                    onCreated === undefined
                                }
                                onIntent={setIntent}
                                saveTestId="save-designation"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
