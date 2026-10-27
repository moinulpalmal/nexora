<?php

use App\Http\Controllers\Training\TrainingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Training module
|--------------------------------------------------------------------------
|
| The in-app manual: one guide per screen, in English and Bangla. Ungated on
| purpose — any signed-in user may read any guide, including the guide to a
| screen they cannot open yet. The key is a registry key from
| config/training.php, such as `admin.users`; dots are legal in a route
| parameter, slashes are not.
|
| See ARCHITECTURE.md §5 and documentation/training.md.
|
*/

Route::middleware(['auth', 'auth.session'])
    ->prefix('training')
    ->name('training.')
    ->group(function (): void {
        Route::get('/', [TrainingController::class, 'index'])->name('index');
        Route::get('{key}/panel', [TrainingController::class, 'panel'])->name('panel');
        Route::get('{key}', [TrainingController::class, 'show'])->name('show');
    });
