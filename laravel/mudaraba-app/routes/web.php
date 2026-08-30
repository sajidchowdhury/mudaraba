<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes (no auth required)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index']);
Route::get('/design-system', [DesignSystemController::class, 'index']);

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Admin — permission management (superadmin only)
    |----------------------------------------------------------------------
    */
    Route::middleware('superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions');
        Route::put('/permissions/{user}/{menu}', [PermissionController::class, 'update'])
            ->name('permissions.update');
    });
});
