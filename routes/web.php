<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
 * There is no public landing page. `/` keeps the `home` name because the auth
 * layouts and Wayfinder's generated route helpers depend on `home()` resolving.
 */
Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    /*
     * The bell — module-less, since any module may notify. Each route reads the
     * signed-in user's own notifications only; no permission gate, because a
     * notification was sent to its reader.
     */
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::put('notifications/{notification}/read', [NotificationController::class, 'read'])
        ->whereUuid('notification')
        ->name('notifications.read');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/training.php';
require __DIR__.'/projects.php';
require __DIR__.'/accounting.php';
require __DIR__.'/hrm.php';
