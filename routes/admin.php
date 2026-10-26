<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
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
    });
