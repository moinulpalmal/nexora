import { Form, Head, usePage } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useRef, useState } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import PasswordPolicyChecklist from '@/components/shared/password-policy-checklist';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { usePasswordPolicy } from '@/hooks/use-password-policy';
import { edit } from '@/routes/security';
import type { Auth } from '@/types';

export default function Security() {
    // Set by `EnsurePasswordIsChanged`'s flag; cleared by the save below.
    const mustChangePassword = Boolean(
        usePage<{ auth?: Auth }>().props.auth?.user?.must_change_password,
    );
    const [password, setPassword] = useState('');
    const { hint } = usePasswordPolicy();
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Security settings" />

            <h1 className="sr-only">Security settings</h1>

            <div className="space-y-6">
                {mustChangePassword && (
                    <div
                        role="alert"
                        className="alert alert-soft alert-warning"
                        data-test="must-change-password"
                    >
                        <TriangleAlert className="size-5" />
                        <div>
                            <p className="font-medium">
                                Choose your own password
                            </p>
                            <p className="text-xs">
                                Your password was set by someone else. Enter it
                                as your current password and choose a new one to
                                continue using the application.
                            </p>
                        </div>
                    </div>
                )}

                <Heading
                    variant="small"
                    title="Update password"
                    description="Ensure your account is using a long, random password to stay secure"
                />

                <Form
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    onSuccess={() => setPassword('')}
                    onError={(errors) => {
                        setPassword('');

                        if (errors.password) {
                            passwordInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPasswordInput.current?.focus();
                        }
                    }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">
                                    Current password
                                </Label>

                                <PasswordInput
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    placeholder="Current password"
                                />

                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">New password</Label>

                                <PasswordInput
                                    id="password"
                                    ref={passwordInput}
                                    name="password"
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    placeholder="New password"
                                    value={password}
                                    onChange={(event) =>
                                        setPassword(event.target.value)
                                    }
                                    passwordrules={hint}
                                    aria-describedby="password-policy"
                                />

                                <PasswordPolicyChecklist
                                    id="password-policy"
                                    password={password}
                                    className="mt-1"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>

                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    placeholder="Confirm password"
                                    passwordrules={hint}
                                />

                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-password-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security settings',
            href: edit(),
        },
    ],
};
