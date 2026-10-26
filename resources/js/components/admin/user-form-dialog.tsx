import { Form } from '@inertiajs/react';
import { Check, LoaderCircle, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { useCallback, useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import FormDialogFooter from '@/components/shared/form-dialog-footer';
import PasswordPolicyChecklist from '@/components/shared/password-policy-checklist';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { AvailabilityState } from '@/hooks/use-availability';
import { useAvailability } from '@/hooks/use-availability';
import { useFormDialog } from '@/hooks/use-form-dialog';
import { useInitials } from '@/hooks/use-initials';
import { RECORD_STATUS } from '@/lib/enums';
import type { StatusOption, UserListItem } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

/** Kept in step with `ProfileValidationRules::usernameRules()`. */
const USERNAME_PATTERN = /^[A-Za-z0-9][A-Za-z0-9._-]{2,29}$/;

/**
 * Create or edit a user in a modal — the form-modal standard in
 * ARCHITECTURE.md §8.10.
 *
 * Client-side checks are feedback only; `UserStoreRequest` and
 * `UserUpdateRequest` enforce the rules, and their errors render through the
 * same `InputError` slots. Roles and passwords of an existing user are changed
 * with their own dialogs, gated by their own permissions.
 */
export default function UserFormDialog({
    submit,
    statuses,
    roles,
    user,
    title,
    description,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    /** `RecordStatus::options()` — active/inactive. */
    statuses: StatusOption[];
    /** Assignable role names — only offered when creating. */
    roles?: string[];
    user?: UserListItem;
    title: string;
    description: string;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const close = useCallback(() => setOpen(false), []);
    const { formKey, formProps, setIntent } = useFormDialog(close);
    const isCreate = user === undefined;
    const getInitials = useInitials();

    const [username, setUsername] = useState(user?.username ?? '');
    const [email, setEmail] = useState(user?.email ?? '');
    const [password, setPassword] = useState('');
    const [usernameTouched, setUsernameTouched] = useState(false);
    const [preview, setPreview] = useState<string | null>(null);
    const [removeAvatar, setRemoveAvatar] = useState(false);
    // Held above `DialogContent`, so "Save & add another" keeps the choice.
    const [notify, setNotify] = useState(true);

    const usernameState = useAvailability('username', username, user?.id);
    const emailState = useAvailability('email', email, user?.id);

    const usernameFormat =
        usernameTouched && username !== '' && !USERNAME_PATTERN.test(username)
            ? 'Use 3–30 letters, digits, dots, dashes or underscores.'
            : undefined;

    const shownAvatar = removeAvatar ? null : (preview ?? user?.avatar_url);

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
                            <div className="flex items-center gap-4">
                                <Avatar className="size-16 overflow-hidden rounded-full">
                                    <AvatarImage
                                        src={shownAvatar ?? undefined}
                                        alt=""
                                    />
                                    <AvatarFallback className="text-lg">
                                        {getInitials(user?.name ?? 'New user')}
                                    </AvatarFallback>
                                </Avatar>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="avatar">Photo</Label>
                                    <input
                                        id="avatar"
                                        name="avatar"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        className="file-input file-input-sm"
                                        onChange={(event) => {
                                            const file =
                                                event.target.files?.[0];

                                            setRemoveAvatar(false);
                                            setPreview(
                                                file
                                                    ? URL.createObjectURL(file)
                                                    : null,
                                            );
                                        }}
                                        aria-invalid={Boolean(errors.avatar)}
                                        data-test="user-avatar"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        JPG, PNG or WebP, up to 2 MB.
                                    </p>

                                    {user !== undefined &&
                                        user.avatar_url !== null && (
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="hidden"
                                                    name="remove_avatar"
                                                    value="0"
                                                />
                                                <Checkbox
                                                    name="remove_avatar"
                                                    value="1"
                                                    checked={removeAvatar}
                                                    onChange={(event) =>
                                                        setRemoveAvatar(
                                                            event.target
                                                                .checked,
                                                        )
                                                    }
                                                />
                                                Remove the current photo
                                            </label>
                                        )}

                                    <InputError message={errors.avatar} />
                                </div>
                            </div>

                            <div className="field-grid">
                                <Field
                                    label="Full name"
                                    htmlFor="name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={user?.name}
                                        required
                                        autoFocus
                                        autoComplete="off"
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                </Field>

                                <Field
                                    label="Username"
                                    htmlFor="username"
                                    error={errors.username ?? usernameFormat}
                                    hint={
                                        <AvailabilityHint
                                            state={usernameState}
                                        />
                                    }
                                >
                                    <Input
                                        id="username"
                                        name="username"
                                        value={username}
                                        onChange={(event) =>
                                            setUsername(event.target.value)
                                        }
                                        onBlur={() => setUsernameTouched(true)}
                                        required
                                        maxLength={30}
                                        autoComplete="off"
                                        autoCapitalize="none"
                                        spellCheck={false}
                                        aria-invalid={
                                            usernameState === 'taken' ||
                                            Boolean(errors.username)
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Email address"
                                    htmlFor="email"
                                    error={errors.email}
                                    hint={
                                        <AvailabilityHint state={emailState} />
                                    }
                                >
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value={email}
                                        onChange={(event) =>
                                            setEmail(event.target.value)
                                        }
                                        required
                                        autoComplete="off"
                                        aria-invalid={
                                            emailState === 'taken' ||
                                            Boolean(errors.email)
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Phone"
                                    htmlFor="phone"
                                    error={errors.phone}
                                >
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        defaultValue={user?.phone ?? ''}
                                        maxLength={30}
                                        autoComplete="off"
                                        aria-invalid={Boolean(errors.phone)}
                                    />
                                </Field>

                                <Field
                                    label="Employee code"
                                    htmlFor="employee_code"
                                    error={errors.employee_code}
                                >
                                    <Input
                                        id="employee_code"
                                        name="employee_code"
                                        defaultValue={user?.employee_code ?? ''}
                                        maxLength={30}
                                        autoComplete="off"
                                        aria-invalid={Boolean(
                                            errors.employee_code,
                                        )}
                                    />
                                </Field>

                                <Field
                                    label="Account status"
                                    htmlFor="status"
                                    error={errors.status}
                                >
                                    <Combobox
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            user?.status ?? RECORD_STATUS.Active
                                        }
                                        options={statuses}
                                        required
                                        aria-invalid={Boolean(errors.status)}
                                        data-test="user-status"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        Inactive accounts cannot sign in.
                                    </p>
                                </Field>

                                {isCreate && (
                                    <Field
                                        label="Password"
                                        htmlFor="password"
                                        error={errors.password}
                                    >
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            value={password}
                                            onChange={(event) =>
                                                setPassword(event.target.value)
                                            }
                                            required
                                            autoComplete="new-password"
                                            aria-invalid={Boolean(
                                                errors.password,
                                            )}
                                            aria-describedby="password-policy"
                                        />

                                        <PasswordPolicyChecklist
                                            id="password-policy"
                                            password={password}
                                        />
                                    </Field>
                                )}

                                {isCreate && (
                                    <Field
                                        label="Confirm password"
                                        htmlFor="password_confirmation"
                                        error={errors.password_confirmation}
                                    >
                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            aria-invalid={Boolean(
                                                errors.password_confirmation,
                                            )}
                                        />
                                    </Field>
                                )}
                            </div>

                            {isCreate && (
                                <label className="flex cursor-pointer items-start gap-3">
                                    <Checkbox
                                        name="notify"
                                        value="1"
                                        checked={notify}
                                        onChange={(event) =>
                                            setNotify(event.target.checked)
                                        }
                                        className="mt-0.5"
                                        data-test="user-form-notify"
                                    />
                                    <span className="text-sm">
                                        Email this user their username and
                                        password.
                                        <span className="block text-xs text-base-content/60">
                                            Sent Mails keeps a copy with the
                                            password masked.
                                        </span>
                                    </span>
                                </label>
                            )}

                            {isCreate && roles !== undefined && (
                                <fieldset className="grid gap-2 rounded-box border border-base-300/70 p-4">
                                    <legend className="px-1 text-sm font-medium">
                                        Roles
                                    </legend>

                                    {roles.length === 0 && (
                                        <p className="text-sm text-base-content/60">
                                            No roles exist yet.
                                        </p>
                                    )}

                                    <div className="flex flex-wrap gap-x-6 gap-y-2">
                                        {roles.map((role) => (
                                            <label
                                                key={role}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    name="roles[]"
                                                    value={role}
                                                />
                                                <span className="font-mono text-xs">
                                                    {role}
                                                </span>
                                            </label>
                                        ))}
                                    </div>

                                    <InputError
                                        message={
                                            errors.roles ?? errors['roles.0']
                                        }
                                    />
                                </fieldset>
                            )}

                            <FormDialogFooter
                                processing={processing}
                                addAnother={isCreate}
                                onIntent={setIntent}
                                saveTestId="save-user"
                            />
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/** Label + control + hint + error, so the grid above stays readable. */
function Field({
    label,
    htmlFor,
    error,
    hint,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    hint?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="grid content-start gap-1.5">
            <div className="flex items-baseline justify-between gap-2">
                <Label htmlFor={htmlFor}>{label}</Label>
                {hint}
            </div>

            {children}

            <InputError message={error} />
        </div>
    );
}

/** Live result of the server-side uniqueness check. */
function AvailabilityHint({ state }: { state: AvailabilityState }) {
    if (state === 'idle') {
        return null;
    }

    if (state === 'checking') {
        return (
            <span className="flex items-center gap-1 text-xs text-base-content/60">
                <LoaderCircle className="size-3 animate-spin" /> Checking
            </span>
        );
    }

    if (state === 'available') {
        return (
            <span className="flex items-center gap-1 text-xs text-success">
                <Check className="size-3" /> Available
            </span>
        );
    }

    return (
        <span className="flex items-center gap-1 text-xs text-error">
            <X className="size-3" /> Already taken
        </span>
    );
}
