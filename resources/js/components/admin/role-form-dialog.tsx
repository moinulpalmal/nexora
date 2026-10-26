import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCallback, useState } from 'react';
import PermissionPicker from '@/components/admin/permission-picker';
import InputError from '@/components/input-error';
import FormDialogFooter from '@/components/shared/form-dialog-footer';
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
import type { ModuleHelpMap, PermissionGroups, RoleListItem } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Create or edit a role: its name and the permissions it carries, picked from
 * the catalogue grouped by module (hover a row for what it allows).
 *
 * The picker scrolls inside the panel (`scrollBody`), because a catalogue that
 * grows with every module would otherwise push the footer off screen.
 */
export default function RoleFormDialog({
    submit,
    permissions,
    moduleHelp,
    role,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    permissions: PermissionGroups;
    moduleHelp: ModuleHelpMap;
    role?: RoleListItem;
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
                            <div className="grid max-w-md gap-1.5">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={role?.name}
                                    required
                                    autoFocus
                                    autoComplete="off"
                                    placeholder="data-entry"
                                    aria-invalid={Boolean(errors.name)}
                                />

                                <p className="text-xs text-base-content/60">
                                    Lowercase kebab-case. Code checks
                                    permissions, never role names — a role is
                                    just a bundle.
                                </p>

                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label>Permissions</Label>

                                <div className="max-h-[50vh] overflow-y-auto pr-1">
                                    <PermissionPicker
                                        groups={permissions}
                                        moduleHelp={moduleHelp}
                                        defaultSelected={
                                            role?.permissions ?? []
                                        }
                                    />
                                </div>

                                <InputError
                                    message={
                                        errors.permissions ??
                                        errors['permissions.0']
                                    }
                                />
                            </div>

                            <FormDialogFooter
                                processing={processing}
                                addAnother={role === undefined}
                                onIntent={setIntent}
                                saveTestId="save-role"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
