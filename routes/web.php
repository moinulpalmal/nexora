<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
 * There is no public landing page. `/` keeps the `home` name because the auth
 * layouts and Wayfinder's generated route helpers depend on `home()` resolving.
 */
Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
