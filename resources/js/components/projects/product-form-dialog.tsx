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
    ProductCategoryOption,
    ProductListItem,
    StatusOption,
} from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Only the active categories, plus the one the product already holds — as
 * `projects/supplier-form-dialog.tsx`'s `choosable()`.
 */
function choosable(options: ProductCategoryOption[], current?: number) {
    return options
        .filter(
            (option) =>
                option.status === RECORD_STATUS.Active ||
                option.value === current,
        )
        .map(({ value, label, hint }) => ({ value, label, hint }));
}

/**
 * Create or edit a product — the form-modal standard in ARCHITECTURE.md §8.10,
 * mirroring `projects/product-category-form-dialog.tsx` with the category
 * combobox of `projects/certification-form-dialog.tsx`.
 *
 * **No company field** (§9.10): the product belongs to the company selected in
 * the header, which the server stamps, and the categories offered are that
 * company's. The code and name must each be unique within the company,
 * deleted products included.
 */
export default function ProductFormDialog({
    submit,
    statuses,
    categories,
    product,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    /** Every product category of the company, inactive ones marked. */
    categories: ProductCategoryOption[];
    product?: ProductListItem;
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
                                        defaultValue={product?.code}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        maxLength={20}
                                        placeholder="CEM-50KG"
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
                                        defaultValue={product?.name}
                                        required
                                        autoComplete="off"
                                        maxLength={150}
                                        placeholder="Portland Cement 50 kg"
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="product_category_id">
                                        Category
                                    </Label>
                                    <Combobox
                                        id="product_category_id"
                                        name="product_category_id"
                                        defaultValue={
                                            product?.product_category_id
                                        }
                                        options={choosable(
                                            categories,
                                            product?.product_category_id,
                                        )}
                                        required
                                        aria-invalid={Boolean(
                                            errors.product_category_id,
                                        )}
                                        data-test="product-category"
                                    />
                                    <InputError
                                        message={errors.product_category_id}
                                    />
                                </div>

                                <div className="grid content-start gap-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            product?.status ??
                                            RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="product-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        An inactive product stays on record but
                                        is not offered for new entries.
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
                                            product?.description ?? ''
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
                                addAnother={product === undefined}
                                onIntent={setIntent}
                                saveTestId="save-product"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
