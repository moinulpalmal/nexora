<?php

use App\Http\Controllers\ProjectManagement\CertificationCategoryController;
use App\Http\Controllers\ProjectManagement\CertificationController;
use App\Http\Controllers\ProjectManagement\ClientController;
use App\Http\Controllers\ProjectManagement\DashboardController;
use App\Http\Controllers\ProjectManagement\ProductCategoryController;
use App\Http\Controllers\ProjectManagement\ProductController;
use App\Http\Controllers\ProjectManagement\SupplierCategoryController;
use App\Http\Controllers\ProjectManagement\SupplierController;
use App\Http\Controllers\ProjectManagement\TenderTypeController;
use App\Http\Controllers\ProjectManagement\UnitController;
use App\Http\Controllers\ProjectManagement\VerificationItemController;
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

        // The module's setup lists (owner, 2026-11-04): the menu reads Projects →
        // Settings → Clients, and the URL, route name and permission follow it.
        // There is no page at projects/settings itself.
        Route::prefix('settings')->name('settings.')->group(function (): void {
            // Before the resource, so `bulk` is not read as a {client} id.
            Route::delete('clients/bulk', [ClientController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.clients.delete')
                ->name('clients.bulk-destroy');

            Route::delete('clients/bulk/force', [ClientController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.clients.force-delete')
                ->name('clients.bulk-force-delete');

            Route::resource('clients', ClientController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.clients.view')
                ->middlewareFor(['store'], 'permission:projects.settings.clients.create')
                ->middlewareFor(['update'], 'permission:projects.settings.clients.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.clients.delete');

            Route::put('clients/{client}/restore', [ClientController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.clients.restore')
                ->name('clients.restore');

            Route::delete('clients/{client}/force', [ClientController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.clients.force-delete')
                ->name('clients.force-delete');

            // Kept per company (owner, 2026-11-03); every company starts with a
            // starter set.
            Route::delete('product-categories/bulk', [ProductCategoryController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.product-categories.delete')
                ->name('product-categories.bulk-destroy');

            Route::delete('product-categories/bulk/force', [ProductCategoryController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.product-categories.force-delete')
                ->name('product-categories.bulk-force-delete');

            Route::resource('product-categories', ProductCategoryController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.product-categories.view')
                ->middlewareFor(['store'], 'permission:projects.settings.product-categories.create')
                ->middlewareFor(['update'], 'permission:projects.settings.product-categories.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.product-categories.delete');

            Route::put('product-categories/{product_category}/restore', [ProductCategoryController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.product-categories.restore')
                ->name('product-categories.restore');

            Route::delete('product-categories/{product_category}/force', [ProductCategoryController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.product-categories.force-delete')
                ->name('product-categories.force-delete');

            // Kept per company, each in one of the company's product categories
            // (owner, 2026-11-03).
            Route::delete('products/bulk', [ProductController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.products.delete')
                ->name('products.bulk-destroy');

            Route::delete('products/bulk/force', [ProductController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.products.force-delete')
                ->name('products.bulk-force-delete');

            Route::resource('products', ProductController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.products.view')
                ->middlewareFor(['store'], 'permission:projects.settings.products.create')
                ->middlewareFor(['update'], 'permission:projects.settings.products.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.products.delete');

            Route::put('products/{product}/restore', [ProductController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.products.restore')
                ->name('products.restore');

            Route::delete('products/{product}/force', [ProductController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.products.force-delete')
                ->name('products.force-delete');

            // Group-wide (owner, 2026-11-02): every company's suppliers are
            // classified by one list (§9.10).
            Route::delete('supplier-categories/bulk', [SupplierCategoryController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.supplier-categories.delete')
                ->name('supplier-categories.bulk-destroy');

            Route::delete('supplier-categories/bulk/force', [SupplierCategoryController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.supplier-categories.force-delete')
                ->name('supplier-categories.bulk-force-delete');

            Route::resource('supplier-categories', SupplierCategoryController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.supplier-categories.view')
                ->middlewareFor(['store'], 'permission:projects.settings.supplier-categories.create')
                ->middlewareFor(['update'], 'permission:projects.settings.supplier-categories.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.supplier-categories.delete');

            Route::put('supplier-categories/{supplier_category}/restore', [SupplierCategoryController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.supplier-categories.restore')
                ->name('supplier-categories.restore');

            Route::delete('supplier-categories/{supplier_category}/force', [SupplierCategoryController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.supplier-categories.force-delete')
                ->name('supplier-categories.force-delete');

            // The ERP's one supplier list, kept per company (owner, 2026-11-02).
            Route::delete('suppliers/bulk', [SupplierController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.suppliers.delete')
                ->name('suppliers.bulk-destroy');

            Route::delete('suppliers/bulk/force', [SupplierController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.suppliers.force-delete')
                ->name('suppliers.bulk-force-delete');

            Route::resource('suppliers', SupplierController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.suppliers.view')
                ->middlewareFor(['store'], 'permission:projects.settings.suppliers.create')
                ->middlewareFor(['update'], 'permission:projects.settings.suppliers.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.suppliers.delete');

            Route::put('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.suppliers.restore')
                ->name('suppliers.restore');

            Route::delete('suppliers/{supplier}/force', [SupplierController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.suppliers.force-delete')
                ->name('suppliers.force-delete');

            // Group-wide, not the selected company's (owner, 2026-11-02): a
            // procurement method is the same for every company (§9.10).
            Route::delete('tender-types/bulk', [TenderTypeController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.tender-types.delete')
                ->name('tender-types.bulk-destroy');

            Route::delete('tender-types/bulk/force', [TenderTypeController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.tender-types.force-delete')
                ->name('tender-types.bulk-force-delete');

            Route::resource('tender-types', TenderTypeController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.tender-types.view')
                ->middlewareFor(['store'], 'permission:projects.settings.tender-types.create')
                ->middlewareFor(['update'], 'permission:projects.settings.tender-types.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.tender-types.delete');

            Route::put('tender-types/{tender_type}/restore', [TenderTypeController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.tender-types.restore')
                ->name('tender-types.restore');

            Route::delete('tender-types/{tender_type}/force', [TenderTypeController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.tender-types.force-delete')
                ->name('tender-types.force-delete');

            // Group-wide, not the selected company's (owner, 2026-11-02): a
            // kilogram is the same for every company (§9.10).
            Route::delete('units/bulk', [UnitController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.units.delete')
                ->name('units.bulk-destroy');

            Route::delete('units/bulk/force', [UnitController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.units.force-delete')
                ->name('units.bulk-force-delete');

            Route::resource('units', UnitController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.units.view')
                ->middlewareFor(['store'], 'permission:projects.settings.units.create')
                ->middlewareFor(['update'], 'permission:projects.settings.units.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.units.delete');

            Route::put('units/{unit}/restore', [UnitController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.units.restore')
                ->name('units.restore');

            Route::delete('units/{unit}/force', [UnitController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.units.force-delete')
                ->name('units.force-delete');

            // Group-wide (owner, 2026-11-02): one list of certification
            // categories every company shares, seeded (§9.10).
            Route::delete('certification-categories/bulk', [CertificationCategoryController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.certification-categories.delete')
                ->name('certification-categories.bulk-destroy');

            Route::delete('certification-categories/bulk/force', [CertificationCategoryController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.certification-categories.force-delete')
                ->name('certification-categories.bulk-force-delete');

            Route::resource('certification-categories', CertificationCategoryController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.certification-categories.view')
                ->middlewareFor(['store'], 'permission:projects.settings.certification-categories.create')
                ->middlewareFor(['update'], 'permission:projects.settings.certification-categories.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.certification-categories.delete');

            Route::put('certification-categories/{certification_category}/restore', [CertificationCategoryController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.certification-categories.restore')
                ->name('certification-categories.restore');

            Route::delete('certification-categories/{certification_category}/force', [CertificationCategoryController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.certification-categories.force-delete')
                ->name('certification-categories.force-delete');

            // Group-wide (owner, 2026-11-02): ISO 9001 is the same standard for
            // every company (§9.10). A catalogue of standards, seeded.
            Route::delete('certifications/bulk', [CertificationController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.certifications.delete')
                ->name('certifications.bulk-destroy');

            Route::delete('certifications/bulk/force', [CertificationController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.certifications.force-delete')
                ->name('certifications.bulk-force-delete');

            Route::resource('certifications', CertificationController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.certifications.view')
                ->middlewareFor(['store'], 'permission:projects.settings.certifications.create')
                ->middlewareFor(['update'], 'permission:projects.settings.certifications.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.certifications.delete');

            Route::put('certifications/{certification}/restore', [CertificationController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.certifications.restore')
                ->name('certifications.restore');

            Route::delete('certifications/{certification}/force', [CertificationController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.certifications.force-delete')
                ->name('certifications.force-delete');

            // Kept per company (owner, 2026-11-03); what will use an item is still
            // to be named.
            Route::delete('verification-items/bulk', [VerificationItemController::class, 'bulkDestroy'])
                ->middleware('permission:projects.settings.verification-items.delete')
                ->name('verification-items.bulk-destroy');

            Route::delete('verification-items/bulk/force', [VerificationItemController::class, 'bulkForceDelete'])
                ->middleware('permission:projects.settings.verification-items.force-delete')
                ->name('verification-items.bulk-force-delete');

            Route::resource('verification-items', VerificationItemController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->middlewareFor(['index'], 'permission:projects.settings.verification-items.view')
                ->middlewareFor(['store'], 'permission:projects.settings.verification-items.create')
                ->middlewareFor(['update'], 'permission:projects.settings.verification-items.update')
                ->middlewareFor(['destroy'], 'permission:projects.settings.verification-items.delete');

            Route::put('verification-items/{verification_item}/restore', [VerificationItemController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:projects.settings.verification-items.restore')
                ->name('verification-items.restore');

            Route::delete('verification-items/{verification_item}/force', [VerificationItemController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:projects.settings.verification-items.force-delete')
                ->name('verification-items.force-delete');
        });
    });
