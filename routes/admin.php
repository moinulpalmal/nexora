<?php

use App\Http\Controllers\Admin\ApplicationSettingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BrandingSettingController;
use App\Http\Controllers\Admin\MailSettingController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\QuickLoginSettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SentMailController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin module
|--------------------------------------------------------------------------
|
| Users, roles, the permission catalogue and the audit trail. Name prefix
| `admin.`, URL prefix `admin/`. Every route is gated by a permission —
| never a role name — see ARCHITECTURE.md §9.1. Self-protection rules live in
| the services' `*Blocker()` guards, because `Gate::before` lets a super admin
| through any gate.
|
*/

Route::middleware(['auth', 'auth.session'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // Before the resource, so `availability` is not read as a {user} id.
        Route::get('users/availability', [UserController::class, 'availability'])
            ->middleware('permission:admin.users.create|admin.users.update')
            ->name('users.availability');

        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:admin.users.view')
            ->middlewareFor(['store'], 'permission:admin.users.create')
            ->middlewareFor(['update'], 'permission:admin.users.update')
            ->middlewareFor(['destroy'], 'permission:admin.users.delete');

        Route::put('users/{user}/restore', [UserController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:admin.users.restore')
            ->name('users.restore');

        Route::delete('users/{user}/force', [UserController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:admin.users.force-delete')
            ->name('users.force-delete');

        Route::put('users/{user}/password', [UserController::class, 'updatePassword'])
            ->middleware('permission:admin.users.reset-password')
            ->name('users.password');

        Route::put('users/{user}/roles', [UserController::class, 'updateRoles'])
            ->middleware('permission:admin.users.assign-roles')
            ->name('users.roles');

        Route::resource('roles', RoleController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:admin.roles.view')
            ->middlewareFor(['store'], 'permission:admin.roles.create')
            ->middlewareFor(['update'], 'permission:admin.roles.update')
            ->middlewareFor(['destroy'], 'permission:admin.roles.delete');

        Route::get('permissions', [PermissionController::class, 'index'])
            ->middleware('permission:admin.permissions.view')
            ->name('permissions.index');

        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:admin.audit-logs.view')
            ->name('audit-logs.index');

        Route::get('audit-logs/history', [AuditLogController::class, 'history'])
            ->middleware('permission:admin.audit-logs.view')
            ->name('audit-logs.history');

        // The settings hub opens for either tab's `.view`; each tab saves on its own route.
        Route::get('application-settings', [ApplicationSettingController::class, 'edit'])
            ->middleware('permission:admin.mail-settings.view|admin.quick-login.view|admin.branding.view')
            ->name('application-settings.edit');

        Route::put('application-settings/mail', [MailSettingController::class, 'update'])
            ->middleware('permission:admin.mail-settings.update')
            ->name('mail-settings.update');

        Route::post('application-settings/mail/test', [MailSettingController::class, 'test'])
            ->middleware(['permission:admin.mail-settings.update', 'throttle:6,1'])
            ->name('mail-settings.test');

        Route::put('application-settings/quick-login', [QuickLoginSettingController::class, 'update'])
            ->middleware('permission:admin.quick-login.update')
            ->name('quick-login.update');

        Route::put('application-settings/branding', [BrandingSettingController::class, 'update'])
            ->middleware('permission:admin.branding.update')
            ->name('branding.update');

        Route::delete('application-settings/branding/{image}', [BrandingSettingController::class, 'destroy'])
            ->whereIn('image', ['logo', 'favicon'])
            ->middleware('permission:admin.branding.update')
            ->name('branding.destroy');

        Route::get('sent-mails', [SentMailController::class, 'index'])
            ->middleware('permission:admin.sent-mails.view')
            ->name('sent-mails.index');

        Route::get('sent-mails/{sentMail}', [SentMailController::class, 'show'])
            ->middleware('permission:admin.sent-mails.view')
            ->name('sent-mails.show');
    });
