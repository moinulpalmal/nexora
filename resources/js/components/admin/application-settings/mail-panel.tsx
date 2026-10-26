import { Form } from '@inertiajs/react';
import { Info, Send, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import MailSettingController from '@/actions/App/Http/Controllers/Admin/MailSettingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCan } from '@/hooks/use-can';
import { MAIL_TRANSPORT } from '@/lib/enums';
import type { StatusOption } from '@/types';

type Props = {
    transport: string;
    host: string | null;
    port: number | null;
    encryption: string;
    username: string | null;
    /** Whether a password is stored — the password itself never reaches the browser. */
    passwordSet: boolean;
    /** A password is stored but this server's `APP_KEY` cannot decrypt it. */
    passwordUnreadable: boolean;
    from_address: string | null;
    from_name: string | null;
    /** Whether mail can be sent at all; there is no `.env` fallback. */
    complete: boolean;
    transports: StatusOption[];
    encryptions: StatusOption[];
    /** The acting administrator's own address, where the test mail goes. */
    testRecipient: string | null;
    updatedBy: string | null;
    updatedAt: string | null;
};

/**
 * The outgoing mail server and sender, moved out of `.env`. Every mail the
 * application sends — sign-in details, password-reset links, the test mail —
 * goes out through what is saved here, from the next mail on, with no restart.
 *
 * **Under Log only the server fields stay posted** (read-only, not disabled), so
 * switching back to SMTP finds them as they were. The password is write-only:
 * leaving it blank keeps the stored one.
 */
export default function MailPanel({
    transport,
    host,
    port,
    encryption,
    username,
    passwordSet,
    passwordUnreadable,
    from_address,
    from_name,
    complete,
    transports,
    encryptions,
    testRecipient,
    updatedBy,
    updatedAt,
}: Props) {
    const canUpdate = useCan('admin.mail-settings.update');
    const [selectedTransport, setSelectedTransport] = useState(transport);
    const logOnly = selectedTransport === MAIL_TRANSPORT.Log;
    const serverReadOnly = !canUpdate || logOnly;

    return (
        <div className="max-w-xl space-y-6">
            {!complete && (
                <div
                    role="alert"
                    className="alert alert-soft alert-warning"
                    data-test="mail-not-configured"
                >
                    <TriangleAlert className="size-5" />
                    <span className="text-sm">
                        Mail is not configured. No mail can be sent — sign-in
                        details and password-reset links fail until the server
                        and sender are saved here.
                    </span>
                </div>
            )}

            {passwordUnreadable && (
                <div
                    role="alert"
                    className="alert alert-soft alert-warning"
                    data-test="mail-password-unreadable"
                >
                    <TriangleAlert className="size-5" />
                    <span className="text-sm">
                        The saved password cannot be read: it was saved under a
                        different <code>APP_KEY</code>, usually because the
                        database was copied from another machine. Enter the
                        password again and save. Until then, mail fails.
                    </span>
                </div>
            )}

            <div role="note" className="alert alert-soft">
                <Info className="size-5" />
                <span className="text-xs">
                    Every mail the application sends uses these settings, from
                    the next mail on. <strong>Log only</strong> writes mail to{' '}
                    <code>storage/logs/mail.log</code> on the server instead of
                    sending it. Either way, every mail appears in Sent Mails.
                </span>
            </div>

            <Form
                {...MailSettingController.update.form()}
                options={{ preserveScroll: true }}
                resetOnSuccess={['password']}
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1.5">
                            <Label htmlFor="transport">Send mail by</Label>

                            <select
                                id="transport"
                                name="transport"
                                value={selectedTransport}
                                disabled={!canUpdate}
                                onChange={(event) =>
                                    setSelectedTransport(event.target.value)
                                }
                                className="select-bordered select"
                                data-test="mail-transport"
                            >
                                {transports.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>

                            <InputError message={errors.transport} />
                        </div>

                        <fieldset
                            className={`grid gap-4 ${logOnly ? 'opacity-60' : ''}`}
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label htmlFor="host">SMTP server</Label>

                                    <Input
                                        id="host"
                                        name="host"
                                        type="text"
                                        placeholder="mail.example.com"
                                        defaultValue={host ?? ''}
                                        readOnly={serverReadOnly}
                                        aria-invalid={Boolean(errors.host)}
                                        data-test="mail-host"
                                    />

                                    <InputError message={errors.host} />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="port">Port</Label>

                                    <Input
                                        id="port"
                                        name="port"
                                        type="number"
                                        min={1}
                                        max={65535}
                                        placeholder="465"
                                        defaultValue={port ?? ''}
                                        readOnly={serverReadOnly}
                                        aria-invalid={Boolean(errors.port)}
                                        data-test="mail-port"
                                    />

                                    <InputError message={errors.port} />
                                </div>
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="encryption">Encryption</Label>

                                {/* A read-only select still posts: `disabled` would drop it. */}
                                <select
                                    id="encryption"
                                    name="encryption"
                                    defaultValue={encryption}
                                    className={`select-bordered select ${serverReadOnly ? 'pointer-events-none' : ''}`}
                                    aria-readonly={serverReadOnly}
                                    tabIndex={serverReadOnly ? -1 : undefined}
                                    data-test="mail-encryption"
                                >
                                    {encryptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>

                                <InputError message={errors.encryption} />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="username">Username</Label>

                                    <Input
                                        id="username"
                                        name="username"
                                        type="text"
                                        autoComplete="off"
                                        defaultValue={username ?? ''}
                                        readOnly={serverReadOnly}
                                        aria-invalid={Boolean(errors.username)}
                                        data-test="mail-username"
                                    />

                                    <InputError message={errors.username} />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="password">Password</Label>

                                    <Input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder={
                                            passwordSet
                                                ? '•••••••• (leave blank to keep)'
                                                : 'Not set'
                                        }
                                        readOnly={serverReadOnly}
                                        aria-invalid={Boolean(errors.password)}
                                        data-test="mail-password"
                                    />

                                    <InputError message={errors.password} />
                                </div>
                            </div>
                        </fieldset>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="grid gap-1.5">
                                <Label htmlFor="from_address">
                                    Sender address
                                </Label>

                                <Input
                                    id="from_address"
                                    name="from_address"
                                    type="email"
                                    required
                                    defaultValue={from_address ?? ''}
                                    readOnly={!canUpdate}
                                    aria-invalid={Boolean(errors.from_address)}
                                    data-test="mail-from-address"
                                />

                                <InputError message={errors.from_address} />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="from_name">Sender name</Label>

                                <Input
                                    id="from_name"
                                    name="from_name"
                                    type="text"
                                    required
                                    defaultValue={from_name ?? ''}
                                    readOnly={!canUpdate}
                                    aria-invalid={Boolean(errors.from_name)}
                                    data-test="mail-from-name"
                                />

                                <InputError message={errors.from_name} />
                            </div>
                        </div>

                        {updatedBy !== null && updatedAt !== null && (
                            <p className="text-xs text-base-content/60">
                                Last changed by {updatedBy} on{' '}
                                {new Date(updatedAt).toLocaleString()}.
                            </p>
                        )}

                        {canUpdate && (
                            <Button
                                type="submit"
                                disabled={processing}
                                data-test="save-mail"
                            >
                                {processing ? 'Saving…' : 'Save'}
                            </Button>
                        )}
                    </>
                )}
            </Form>

            {canUpdate && (
                <Form
                    {...MailSettingController.test.form()}
                    options={{ preserveScroll: true }}
                    className="flex flex-wrap items-center gap-3 border-t border-base-300 pt-6"
                >
                    {({ processing }) => (
                        <>
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={
                                    processing ||
                                    !complete ||
                                    testRecipient === null
                                }
                                data-test="send-test-mail"
                            >
                                <Send />
                                {processing ? 'Sending…' : 'Send test mail'}
                            </Button>

                            <span className="text-xs text-base-content/60">
                                {testRecipient === null
                                    ? 'Your account has no email address to send a test to.'
                                    : `Sends a test mail to ${testRecipient} with the saved settings — save first.`}
                            </span>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
}
