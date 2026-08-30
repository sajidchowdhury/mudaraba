<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvestorController;
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
    | Investors (permission-guarded)
    |----------------------------------------------------------------------
    */
    Route::prefix('investors')->name('investors.')->group(function () {
        Route::get('/', [InvestorController::class, 'index'])
            ->middleware('permission:investors.index')
            ->name('index');

        Route::get('/new', [InvestorController::class, 'create'])
            ->middleware('permission:investors.new')
            ->name('new');

        Route::post('/', [InvestorController::class, 'store'])
            ->middleware('permission:investors.new')
            ->name('store');

        Route::get('/{investor}', [InvestorController::class, 'show'])
            ->middleware('permission:investors.index')
            ->name('show');

        Route::get('/{investor}/edit', [InvestorController::class, 'edit'])
            ->middleware('permission:investors.index')
            ->name('edit');

        Route::put('/{investor}', [InvestorController::class, 'update'])
            ->middleware('permission:investors.index')
            ->name('update');

        Route::delete('/{investor}', [InvestorController::class, 'destroy'])
            ->middleware('permission:investors.index')
            ->name('destroy');
    });

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
