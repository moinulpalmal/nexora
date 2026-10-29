<?php

use App\Http\Controllers\Hrm\DashboardController;
use App\Http\Controllers\Hrm\DepartmentController;
use App\Http\Controllers\Hrm\DepartmentImportController;
use App\Http\Controllers\Hrm\DesignationController;
use App\Http\Controllers\Hrm\DesignationImportController;
use App\Http\Controllers\Hrm\EmployeeController;
use App\Http\Controllers\Hrm\EmployeeImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HRM module
|--------------------------------------------------------------------------
|
| Each company's workforce. Name prefix `hrm.`, URL prefix `hrm/`. Every
| route is gated by a permission — see ARCHITECTURE.md §9.1. Every record is
| the selected company's (§9.10): the models' scope, not the routes, sees to
| that — except designations, which are group-wide (owner, 2026-10-29).
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

        // Designations are group-wide (§9.10): no company scope on these.
        // Before the resource, so `import` is not read as a {designation} id.
        Route::get('designations/import/template', [DesignationImportController::class, 'template'])
            ->middleware('permission:hrm.designations.import')
            ->name('designations.import.template');

        Route::post('designations/import', [DesignationImportController::class, 'store'])
            ->middleware('permission:hrm.designations.import')
            ->name('designations.import.store');

        Route::post('designations/imports/{sheetImport}/review', [DesignationImportController::class, 'review'])
            ->middleware('permission:hrm.designations.import')
            ->name('designations.import.review');

        Route::post('designations/imports/{sheetImport}/confirm', [DesignationImportController::class, 'confirm'])
            ->middleware('permission:hrm.designations.import')
            ->name('designations.import.confirm');

        Route::get('designations/imports/{sheetImport}/rejects', [DesignationImportController::class, 'rejects'])
            ->middleware('permission:hrm.designations.import')
            ->name('designations.import.rejects');

        Route::delete('designations/imports/{sheetImport}', [DesignationImportController::class, 'cancel'])
            ->middleware('permission:hrm.designations.import')
            ->name('designations.import.cancel');

        // Before the resource, so `bulk` is not read as a {designation} id.
        Route::delete('designations/bulk', [DesignationController::class, 'bulkDestroy'])
            ->middleware('permission:hrm.designations.delete')
            ->name('designations.bulk-destroy');

        Route::delete('designations/bulk/force', [DesignationController::class, 'bulkForceDelete'])
            ->middleware('permission:hrm.designations.force-delete')
            ->name('designations.bulk-force-delete');

        Route::resource('designations', DesignationController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:hrm.designations.view')
            ->middlewareFor(['store'], 'permission:hrm.designations.create')
            ->middlewareFor(['update'], 'permission:hrm.designations.update')
            ->middlewareFor(['destroy'], 'permission:hrm.designations.delete');

        Route::put('designations/{designation}/restore', [DesignationController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:hrm.designations.restore')
            ->name('designations.restore');

        Route::delete('designations/{designation}/force', [DesignationController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:hrm.designations.force-delete')
            ->name('designations.force-delete');

        // Before the resource, so `import` is not read as an {employee} id.
        Route::get('employees/import/template', [EmployeeImportController::class, 'template'])
            ->middleware('permission:hrm.employees.import')
            ->name('employees.import.template');

        Route::post('employees/import', [EmployeeImportController::class, 'store'])
            ->middleware('permission:hrm.employees.import')
            ->name('employees.import.store');

        Route::post('employees/imports/{sheetImport}/review', [EmployeeImportController::class, 'review'])
            ->middleware('permission:hrm.employees.import')
            ->name('employees.import.review');

        Route::post('employees/imports/{sheetImport}/confirm', [EmployeeImportController::class, 'confirm'])
            ->middleware('permission:hrm.employees.import')
            ->name('employees.import.confirm');

        Route::get('employees/imports/{sheetImport}/rejects', [EmployeeImportController::class, 'rejects'])
            ->middleware('permission:hrm.employees.import')
            ->name('employees.import.rejects');

        Route::delete('employees/imports/{sheetImport}', [EmployeeImportController::class, 'cancel'])
            ->middleware('permission:hrm.employees.import')
            ->name('employees.import.cancel');

        // Before the resource, so `bulk` is not read as an {employee} id.
        Route::delete('employees/bulk', [EmployeeController::class, 'bulkDestroy'])
            ->middleware('permission:hrm.employees.delete')
            ->name('employees.bulk-destroy');

        Route::delete('employees/bulk/force', [EmployeeController::class, 'bulkForceDelete'])
            ->middleware('permission:hrm.employees.force-delete')
            ->name('employees.bulk-force-delete');

        Route::resource('employees', EmployeeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['index'], 'permission:hrm.employees.view')
            ->middlewareFor(['store'], 'permission:hrm.employees.create')
            ->middlewareFor(['update'], 'permission:hrm.employees.update')
            ->middlewareFor(['destroy'], 'permission:hrm.employees.delete');

        Route::put('employees/{employee}/restore', [EmployeeController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:hrm.employees.restore')
            ->name('employees.restore');

        Route::delete('employees/{employee}/force', [EmployeeController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('permission:hrm.employees.force-delete')
            ->name('employees.force-delete');
    });
