import { Form } from '@inertiajs/react';
import { Lock, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import QuickLoginSettingController from '@/actions/App/Http/Controllers/Admin/QuickLoginSettingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';

type Account = {
    label: string;
    username: string;
    /** `false` when no active user has this username — no button for it. */
    exists: boolean;
};

type Props = {
    enabled: boolean;
    /** Whether this environment may ever show the buttons (local or testing). */
    environmentAllows: boolean;
    environment: string;
    accounts: Account[];
    /** Who last switched it, or `null` when it has never been changed. */
    updatedBy: string | null;
    updatedAt: string | null;
};

/**
 * The login page's quick-login switch. On — and only in a `local` or `testing`
 * environment — `/login` shows one button per seeded account below, each
 * submitting the real login form with the shared password `password`. The
 * accounts are fixed in code (the demo seeder); this tab only shows them.
 */
export default function QuickLoginPanel({
    enabled,
    environmentAllows,
    environment,
    accounts,
    updatedBy,
    updatedAt,
}: Props) {
    const canUpdate = useCan('admin.quick-login.update');
    const [on, setOn] = useState(enabled);

    return (
        <Form
            {...QuickLoginSettingController.update.form()}
            transform={() => ({ enabled: on })}
            options={{ preserveScroll: true }}
            className="max-w-xl space-y-6"
        >
            {({ processing, errors }) => (
                <>
                    {environmentAllows ? (
                        <div role="alert" className="alert alert-soft alert-warning">
                            <TriangleAlert className="size-5" />
                            <span className="text-xs">
                                While on, anyone who can open the login page can
                                sign in as these accounts — a super admin among
                                them — with one click. Switching it off hides the
                                buttons; it does not change any password.
                            </span>
                        </div>
                    ) : (
                        <div role="note" className="alert alert-soft alert-info">
                            <Lock className="size-5" />
                            <span className="text-xs">
                                This is the <code>{environment}</code>{' '}
                                environment, so the buttons never appear,
                                whatever this switch says. They only render in
                                local and testing.
                            </span>
                        </div>
                    )}

                    <label className="flex items-center gap-3">
                        <input
                            type="checkbox"
                            className="toggle toggle-warning"
                            checked={on}
                            disabled={!canUpdate}
                            onChange={(event) => setOn(event.target.checked)}
                            data-test="quick-login-enabled"
                        />
                        <span className="text-sm font-medium">
                            {on ? 'Quick login is on' : 'Quick login is off'}
                        </span>
                    </label>

                    <div className="overflow-x-auto">
                        <table className="table table-sm">
                            <thead>
                                <tr>
                                    <th>Button</th>
                                    <th>Username</th>
                                    <th>Account</th>
                                </tr>
                            </thead>
                            <tbody>
                                {accounts.map((account) => (
                                    <tr key={account.username}>
                                        <td>{account.label}</td>
                                        <td className="font-mono">
                                            {account.username}
                                        </td>
                                        <td>
                                            {account.exists ? (
                                                <span className="badge badge-soft badge-sm badge-success">
                                                    Active
                                                </span>
                                            ) : (
                                                <span className="badge badge-soft badge-sm">
                                                    Missing or inactive — no button
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {updatedBy !== null && updatedAt !== null && (
                        <p className="text-xs text-base-content/60">
                            Last changed by {updatedBy} on{' '}
                            {new Date(updatedAt).toLocaleString()}.
                        </p>
                    )}

                    <InputError message={errors.enabled} />

                    {canUpdate && (
                        <Button
                            type="submit"
                            disabled={processing || on === enabled}
                            data-test="save-quick-login"
                        >
                            {processing ? 'Saving…' : 'Save'}
                        </Button>
                    )}
                </>
            )}
        </Form>
    );
}
