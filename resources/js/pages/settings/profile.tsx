import { Form, Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile() {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;
    const getInitials = useInitials();

    const [username, setUsername] = useState(user.username);
    const [email, setEmail] = useState(user.email);
    const [preview, setPreview] = useState<string | null>(null);
    const [removeAvatar, setRemoveAvatar] = useState(false);

    // The two identifiers that sign in; changing either asks for the current
    // password (ProfileUpdateRequest), so the field only appears when needed.
    const changesSignIn =
        username.trim().toLowerCase() !== user.username ||
        email.trim().toLowerCase() !== user.email;

    const shownAvatar = removeAvatar ? null : (preview ?? user.avatar_url);

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Your name, photo and how you sign in"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['current_password', 'avatar']}
                    onSuccess={() => {
                        setPreview(null);
                        setRemoveAvatar(false);
                    }}
                    className="space-y-6"
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
                                        {getInitials(user.name)}
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
                                        data-test="profile-avatar"
                                    />
                                    <p className="text-xs text-base-content/60">
                                        JPG, PNG or WebP, up to 2 MB.
                                    </p>

                                    {user.avatar_url !== null && (
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
                                                        event.target.checked,
                                                    )
                                                }
                                            />
                                            Remove my photo
                                        </label>
                                    )}

                                    <InputError message={errors.avatar} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    defaultValue={user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    defaultValue={user.phone ?? ''}
                                    name="phone"
                                    maxLength={30}
                                    autoComplete="tel"
                                    aria-invalid={Boolean(errors.phone)}
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="username">Username</Label>
                                <Input
                                    id="username"
                                    name="username"
                                    value={username}
                                    onChange={(event) =>
                                        setUsername(event.target.value)
                                    }
                                    required
                                    maxLength={30}
                                    autoComplete="username"
                                    autoCapitalize="none"
                                    spellCheck={false}
                                    aria-invalid={Boolean(errors.username)}
                                />
                                <InputError message={errors.username} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={email}
                                    onChange={(event) =>
                                        setEmail(event.target.value)
                                    }
                                    required
                                    autoComplete="email"
                                    aria-invalid={Boolean(errors.email)}
                                />
                                <InputError message={errors.email} />
                            </div>

                            {changesSignIn && (
                                <div
                                    className="grid gap-2 rounded-box border border-warning/40 p-4"
                                    data-test="profile-current-password"
                                >
                                    <Label htmlFor="current_password">
                                        Current password
                                    </Label>
                                    <PasswordInput
                                        id="current_password"
                                        name="current_password"
                                        required
                                        autoComplete="current-password"
                                        aria-invalid={Boolean(
                                            errors.current_password,
                                        )}
                                    />
                                    <p className="text-xs text-base-content/60">
                                        You sign in with your username or email,
                                        so changing either needs your password.
                                    </p>
                                    <InputError
                                        message={errors.current_password}
                                    />
                                </div>
                            )}

                            <dl className="grid gap-1 text-sm text-base-content/70">
                                <div className="flex gap-2">
                                    <dt className="font-medium">
                                        Employee code:
                                    </dt>
                                    <dd>{user.employee_code ?? '—'}</dd>
                                </div>
                                <p className="text-xs text-base-content/60">
                                    Your employee code and roles are managed by
                                    an administrator.
                                </p>
                            </dl>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
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

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
