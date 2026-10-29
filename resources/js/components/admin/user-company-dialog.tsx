import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCallback, useState } from 'react';
import InputError from '@/components/input-error';
import FormDialogFooter from '@/components/shared/form-dialog-footer';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useFormDialog } from '@/hooks/use-form-dialog';
import type { CompanyGrantOption, UserListItem } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Replace the companies a user may work in (ARCHITECTURE.md §9.10) — the
 * counterpart of `user-role-dialog.tsx`.
 *
 * A super admin needs no grant and works in every active company; granting
 * them one changes nothing they can reach. An inactive company may be granted
 * and becomes usable when it is reactivated.
 *
 * **No "Save & add another"** — it edits one named user (§8.10).
 */
export default function UserCompanyDialog({
    submit,
    user,
    companies,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    user: UserListItem;
    companies: CompanyGrantOption[];
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const close = useCallback(() => setOpen(false), []);
    const { formKey, formProps, setIntent } = useFormDialog(close);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>

            <DialogContent>
                <DialogTitle>Companies for {user.name}</DialogTitle>
                <DialogDescription>
                    The companies whose Projects, Accounting and HRM records
                    this user can work in. With none, those modules show nothing
                    to them.
                </DialogDescription>

                <Form
                    key={formKey}
                    {...submit}
                    {...formProps}
                    options={{ preserveScroll: true }}
                    className="mt-4 space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid max-h-[50vh] gap-2 overflow-y-auto pr-1">
                                {companies.length === 0 && (
                                    <p className="text-sm text-base-content/60">
                                        No companies exist yet. Add them in
                                        Admin → Companies.
                                    </p>
                                )}

                                {companies.map((company) => (
                                    <label
                                        key={company.id}
                                        className="flex items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            name="companies[]"
                                            value={company.id}
                                            defaultChecked={user.companies.includes(
                                                company.id,
                                            )}
                                            data-test={`company-${company.short_code}`}
                                        />
                                        <span className="font-mono text-xs">
                                            {company.short_code}
                                        </span>
                                        <span>{company.name}</span>
                                        {!company.is_active && (
                                            <span className="badge badge-sm badge-warning">
                                                inactive
                                            </span>
                                        )}
                                    </label>
                                ))}
                            </div>

                            <InputError
                                message={
                                    errors.companies ?? errors['companies.0']
                                }
                            />

                            <FormDialogFooter
                                processing={processing}
                                onIntent={setIntent}
                                saveTestId="save-user-companies"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
