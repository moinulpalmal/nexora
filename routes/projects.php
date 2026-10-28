<?php

use App\Http\Controllers\ProjectManagement\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Projects module (Project Management)
|--------------------------------------------------------------------------
|
| Client projects from contract to billing request. Namespace
| `ProjectManagement`, name prefix `projects.`, URL prefix `projects/`. Every
| route is gated by a permission — see ARCHITECTURE.md §9.1.
|
| 🧪 POC: only the overview exists, showing sample data. See
| documentation/projects.md for the agreed scope.
|
*/

Route::middleware(['auth', 'auth.session'])
    ->prefix('projects')
    ->name('projects.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)
            ->middleware('permission:projects.dashboard.view')
            ->name('dashboard');
    });
