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
import type {
    CertificationCategoryOption,
    CertificationListItem,
    StatusOption,
} from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Only the active categories, plus the one the certification already holds —
 * as `projects/supplier-form-dialog.tsx`'s `choosable()`.
 */
function choosable(options: CertificationCategoryOption[], current?: number) {
    return options
        .filter(
            (option) =>
                option.status === RECORD_STATUS.Active ||
                option.value === current,
        )
        .map(({ value, label, hint }) => ({ value, label, hint }));
}

/**
 * Create or edit a certification — the form-modal standard in ARCHITECTURE.md
 * §8.10, mirroring `projects/tender-type-form-dialog.tsx` with the category
 * combobox and description of `projects/supplier-form-dialog.tsx`.
 *
 * Certifications are group-wide (§9.10): no company. The code is unique
 * across every certification, the name within its version, deleted ones
 * included.
 */
export default function CertificationFormDialog({
    submit,
    statuses,
    categories,
    certification,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    /** Every certification category, inactive ones marked. */
    categories: CertificationCategoryOption[];
    certification?: CertificationListItem;
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
                                        defaultValue={certification?.code}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={30}
                                        placeholder="ISO9001-2015"
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
                                        defaultValue={certification?.name}
                                        required
                                        autoComplete="off"
                                        maxLength={150}
                                        placeholder="ISO 9001 Quality Management Systems"
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="certification_category_id">
                                        Category
                                    </Label>
                                    <Combobox
                                        id="certification_category_id"
                                        name="certification_category_id"
                                        defaultValue={
                                            certification?.certification_category_id
                                        }
                                        options={choosable(
                                            categories,
                                            certification?.certification_category_id,
                                        )}
                                        required
                                        aria-invalid={Boolean(
                                            errors.certification_category_id,
                                        )}
                                        data-test="certification-category"
                                    />
                                    <InputError
                                        message={
                                            errors.certification_category_id
                                        }
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="issuing_body">
                                        Issuing body
                                    </Label>
                                    <Input
                                        id="issuing_body"
                                        name="issuing_body"
                                        defaultValue={
                                            certification?.issuing_body ?? ''
                                        }
                                        autoComplete="off"
                                        maxLength={150}
                                        placeholder="ISO"
                                        aria-invalid={Boolean(
                                            errors.issuing_body,
                                        )}
                                    />
                                    <InputError message={errors.issuing_body} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="version">
                                        Version / edition
                                    </Label>
                                    <Input
                                        id="version"
                                        name="version"
                                        defaultValue={
                                            certification?.version ?? ''
                                        }
                                        autoComplete="off"
                                        maxLength={20}
                                        placeholder="2015"
                                        aria-invalid={Boolean(errors.version)}
                                    />
                                    <p className="text-xs text-base-content/60">
                                        Leave blank for a mark with no edition,
                                        such as CE. One name may repeat across
                                        editions.
                                    </p>
                                    <InputError message={errors.version} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            certification?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="certification-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive certification stays on
                                        record but is not offered for new
                                        entries.
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
                                        defaultValue={
                                            certification?.description ?? ''
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
                                addAnother={certification === undefined}
                                onIntent={setIntent}
                                saveTestId="save-certification"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
