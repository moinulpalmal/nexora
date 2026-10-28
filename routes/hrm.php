<?php

use App\Http\Controllers\Hrm\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HRM module
|--------------------------------------------------------------------------
|
| Each company's workforce. Name prefix `hrm.`, URL prefix `hrm/`. Every
| route is gated by a permission — see ARCHITECTURE.md §9.1.
|
| 🧪 POC: only the overview exists, showing sample data. See
| documentation/hrm.md for the agreed scope.
|
*/

Route::middleware(['auth', 'auth.session'])
    ->prefix('hrm')
    ->name('hrm.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)
            ->middleware('permission:hrm.dashboard.view')
            ->name('dashboard');
    });
