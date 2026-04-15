<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, TranslationController};


// Public routes (no authentication required)
Route::post('/login', [AuthController::class, 'login'])
    ->name('login')
    ->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute

    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // })->middleware('auth:sanctum');

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAllDevices'])
        ->name('logout.all');
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->name('token.refresh');
    Route::get('/me', [AuthController::class, 'me'])->name('user.profile');

    // Translation routes
    Route::prefix('translations')->group(function () {
        // CRUD Operations
        Route::post('/', [TranslationController::class, 'store'])->name('translations.store');
        Route::put('/{id}', [TranslationController::class, 'update'])->name('translations.update');
        Route::get('/{id}', [TranslationController::class, 'show'])->name('translations.show');
        Route::delete('/{id}', [TranslationController::class, 'destroy'])->name('translations.destroy');

        // Search and Export
        Route::get('/search', [TranslationController::class, 'search'])->name('translations.search');
        Route::get('/export', [TranslationController::class, 'export'])->name('translations.export');

        // Utility endpoints
        Route::get('/locales/available', [TranslationController::class, 'getAvailableLocales']);
        Route::get('/tags/list', [TranslationController::class, 'getTags']);
    });
});

