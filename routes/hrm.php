<?php

use App\Http\Controllers\Hrm\DashboardController;
use App\Http\Controllers\Hrm\DepartmentController;
use App\Http\Controllers\Hrm\DepartmentImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HRM module
|--------------------------------------------------------------------------
|
| Each company's workforce. Name prefix `hrm.`, URL prefix `hrm/`. Every
| route is gated by a permission — see ARCHITECTURE.md §9.1. Every record is
| the selected company's (§9.10): the models' scope, not the routes, sees to
| that.
|
| 🧪 POC: the overview still shows sample data; Departments is the first real
| screen. See documentation/hrm.md for the agreed scope.
|
*/

Route::middleware(['auth', 'auth.session'])
    ->prefix('hrm')
    ->name('hrm.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)
            ->middleware('permission:hrm.dashboard.view')
            ->name('dashboard');

        // Before the resource, so `import` is not read as a {department} id.
        Route::get('departments/import/template', [DepartmentImportController::class, 'template'])
            ->middleware('permission:hrm.departments.import')
            ->name('departments.import.template');

        Route::post('departments/import', [DepartmentImportController::class, 'store'])
            ->middleware('permission:hrm.departments.import')
            ->name('departments.import.store');

        Route::post('departments/imports/{sheetImport}/review', [DepartmentImportController::class, 'review'])
            ->middleware('permission:hrm.departments.import')
            ->name('departments.import.review');

        Route::post('departments/imports/{sheetImport}/confirm', [DepartmentImportController::class, 'confirm'])
            ->middleware('permission:hrm.departments.import')
            ->name('departments.import.confirm');

        Route::get('departments/imports/{sheetImport}/rejects', [DepartmentImportController::class, 'rejects'])
            ->middleware('permission:hrm.departments.import')
            ->name('departments.import.rejects');

        Route::delete('departments/imports/{sheetImport}', [DepartmentImportController::class, 'cancel'])
            ->middleware('permission:hrm.departments.import')
            ->name('departments.import.cancel');

        // Before the resource, so `bulk` is not read as a {department} id.
        Route::delete('departments/bulk', [DepartmentController::class, 'bulkDestroy'])
            ->middleware('permission:hrm.departments.delete')
            ->name('departments.bulk-destroy');

        Route::delete('departments/bulk/force', [DepartmentController::class, 'bulkForceDelete'])
            ->middleware('permission:hrm.departments.force-delete')
            ->name('departments.bulk-force-delete');

        Route::resource('departments', DepartmentController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:hrm.departments.view')
            ->middlewareFor(['store'], 'permission:hrm.departments.create')
            ->middlewareFor(['update'], 'permission:hrm.departments.update')
            ->middlewareFor(['destroy'], 'permission:hrm.departments.delete');

        Route::put('departments/{department}/restore', [DepartmentController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:hrm.departments.restore')
            ->name('departments.restore');

        Route::delete('departments/{department}/force', [DepartmentController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:hrm.departments.force-delete')
            ->name('departments.force-delete');
    });
