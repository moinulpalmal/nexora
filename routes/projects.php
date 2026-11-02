<?php

use App\Http\Controllers\ProjectManagement\ClientController;
use App\Http\Controllers\ProjectManagement\DashboardController;
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
    });
