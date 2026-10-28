<?php

use App\Http\Controllers\Accounting\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounting module (menu: Accounts)
|--------------------------------------------------------------------------
|
| Each company's books, customers and sales invoices. Name prefix
| `accounting.`, URL prefix `accounting/`. Every route is gated by a
| permission — see ARCHITECTURE.md §9.1.
|
| 🧪 POC: only the overview exists, showing sample data. See
| documentation/accounting.md for the agreed scope.
|
*/

Route::middleware(['auth', 'auth.session'])
    ->prefix('accounting')
    ->name('accounting.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)
            ->middleware('permission:accounting.dashboard.view')
            ->name('dashboard');
    });
