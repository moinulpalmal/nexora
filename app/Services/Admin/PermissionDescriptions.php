<?php

namespace App\Services\Admin;

use Database\Seeders\Admin\RolePermissionSeeder;
use Illuminate\Support\Str;

/**
 * Plain-language help for every permission, shown on the role permission picker
 * and the permissions catalogue.
 *
 * The copy lives in code beside the catalogue in {@see RolePermissionSeeder}, and
 * `PermissionDescriptionsTest` fails when a catalogued permission or module has
 * no entry here — so adding a permission means adding its sentence too. A
 * permission that exists in the database without a catalogue entry (granted by
 * hand on a live system) gets a sentence generated from its name, flagged as not
 * curated.
 */
class PermissionDescriptions
{
    protected const string SUPER_ADMIN_ONLY = 'Super admin only by default.';

    /**
     * Curated help per permission name.
     *
     * Every entry names the screen it applies to (`where`), so the hover card
     * can always say where to find it.
     *
     * @var array<string, array{summary: string, note?: string, where: string}>
     */
    protected const array PERMISSIONS = [
        'admin.users.view' => ['summary' => 'Open the Users list (active and deleted tabs) and search it.', 'where' => 'Admin → Users'],
        'admin.users.create' => ['summary' => 'Add a new user from the New user dialog.', 'note' => 'The new user is emailed their sign-in details.', 'where' => 'Admin → Users'],
        'admin.users.update' => ['summary' => "Edit a user's details and activate or deactivate them.", 'note' => 'Does not include changing their roles or password.', 'where' => 'Admin → Users'],
        'admin.users.delete' => ['summary' => 'Move a user to Deleted (soft delete); they can no longer sign in.', 'note' => 'Reversible with restore.', 'where' => 'Admin → Users'],
        'admin.users.restore' => ['summary' => 'Bring a user back from Deleted.', 'where' => 'Admin → Users → Deleted'],
        'admin.users.force-delete' => ['summary' => 'Permanently delete a user from Deleted.', 'note' => 'Cannot be undone.', 'where' => 'Admin → Users → Deleted'],
        'admin.users.reset-password' => ['summary' => 'Set a new password for another user.', 'where' => 'Admin → Users'],
        'admin.users.assign-roles' => ['summary' => 'Change which roles another user holds.', 'note' => 'Widens or narrows their access; guarded against self-escalation.', 'where' => 'Admin → Users'],

        'admin.roles.view' => ['summary' => 'See the roles list with user and permission counts.', 'where' => 'Admin → Roles'],
        'admin.roles.create' => ['summary' => 'Create a new role and choose its permissions.', 'where' => 'Admin → Roles'],
        'admin.roles.update' => ['summary' => 'Rename a role and change its permissions.', 'note' => 'Affects every user holding the role.', 'where' => 'Admin → Roles'],
        'admin.roles.delete' => ['summary' => 'Delete a role that no user holds.', 'note' => 'The super-admin role can never be deleted.', 'where' => 'Admin → Roles'],

        'admin.permissions.view' => ['summary' => 'See every permission, what it allows and which roles hold it.', 'where' => 'Admin → Permissions'],

        'admin.audit-logs.view' => ['summary' => 'Read the audit trail: who changed what, when, with the old and new values.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Audit Log'],

        'admin.mail-settings.view' => ['summary' => 'See the outgoing mail server settings.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Application Settings → Mail Server'],
        'admin.mail-settings.update' => ['summary' => 'Change the outgoing mail server and send a test mail.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Application Settings → Mail Server'],
        'admin.quick-login.view' => ['summary' => 'See whether development quick login is switched on.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Application Settings → Quick Login'],
        'admin.quick-login.update' => ['summary' => 'Switch development quick login on or off.', 'note' => self::SUPER_ADMIN_ONLY.' Never shown outside local and testing environments.', 'where' => 'Admin → Application Settings → Quick Login'],
        'admin.sent-mails.view' => ['summary' => 'See every mail the application sent, with its status and a preview.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Sent Mails'],
    ];

    /**
     * Curated help per module — the first segment of a permission name.
     *
     * @var array<string, array{summary: string, where: string}>
     */
    protected const array MODULES = [
        'admin' => ['summary' => 'Users, roles, the audit trail and application-wide settings.', 'where' => 'Admin menu'],
    ];

    /**
     * Sentences for the common actions, used when a permission has no curated entry.
     *
     * @var array<string, string>
     */
    protected const array ACTION_SENTENCES = [
        'view' => 'Open and read %s.',
        'create' => 'Add new %s.',
        'update' => 'Edit %s.',
        'delete' => 'Delete %s.',
        'import' => 'Import %s from a file.',
        'export' => 'Export %s.',
        'restore' => 'Restore deleted %s.',
        'force-delete' => 'Permanently delete %s.',
    ];

    /**
     * The help for one permission, curated when there is an entry and generated from its name otherwise.
     *
     * @return array{summary: string, note: ?string, where: ?string, curated: bool}
     */
    public function for(string $name): array
    {
        $entry = self::PERMISSIONS[$name] ?? null;

        if ($entry !== null) {
            return [
                'summary' => $entry['summary'],
                'note' => $entry['note'] ?? null,
                'where' => $entry['where'],
                'curated' => true,
            ];
        }

        [, $resource, $action] = array_pad(explode('.', $name, 3), 3, '');
        $subject = Str::of($resource)->replace('-', ' ')->lower()->value() ?: $name;
        $sentence = self::ACTION_SENTENCES[$action] ?? null;

        return [
            'summary' => $sentence !== null
                ? sprintf($sentence, $subject)
                : Str::ucfirst(Str::of($action)->replace('-', ' ')->value()).' '.$subject.'.',
            'note' => 'Not in the catalogue — no curated description.',
            'where' => null,
            'curated' => false,
        ];
    }

    /**
     * The help for one module, with a generic fallback for a module the map does not know.
     *
     * @return array{summary: string, where: ?string}
     */
    public function forModule(string $module): array
    {
        return self::MODULES[$module] ?? [
            'summary' => 'Permissions for the '.Str::of($module)->replace('-', ' ')->value().' module.',
            'where' => null,
        ];
    }

    /**
     * Every permission name with a curated entry.
     *
     * @return list<string>
     */
    public function curatedNames(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    /**
     * Every module with a curated entry.
     *
     * @return list<string>
     */
    public function curatedModules(): array
    {
        return array_keys(self::MODULES);
    }
}
