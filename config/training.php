<?php

/*
|--------------------------------------------------------------------------
| Training — the registry
|--------------------------------------------------------------------------
|
| Every screen of the application, and the guide that explains it. Read by
| `App\Services\Training\TrainingRegistry`; checked by `php artisan
| training:gaps`, whose test fails the suite on any gap
| (documentation/training.md §5).
|
| A page key is the route name with a trailing `.index` dropped. Every entry:
|
|   route     the GET route the guide describes
|   guide     the guide's file under resources/js/components/training/guides/,
|             without `.tsx`
|   section   a key of `sections`
|   sort      order within the section — the sidebar's order
|   title_en  the guide's title in English
|   title_bn  the guide's title in Bangla
|   status    `written` once the guide exists, `pending` until then. A pending
|             entry is listed in the manual and says "not yet documented"
|   shots     shot id => what the screenshot shows. A written guide has at
|             least `overview`. The PNG lives at
|             public/images/training/{key}/{id}.png and is captured by
|             `composer training:screens`
|
| A new screen must be registered here — `pending` is enough — or the suite
| fails. `ignore_routes` lists the GET routes that are not screens.
|
| The gate sees only routes of `App\Http\Controllers\…`, so a screen declared
| with `Route::inertia()` (the dashboard) must be remembered by hand.
|
*/

return [

    'sections' => [
        'general' => ['en' => 'Getting started', 'bn' => 'শুরু করুন', 'sort' => 10],
        'admin' => ['en' => 'Admin', 'bn' => 'অ্যাডমিন', 'sort' => 20],
        'settings' => ['en' => 'Settings (your account)', 'bn' => 'সেটিংস (আপনার অ্যাকাউন্ট)', 'sort' => 30],
        'projects' => ['en' => 'Projects', 'bn' => 'প্রকল্প', 'sort' => 40],
        'accounting' => ['en' => 'Accounts', 'bn' => 'হিসাব', 'sort' => 50],
        'hrm' => ['en' => 'HRM', 'bn' => 'মানবসম্পদ', 'sort' => 60],
    ],

    'pages' => [

        /* ---------------------------------------------------------------- General */

        'dashboard' => [
            'route' => 'dashboard', 'guide' => 'general/dashboard', 'section' => 'general', 'sort' => 10,
            'title_en' => 'Dashboard and getting around', 'title_bn' => 'ড্যাশবোর্ড ও চলাফেরা', 'status' => 'written',
            'shots' => ['overview' => 'The dashboard with the sidebar and the header', 'bell' => 'The notification bell open with an unread notification'],
        ],

        /* ------------------------------------------------------------------ Admin */

        'admin.users' => [
            'route' => 'admin.users.index', 'guide' => 'admin/users', 'section' => 'admin', 'sort' => 10,
            'title_en' => 'Users', 'title_bn' => 'ব্যবহারকারী', 'status' => 'written',
            'shots' => ['overview' => 'The users list with invented users', 'form' => 'The New user dialog', 'roles' => 'The Roles dialog for a user', 'companies' => 'The Companies dialog for a user', 'password' => 'The Set a password dialog', 'deleted' => 'The Deleted tab with a deleted user'],
        ],
        'admin.companies' => [
            'route' => 'admin.companies.index', 'guide' => 'admin/companies', 'section' => 'admin', 'sort' => 25,
            'title_en' => 'Companies', 'title_bn' => 'কোম্পানি', 'status' => 'written',
            'shots' => ['overview' => 'The companies list with invented companies', 'form' => 'The New company dialog', 'deleted' => 'The Deleted tab with a deleted company'],
        ],
        'admin.roles' => [
            'route' => 'admin.roles.index', 'guide' => 'admin/roles', 'section' => 'admin', 'sort' => 20,
            'title_en' => 'Roles', 'title_bn' => 'রোল', 'status' => 'written',
            'shots' => ['overview' => 'The roles list with permission and user counts', 'form' => 'The New role dialog with a permission explained'],
        ],
        'admin.permissions' => [
            'route' => 'admin.permissions.index', 'guide' => 'admin/permissions', 'section' => 'admin', 'sort' => 30,
            'title_en' => 'Permissions', 'title_bn' => 'পারমিশন', 'status' => 'written',
            'shots' => ['overview' => 'The permission catalogue'],
        ],
        'admin.audit-logs' => [
            'route' => 'admin.audit-logs.index', 'guide' => 'admin/audit-logs', 'section' => 'admin', 'sort' => 40,
            'title_en' => 'Audit Log', 'title_bn' => 'অডিট লগ', 'status' => 'written',
            'shots' => ['overview' => 'The audit trail with invented changes', 'detail' => 'One change open, field by field', 'history' => 'The full history of one record'],
        ],
        'admin.sent-mails' => [
            'route' => 'admin.sent-mails.index', 'guide' => 'admin/sent-mails', 'section' => 'admin', 'sort' => 50,
            'title_en' => 'Sent Mails', 'title_bn' => 'পাঠানো মেইল', 'status' => 'written',
            'shots' => ['overview' => 'The list of invented sent mails', 'mail' => 'A stored welcome mail open, password masked'],
        ],
        'admin.application-settings' => [
            'route' => 'admin.application-settings.edit', 'guide' => 'admin/application-settings', 'section' => 'admin', 'sort' => 60,
            'title_en' => 'Application Settings', 'title_bn' => 'অ্যাপ্লিকেশন সেটিংস', 'status' => 'written',
            'shots' => ['overview' => 'The Mail Server tab with an invented SMTP server', 'quick-login' => 'The Quick Login tab'],
        ],

        /* --------------------------------------------------------------- Settings */

        'profile.edit' => [
            'route' => 'profile.edit', 'guide' => 'settings/profile', 'section' => 'settings', 'sort' => 10,
            'title_en' => 'Profile', 'title_bn' => 'প্রোফাইল', 'status' => 'written',
            'shots' => ['overview' => 'The profile form', 'sign-in-change' => 'A changed username asking for the current password'],
        ],
        'security.edit' => [
            'route' => 'security.edit', 'guide' => 'settings/security', 'section' => 'settings', 'sort' => 20,
            'title_en' => 'Password and security', 'title_bn' => 'পাসওয়ার্ড ও নিরাপত্তা', 'status' => 'written',
            'shots' => ['overview' => 'The update password form'],
        ],
        'appearance.edit' => [
            'route' => 'appearance.edit', 'guide' => 'settings/appearance', 'section' => 'settings', 'sort' => 30,
            'title_en' => 'Appearance', 'title_bn' => 'চেহারা', 'status' => 'written',
            'shots' => ['overview' => 'The theme picker'],
        ],

        /* ------------------------------------------------- ERP modules (🧪 POC) */

        'projects.dashboard' => [
            'route' => 'projects.dashboard', 'guide' => 'projects/dashboard', 'section' => 'projects', 'sort' => 10,
            'title_en' => 'Projects overview', 'title_bn' => 'প্রকল্পের সারসংক্ষেপ', 'status' => 'pending',
            'shots' => [],
        ],
        'accounting.dashboard' => [
            'route' => 'accounting.dashboard', 'guide' => 'accounting/dashboard', 'section' => 'accounting', 'sort' => 10,
            'title_en' => 'Accounts overview', 'title_bn' => 'হিসাবের সারসংক্ষেপ', 'status' => 'pending',
            'shots' => [],
        ],
        'hrm.dashboard' => [
            'route' => 'hrm.dashboard', 'guide' => 'hrm/dashboard', 'section' => 'hrm', 'sort' => 10,
            'title_en' => 'HRM overview', 'title_bn' => 'মানবসম্পদের সারসংক্ষেপ', 'status' => 'pending',
            'shots' => [],
        ],
        'hrm.departments' => [
            'route' => 'hrm.departments.index', 'guide' => 'hrm/departments', 'section' => 'hrm', 'sort' => 20,
            'title_en' => 'Departments', 'title_bn' => 'বিভাগ', 'status' => 'written',
            'shots' => ['overview' => 'The departments of an invented company', 'form' => 'The New department dialog', 'import' => 'The department import dialog'],
        ],
        'hrm.designations' => [
            'route' => 'hrm.designations.index', 'guide' => 'hrm/designations', 'section' => 'hrm', 'sort' => 30,
            'title_en' => 'Designations', 'title_bn' => 'পদবি', 'status' => 'written',
            'shots' => ['overview' => 'The designations every company shares', 'form' => 'The New designation dialog', 'import' => 'The designation import dialog'],
        ],

    ],

    /*
     * Named GET routes of App\Http\Controllers that are not screens: JSON,
     * a file, a redirect, or the manual itself.
     */
    'ignore_routes' => [
        'home',
        'notifications.index',
        'admin.users.availability',
        // A workbook download and a rejected-rows download — files, not screens (ARCHITECTURE.md §9.13).
        'hrm.departments.import.template',
        'hrm.departments.import.rejects',
        'hrm.designations.import.template',
        'hrm.designations.import.rejects',
        'admin.audit-logs.history',
        'admin.sent-mails.show',
        'training.index',
        'training.show',
        'training.panel',
    ],

    'guides_path' => 'resources/js/components/training/guides',

    /*
     * Never under public/training/: Apache serves a real folder before Laravel
     * sees the request, so a folder named like the `/training` route hides it.
     */
    'screens_path' => 'public/images/training',

];
