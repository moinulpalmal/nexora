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

        'admin.countries.view' => ['summary' => 'Open the Countries list (active and deleted tabs) and search it.', 'where' => 'Admin → Countries'],
        'admin.countries.create' => ['summary' => 'Add a country that is not in the seeded ISO list.', 'where' => 'Admin → Countries'],
        'admin.countries.update' => ['summary' => 'Edit a country\'s name, codes and nationality, and activate or deactivate it.', 'note' => 'The change shows in every company.', 'where' => 'Admin → Countries'],
        'admin.countries.delete' => ['summary' => 'Move countries into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore. Deactivating is the usual way to retire a country.', 'where' => 'Admin → Countries'],
        'admin.countries.restore' => ['summary' => 'Bring a country back from Deleted.', 'where' => 'Admin → Countries → Deleted'],
        'admin.countries.force-delete' => ['summary' => 'Permanently delete countries from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone. Re-running the database seeder adds a permanently deleted ISO country back.', 'where' => 'Admin → Countries → Deleted'],

        'admin.permissions.view' => ['summary' => 'See every permission, what it allows and which roles hold it.', 'where' => 'Admin → Permissions'],
        'admin.enums.view' => ['summary' => 'See every fixed list of values the application uses — its choices, and what is stored for each.', 'where' => 'Admin → Enums'],

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
        'projects.settings.clients.view' => ['summary' => "Open the selected company's Clients list (active and deleted tabs) and search it.", 'where' => 'Projects → Settings → Clients'],
        'projects.settings.clients.create' => ['summary' => 'Add a client to the selected company.', 'where' => 'Projects → Settings → Clients'],
        'projects.settings.clients.update' => ['summary' => "Change a client's details, and activate or deactivate it.", 'where' => 'Projects → Settings → Clients'],
        'projects.settings.clients.delete' => ['summary' => 'Move clients into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved.', 'where' => 'Projects → Settings → Clients'],
        'projects.settings.clients.restore' => ['summary' => 'Bring a client back from Deleted.', 'where' => 'Projects → Settings → Clients → Deleted'],
        'projects.settings.clients.force-delete' => ['summary' => 'Permanently delete clients from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Projects → Settings → Clients → Deleted'],
        'projects.settings.product-categories.view' => ['summary' => "Open the selected company's Product Categories list (active and deleted tabs) and search it.", 'where' => 'Projects → Settings → Product Categories'],
        'projects.settings.product-categories.create' => ['summary' => 'Add a product category to the selected company.', 'note' => 'Every new company already starts with a standard set.', 'where' => 'Projects → Settings → Product Categories'],
        'projects.settings.product-categories.update' => ['summary' => "Change a product category's code, name or description, and activate or deactivate it.", 'where' => 'Projects → Settings → Product Categories'],
        'projects.settings.product-categories.delete' => ['summary' => 'Move product categories into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved. Refused while any product is in the category.', 'where' => 'Projects → Settings → Product Categories'],
        'projects.settings.product-categories.restore' => ['summary' => 'Bring a product category back from Deleted.', 'where' => 'Projects → Settings → Product Categories → Deleted'],
        'projects.settings.product-categories.force-delete' => ['summary' => 'Permanently delete product categories from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone. A company can be deleted only once all its product categories are gone. Refused while any product is in the category.', 'where' => 'Projects → Settings → Product Categories → Deleted'],
        'projects.settings.products.view' => ['summary' => "Open the selected company's Products list (active and deleted tabs) and search it.", 'where' => 'Projects → Settings → Products'],
        'projects.settings.products.create' => ['summary' => 'Add a product to the selected company, in one of its active product categories.', 'where' => 'Projects → Settings → Products'],
        'projects.settings.products.update' => ['summary' => "Change a product's category, code, name or description, and activate or deactivate it.", 'where' => 'Projects → Settings → Products'],
        'projects.settings.products.delete' => ['summary' => 'Move products into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved.', 'where' => 'Projects → Settings → Products'],
        'projects.settings.products.restore' => ['summary' => 'Bring a product back from Deleted.', 'where' => 'Projects → Settings → Products → Deleted'],
        'projects.settings.products.force-delete' => ['summary' => 'Permanently delete products from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone. A company can be deleted only once all its products are gone.', 'where' => 'Projects → Settings → Products → Deleted'],
        'projects.settings.verification-items.view' => ['summary' => "Open the selected company's Verification Items list (active and deleted tabs) and search it.", 'where' => 'Projects → Settings → Verification Items'],
        'projects.settings.verification-items.create' => ['summary' => 'Add a verification item to the selected company.', 'where' => 'Projects → Settings → Verification Items'],
        'projects.settings.verification-items.update' => ['summary' => "Change a verification item's name or description, and activate or deactivate it.", 'where' => 'Projects → Settings → Verification Items'],
        'projects.settings.verification-items.delete' => ['summary' => 'Move verification items into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its name stays reserved.', 'where' => 'Projects → Settings → Verification Items'],
        'projects.settings.verification-items.restore' => ['summary' => 'Bring a verification item back from Deleted.', 'where' => 'Projects → Settings → Verification Items → Deleted'],
        'projects.settings.verification-items.force-delete' => ['summary' => 'Permanently delete verification items from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone. A company can be deleted only once all its verification items are gone.', 'where' => 'Projects → Settings → Verification Items → Deleted'],
        'projects.settings.supplier-categories.view' => ['summary' => 'Open the Supplier Categories list (active and deleted tabs) and search it.', 'note' => 'One list shared by every company.', 'where' => 'Projects → Settings → Supplier Categories'],
        'projects.settings.supplier-categories.create' => ['summary' => 'Add a supplier category.', 'note' => 'Every company sees it.', 'where' => 'Projects → Settings → Supplier Categories'],
        'projects.settings.supplier-categories.update' => ['summary' => "Change a supplier category's code, name or description, and activate or deactivate it.", 'note' => 'The change shows in every company.', 'where' => 'Projects → Settings → Supplier Categories'],
        'projects.settings.supplier-categories.delete' => ['summary' => 'Move supplier categories into Deleted — one, or several ticked at once.', 'note' => 'Refused while any supplier, in any company, is in the category. Reversible with restore; its code and name stay reserved.', 'where' => 'Projects → Settings → Supplier Categories'],
        'projects.settings.supplier-categories.restore' => ['summary' => 'Bring a supplier category back from Deleted.', 'where' => 'Projects → Settings → Supplier Categories → Deleted'],
        'projects.settings.supplier-categories.force-delete' => ['summary' => 'Permanently delete supplier categories from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Projects → Settings → Supplier Categories → Deleted'],
        'projects.settings.suppliers.view' => ['summary' => "Open the selected company's Suppliers list (active and deleted tabs) and search it.", 'where' => 'Projects → Settings → Suppliers'],
        'projects.settings.suppliers.create' => ['summary' => 'Add a supplier to the selected company.', 'where' => 'Projects → Settings → Suppliers'],
        'projects.settings.suppliers.update' => ['summary' => "Change a supplier's details — category, contact, tax numbers and bank account — and activate or deactivate it.", 'where' => 'Projects → Settings → Suppliers'],
        'projects.settings.suppliers.delete' => ['summary' => 'Move suppliers into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved.', 'where' => 'Projects → Settings → Suppliers'],
        'projects.settings.suppliers.restore' => ['summary' => 'Bring a supplier back from Deleted.', 'where' => 'Projects → Settings → Suppliers → Deleted'],
        'projects.settings.suppliers.force-delete' => ['summary' => 'Permanently delete suppliers from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Projects → Settings → Suppliers → Deleted'],
        'projects.settings.tender-types.view' => ['summary' => 'Open the Tender Types list (active and deleted tabs) and search it.', 'note' => 'One list shared by every company.', 'where' => 'Projects → Settings → Tender Types'],
        'projects.settings.tender-types.create' => ['summary' => 'Add a tender type.', 'note' => 'Every company sees it.', 'where' => 'Projects → Settings → Tender Types'],
        'projects.settings.tender-types.update' => ['summary' => "Change a tender type's code or name, and activate or deactivate it.", 'note' => 'The change shows in every company.', 'where' => 'Projects → Settings → Tender Types'],
        'projects.settings.tender-types.delete' => ['summary' => 'Move tender types into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved. Not granted to project managers by default.', 'where' => 'Projects → Settings → Tender Types'],
        'projects.settings.tender-types.restore' => ['summary' => 'Bring a tender type back from Deleted.', 'where' => 'Projects → Settings → Tender Types → Deleted'],
        'projects.settings.tender-types.force-delete' => ['summary' => 'Permanently delete tender types from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Projects → Settings → Tender Types → Deleted'],
        'projects.settings.units.view' => ['summary' => 'Open the Units of Measure list (active and deleted tabs) and search it.', 'note' => 'One list shared by every company.', 'where' => 'Projects → Settings → Units of Measure'],
        'projects.settings.units.create' => ['summary' => 'Add a unit of measure.', 'note' => 'Every company sees it.', 'where' => 'Projects → Settings → Units of Measure'],
        'projects.settings.units.update' => ['summary' => "Change a unit's code, name, symbol, decimal places or description, and activate or deactivate it.", 'note' => 'The change shows in every company.', 'where' => 'Projects → Settings → Units of Measure'],
        'projects.settings.units.delete' => ['summary' => 'Move units of measure into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved. Not granted to project managers by default.', 'where' => 'Projects → Settings → Units of Measure'],
        'projects.settings.units.restore' => ['summary' => 'Bring a unit of measure back from Deleted.', 'where' => 'Projects → Settings → Units of Measure → Deleted'],
        'projects.settings.units.force-delete' => ['summary' => 'Permanently delete units of measure from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Projects → Settings → Units of Measure → Deleted'],
        'projects.settings.certification-categories.view' => ['summary' => 'Open the Certification Categories list (active and deleted tabs) and search it.', 'note' => 'One list shared by every company.', 'where' => 'Projects → Settings → Certification Categories'],
        'projects.settings.certification-categories.create' => ['summary' => 'Add a certification category.', 'note' => 'Every company sees it.', 'where' => 'Projects → Settings → Certification Categories'],
        'projects.settings.certification-categories.update' => ['summary' => "Change a certification category's code, name or description, and activate or deactivate it.", 'note' => 'The change shows in every company.', 'where' => 'Projects → Settings → Certification Categories'],
        'projects.settings.certification-categories.delete' => ['summary' => 'Move certification categories into Deleted — one, or several ticked at once.', 'note' => 'Refused while any certification is in the category. Reversible with restore; its code and name stay reserved. Not granted to project managers by default.', 'where' => 'Projects → Settings → Certification Categories'],
        'projects.settings.certification-categories.restore' => ['summary' => 'Bring a certification category back from Deleted.', 'where' => 'Projects → Settings → Certification Categories → Deleted'],
        'projects.settings.certification-categories.force-delete' => ['summary' => 'Permanently delete certification categories from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Projects → Settings → Certification Categories → Deleted'],
        'projects.settings.certifications.view' => ['summary' => 'Open the Certifications list (active and deleted tabs) and search it.', 'note' => 'One catalogue of standards shared by every company.', 'where' => 'Projects → Settings → Certifications'],
        'projects.settings.certifications.create' => ['summary' => 'Add a certification (a standard such as ISO 9001).', 'note' => 'Every company sees it.', 'where' => 'Projects → Settings → Certifications'],
        'projects.settings.certifications.update' => ['summary' => "Change a certification's category, code, name, issuing body, version or description, and activate or deactivate it.", 'note' => 'The change shows in every company.', 'where' => 'Projects → Settings → Certifications'],
        'projects.settings.certifications.delete' => ['summary' => 'Move certifications into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code, and its name within its version, stay reserved. Not granted to project managers by default.', 'where' => 'Projects → Settings → Certifications'],
        'projects.settings.certifications.restore' => ['summary' => 'Bring a certification back from Deleted.', 'where' => 'Projects → Settings → Certifications → Deleted'],
        'projects.settings.certifications.force-delete' => ['summary' => 'Permanently delete certifications from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'Projects → Settings → Certifications → Deleted'],
        'accounting.dashboard.view' => ['summary' => 'Open the Accounts overview.', 'note' => 'A preview with sample data for now.', 'where' => 'Accounts → Overview'],
        'hrm.dashboard.view' => ['summary' => 'Open the HRM overview.', 'note' => 'A preview with sample data for now.', 'where' => 'HRM → Overview'],
        'hrm.departments.view' => ['summary' => "Open the selected company's Departments list (active and deleted tabs) and search it.", 'where' => 'HRM → Departments'],
        'hrm.departments.create' => ['summary' => 'Add a department to the selected company.', 'where' => 'HRM → Departments'],
        'hrm.departments.update' => ['summary' => "Change a department's code or name, and activate or deactivate it.", 'where' => 'HRM → Departments'],
        'hrm.departments.delete' => ['summary' => 'Move departments into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved.', 'where' => 'HRM → Departments'],
        'hrm.departments.restore' => ['summary' => 'Bring a department back from Deleted.', 'where' => 'HRM → Departments → Deleted'],
        'hrm.departments.force-delete' => ['summary' => 'Permanently delete departments from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'HRM → Departments → Deleted'],
        'hrm.departments.import' => ['summary' => 'Create and update departments from a spreadsheet.', 'note' => 'Writes many rows at once into the selected company.', 'where' => 'HRM → Departments → Import'],
        'hrm.designations.view' => ['summary' => 'Open the Designations list (active and deleted tabs) and search it.', 'note' => 'One list shared by every company.', 'where' => 'HRM → Designations'],
        'hrm.designations.create' => ['summary' => 'Add a designation.', 'note' => 'Every company sees it.', 'where' => 'HRM → Designations'],
        'hrm.designations.update' => ['summary' => "Change a designation's code or name, and activate or deactivate it.", 'note' => 'The change shows in every company.', 'where' => 'HRM → Designations'],
        'hrm.designations.delete' => ['summary' => 'Move designations into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; its code and name stay reserved. Not granted to HR officers by default.', 'where' => 'HRM → Designations'],
        'hrm.designations.restore' => ['summary' => 'Bring a designation back from Deleted.', 'where' => 'HRM → Designations → Deleted'],
        'hrm.designations.force-delete' => ['summary' => 'Permanently delete designations from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone.', 'where' => 'HRM → Designations → Deleted'],
        'hrm.designations.import' => ['summary' => 'Create and update designations from a spreadsheet.', 'note' => 'Writes many rows at once, shared by every company.', 'where' => 'HRM → Designations → Import'],
        'hrm.employees.view' => ['summary' => "Open the selected company's Employees list (active and deleted tabs) and search it.", 'where' => 'HRM → Employees'],
        'hrm.employees.create' => ['summary' => 'Add an employee to the selected company.', 'where' => 'HRM → Employees'],
        'hrm.employees.update' => ['summary' => "Change an employee's details, department and designation, and activate or deactivate them.", 'note' => 'Deactivating an employee also stops their sign-in account.', 'where' => 'HRM → Employees'],
        'hrm.employees.delete' => ['summary' => 'Move employees into Deleted — one, or several ticked at once.', 'note' => 'Reversible with restore; their employee ID and email stay reserved, and their sign-in account stops.', 'where' => 'HRM → Employees'],
        'hrm.employees.restore' => ['summary' => 'Bring an employee back from Deleted.', 'note' => 'Their sign-in account stays inactive until it is re-opened.', 'where' => 'HRM → Employees → Deleted'],
        'hrm.employees.force-delete' => ['summary' => 'Permanently delete employees from Deleted — one, or several ticked at once.', 'note' => 'Cannot be undone. A sign-in account stays in Admin, inactive.', 'where' => 'HRM → Employees → Deleted'],
        'hrm.employees.import' => ['summary' => 'Create and update employees from a spreadsheet.', 'note' => 'Writes many rows at once into the selected company. Never opens sign-in accounts.', 'where' => 'HRM → Employees → Import'],
        'hrm.employees.create-account' => ['summary' => 'Make an employee an application user — open, close or re-open their sign-in account and set its temporary password.', 'note' => 'The account gets no role; an administrator assigns one in Admin → Users.', 'where' => 'HRM → Employees'],
        'hrm.employees.view-compensation' => ['summary' => "See and change employees' monthly salary, on the list, the form and the import.", 'note' => 'Without it the salary is hidden and left unchanged on save.', 'where' => 'HRM → Employees'],
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
     * What a permission acts on: everything between its module and its action,
     * so `projects.settings.clients.view` is `settings.clients` (ARCHITECTURE.md §6.2).
     */
    public static function resourceOf(string $name): string
    {
        $segments = explode('.', $name);

        return implode('.', array_slice($segments, 1, count($segments) > 2 ? -1 : null));
    }

    /**
     * What a permission allows: the last segment of its name, or nothing when
     * the name has no resource.
     */
    public static function actionOf(string $name): string
    {
        $segments = explode('.', $name);

        return count($segments) > 2 ? $segments[array_key_last($segments)] : '';
    }

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

        $resource = Str::afterLast(self::resourceOf($name), '.');
        $action = self::actionOf($name);
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
