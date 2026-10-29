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
        'admin.users.assign-companies' => ['summary' => 'Change which companies another user may work in.', 'note' => 'Decides whose Projects, Accounting and HRM records they can reach. Nobody changes their own.', 'where' => 'Admin → Users'],

        'admin.roles.view' => ['summary' => 'See the roles list with user and permission counts.', 'where' => 'Admin → Roles'],
        'admin.roles.create' => ['summary' => 'Create a new role and choose its permissions.', 'where' => 'Admin → Roles'],
        'admin.roles.update' => ['summary' => 'Rename a role and change its permissions.', 'note' => 'Affects every user holding the role.', 'where' => 'Admin → Roles'],
        'admin.roles.delete' => ['summary' => 'Delete a role that no user holds.', 'note' => 'The super-admin role can never be deleted.', 'where' => 'Admin → Roles'],

        'admin.companies.view' => ['summary' => 'Open the Companies list (active and deleted tabs) and search it.', 'where' => 'Admin → Companies'],
        'admin.companies.create' => ['summary' => 'Add a company.', 'where' => 'Admin → Companies'],
        'admin.companies.update' => ['summary' => 'Rename a company, change its short code, and activate or deactivate it.', 'note' => 'An inactive company can no longer be selected in the header.', 'where' => 'Admin → Companies'],
        'admin.companies.delete' => ['summary' => 'Move a company that nothing belongs to into Deleted — one, or several ticked at once.', 'note' => 'Refused while any department belongs to it. Reversible with restore.', 'where' => 'Admin → Companies'],
        'admin.companies.restore' => ['summary' => 'Bring a company back from Deleted.', 'where' => 'Admin → Companies → Deleted'],
        'admin.companies.force-delete' => ['summary' => 'Permanently delete companies from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Admin → Companies → Deleted'],

        'admin.permissions.view' => ['summary' => 'See every permission, what it allows and which roles hold it.', 'where' => 'Admin → Permissions'],

        'admin.audit-logs.view' => ['summary' => 'Read the audit trail: who changed what, when, with the old and new values.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Audit Log'],

        'admin.mail-settings.view' => ['summary' => 'See the outgoing mail server settings.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Application Settings → Mail Server'],
        'admin.mail-settings.update' => ['summary' => 'Change the outgoing mail server and send a test mail.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Application Settings → Mail Server'],
        'admin.quick-login.view' => ['summary' => 'See whether development quick login is switched on.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Application Settings → Quick Login'],
        'admin.quick-login.update' => ['summary' => 'Switch development quick login on or off.', 'note' => self::SUPER_ADMIN_ONLY.' Never shown outside local and testing environments.', 'where' => 'Admin → Application Settings → Quick Login'],
        'admin.branding.view' => ['summary' => 'See the uploaded logo and favicon.', 'where' => 'Admin → Application Settings → Branding'],
        'admin.branding.update' => ['summary' => 'Upload or remove the logo and the browser-tab favicon.', 'note' => 'Changes what every user sees in the sidebar, on the login page and in the browser tab.', 'where' => 'Admin → Application Settings → Branding'],
        'admin.import-limits.view' => ['summary' => 'See the row, column and worksheet caps every spreadsheet import is read under.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Application Settings → Import Limits'],
        'admin.import-limits.update' => ['summary' => 'Change the row, column and worksheet caps for spreadsheet imports.', 'note' => self::SUPER_ADMIN_ONLY.' A higher cap lets one upload use more server memory.', 'where' => 'Admin → Application Settings → Import Limits'],
        'admin.sent-mails.view' => ['summary' => 'See every mail the application sent, with its status and a preview.', 'note' => self::SUPER_ADMIN_ONLY, 'where' => 'Admin → Sent Mails'],

        'projects.dashboard.view' => ['summary' => 'Open the Projects overview.', 'note' => 'A preview with sample data for now.', 'where' => 'Projects → Overview'],
        'accounting.dashboard.view' => ['summary' => 'Open the Accounts overview.', 'note' => 'A preview with sample data for now.', 'where' => 'Accounts → Overview'],
        'hrm.dashboard.view' => ['summary' => 'Open the HRM overview.', 'note' => 'A preview with sample data for now.', 'where' => 'HRM → Overview'],
        'hrm.departments.view' => ['summary' => "Open the selected company's Departments list (active and deleted tabs) and search it.", 'where' => 'HRM → Departments'],
        'hrm.departments.create' => ['summary' => 'Add a department to the selected company.', 'where' => 'HRM → Departments'],
        'hrm.departments.update' => ['summary' => "Change a department's code or name, and activate or deactivate it.", 'where' => 'HRM → Departments'],
        'hrm.departments.delete' => ['summary' => 'Move departments into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved.', 'where' => 'HRM → Departments'],
        'hrm.departments.restore' => ['summary' => 'Bring a department back from Deleted.', 'where' => 'HRM → Departments → Deleted'],
        'hrm.departments.force-delete' => ['summary' => 'Permanently delete departments from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'HRM → Departments → Deleted'],
        'hrm.departments.import' => ['summary' => 'Create and update departments from a spreadsheet.', 'note' => 'Writes many rows at once into the selected company.', 'where' => 'HRM → Departments → Import'],
    ];

    /**
     * Curated help per module — the first segment of a permission name.
     *
     * `label` is the module's display name — the menu's name, which is not
     * always the permission prefix (`accounting` is the Accounts menu).
     *
     * @var array<string, array{label: string, summary: string, where: string}>
     */
    protected const array MODULES = [
        'admin' => ['label' => 'Admin', 'summary' => 'Users, roles, companies, the audit trail and application-wide settings.', 'where' => 'Admin menu'],
        'projects' => ['label' => 'Projects', 'summary' => 'Client projects: milestones, tasks, timesheets and billing requests.', 'where' => 'Projects menu'],
        'accounting' => ['label' => 'Accounts', 'summary' => "Each company's books: vouchers, ledgers, statements, customers and invoices.", 'where' => 'Accounts menu'],
        'hrm' => ['label' => 'HRM', 'summary' => 'The workforce: employees, attendance, leave and payroll.', 'where' => 'HRM menu'],
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
     * @return array{label: string, summary: string, where: ?string}
     */
    public function forModule(string $module): array
    {
        $words = Str::of($module)->replace('-', ' ')->value();

        return self::MODULES[$module] ?? [
            'label' => Str::ucfirst($words),
            'summary' => 'Permissions for the '.$words.' module.',
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
