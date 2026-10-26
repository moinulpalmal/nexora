import type { FormComponentRef } from '@inertiajs/core';
import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import DevQuickLogin from '@/components/dev-quick-login';
import type { QuickLoginAccount } from '@/components/dev-quick-login';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    /** Empty unless Application Settings → Quick Login is on, in local/testing. */
    quickLoginAccounts: QuickLoginAccount[];
};

export default function Login({ status, canResetPassword, quickLoginAccounts }: Props) {
    const formRef = useRef<FormComponentRef>(null);

    /** Fill the (uncontrolled) inputs and submit the real Fortify form. */
    const quickLogin = (username: string) => {
        const login = document.getElementById('login') as HTMLInputElement | null;
        const password = document.getElementById('password') as HTMLInputElement | null;

        if (!login || !password) {
            return;
        }

        login.value = username;
        password.value = 'password';
        formRef.current?.submit();
    };

    return (
        <>
            <Head title="Log In" />

            <Form
                {...store.form()}
                ref={formRef}
                resetOnError={['password']}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            {/* One field for either identifier — resolved by
                                `Fortify::authenticateUsing()`. */}
                            <Label htmlFor="login">Email or username</Label>
                            <Input
                                id="login"
                                type="text"
                                name="login"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="username"
                                autoCapitalize="none"
                                spellCheck={false}
                                placeholder="email@example.com or username"
                            />
                            <InputError message={errors.login} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Password</Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="ml-auto text-sm"
                                        tabIndex={5}
                                    >
                                        Forgot your password?
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                            />
                            <Label htmlFor="remember">Remember me</Label>
                        </div>

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Log in
                        </Button>
                    </div>
                )}
            </Form>

            {quickLoginAccounts.length > 0 && (
                <DevQuickLogin accounts={quickLoginAccounts} onSelect={quickLogin} />
            )}

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-success">
                    {status}
                </div>
            )}
        </>
    );
}

/**
 * No `title` — the app name is the heading here, and the form itself already
 * says what the screen is.
 */
Login.layout = {
    description: 'Enter your email or username and password to log in',
};
