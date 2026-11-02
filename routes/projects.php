<?php

use App\Http\Controllers\ProjectManagement\ClientController;
use App\Http\Controllers\ProjectManagement\DashboardController;
use App\Http\Controllers\ProjectManagement\SupplierCategoryController;
use App\Http\Controllers\ProjectManagement\SupplierController;
use App\Http\Controllers\ProjectManagement\TenderTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Projects module (Project Management)
|--------------------------------------------------------------------------
|
| Client projects from contract to billing request. Namespace
| `ProjectManagement`, name prefix `projects.`, URL prefix `projects/`. Every
| route is gated by a permission — see ARCHITECTURE.md §9.1. Every record is
| the selected company's (§9.10): the models' scope, not the routes, sees to
| that.
|
| 🧪 POC: the overview still shows sample data; Clients is the first real
| screen. See documentation/projects.md for the agreed scope.
|
*/

Route::middleware(['auth', 'auth.session'])
    ->prefix('projects')
    ->name('projects.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)
            ->middleware('permission:projects.dashboard.view')
            ->name('dashboard');

        // Before the resource, so `bulk` is not read as a {client} id.
        Route::delete('clients/bulk', [ClientController::class, 'bulkDestroy'])
            ->middleware('permission:projects.clients.delete')
            ->name('clients.bulk-destroy');

        Route::delete('clients/bulk/force', [ClientController::class, 'bulkForceDelete'])
            ->middleware('permission:projects.clients.force-delete')
            ->name('clients.bulk-force-delete');

        Route::resource('clients', ClientController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:projects.clients.view')
            ->middlewareFor(['store'], 'permission:projects.clients.create')
            ->middlewareFor(['update'], 'permission:projects.clients.update')
            ->middlewareFor(['destroy'], 'permission:projects.clients.delete');

        Route::put('clients/{client}/restore', [ClientController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:projects.clients.restore')
            ->name('clients.restore');

        Route::delete('clients/{client}/force', [ClientController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:projects.clients.force-delete')
            ->name('clients.force-delete');

        // Group-wide (owner, 2026-11-02): every company's suppliers are
        // classified by one list (§9.10).
        Route::delete('supplier-categories/bulk', [SupplierCategoryController::class, 'bulkDestroy'])
            ->middleware('permission:projects.supplier-categories.delete')
            ->name('supplier-categories.bulk-destroy');

        Route::delete('supplier-categories/bulk/force', [SupplierCategoryController::class, 'bulkForceDelete'])
            ->middleware('permission:projects.supplier-categories.force-delete')
            ->name('supplier-categories.bulk-force-delete');

        Route::resource('supplier-categories', SupplierCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:projects.supplier-categories.view')
            ->middlewareFor(['store'], 'permission:projects.supplier-categories.create')
            ->middlewareFor(['update'], 'permission:projects.supplier-categories.update')
            ->middlewareFor(['destroy'], 'permission:projects.supplier-categories.delete');

        Route::put('supplier-categories/{supplier_category}/restore', [SupplierCategoryController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:projects.supplier-categories.restore')
            ->name('supplier-categories.restore');

        Route::delete('supplier-categories/{supplier_category}/force', [SupplierCategoryController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:projects.supplier-categories.force-delete')
            ->name('supplier-categories.force-delete');

        // The ERP's one supplier list, kept per company (owner, 2026-11-02).
        Route::delete('suppliers/bulk', [SupplierController::class, 'bulkDestroy'])
            ->middleware('permission:projects.suppliers.delete')
            ->name('suppliers.bulk-destroy');

        Route::delete('suppliers/bulk/force', [SupplierController::class, 'bulkForceDelete'])
            ->middleware('permission:projects.suppliers.force-delete')
            ->name('suppliers.bulk-force-delete');

        Route::resource('suppliers', SupplierController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:projects.suppliers.view')
            ->middlewareFor(['store'], 'permission:projects.suppliers.create')
            ->middlewareFor(['update'], 'permission:projects.suppliers.update')
            ->middlewareFor(['destroy'], 'permission:projects.suppliers.delete');

        Route::put('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:projects.suppliers.restore')
            ->name('suppliers.restore');

        Route::delete('suppliers/{supplier}/force', [SupplierController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:projects.suppliers.force-delete')
            ->name('suppliers.force-delete');

        // Group-wide, not the selected company's (owner, 2026-11-02): a
        // procurement method is the same for every company (§9.10).
        Route::delete('tender-types/bulk', [TenderTypeController::class, 'bulkDestroy'])
            ->middleware('permission:projects.tender-types.delete')
            ->name('tender-types.bulk-destroy');

        Route::delete('tender-types/bulk/force', [TenderTypeController::class, 'bulkForceDelete'])
            ->middleware('permission:projects.tender-types.force-delete')
            ->name('tender-types.bulk-force-delete');

        Route::resource('tender-types', TenderTypeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:projects.tender-types.view')
            ->middlewareFor(['store'], 'permission:projects.tender-types.create')
            ->middlewareFor(['update'], 'permission:projects.tender-types.update')
            ->middlewareFor(['destroy'], 'permission:projects.tender-types.delete');

        Route::put('tender-types/{tender_type}/restore', [TenderTypeController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:projects.tender-types.restore')
            ->name('tender-types.restore');

        Route::delete('tender-types/{tender_type}/force', [TenderTypeController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:projects.tender-types.force-delete')
            ->name('tender-types.force-delete');
    });
