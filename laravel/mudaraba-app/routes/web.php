<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvestmentTransactionController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\InvestorProfitController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonthStatusController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfitAdjustmentController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\SectorProfitController;
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

    // Investors (permission-guarded)
    Route::prefix('investors')->name('investors.')->group(function () {
        Route::get('/', [InvestorController::class, 'index'])->middleware('permission:investors.index')->name('index');
        Route::get('/new', [InvestorController::class, 'create'])->middleware('permission:investors.new')->name('new');
        Route::post('/', [InvestorController::class, 'store'])->middleware('permission:investors.new')->name('store');
        Route::get('/{investor}', [InvestorController::class, 'show'])->middleware('permission:investors.index')->name('show');
        Route::get('/{investor}/edit', [InvestorController::class, 'edit'])->middleware('permission:investors.index')->name('edit');
        Route::put('/{investor}', [InvestorController::class, 'update'])->middleware('permission:investors.index')->name('update');
        Route::delete('/{investor}', [InvestorController::class, 'destroy'])->middleware('permission:investors.index')->name('destroy');
    });

    // Sectors (permission-guarded)
    Route::prefix('sectors')->name('sectors.')->group(function () {
        Route::get('/', [SectorController::class, 'index'])->middleware('permission:sectors.index')->name('index');
        Route::get('/new', [SectorController::class, 'create'])->middleware('permission:sectors.new')->name('new');
        Route::post('/', [SectorController::class, 'store'])->middleware('permission:sectors.new')->name('store');
        Route::get('/{sector}', [SectorController::class, 'show'])->middleware('permission:sectors.index')->name('show');
        Route::get('/{sector}/edit', [SectorController::class, 'edit'])->middleware('permission:sectors.index')->name('edit');
        Route::put('/{sector}', [SectorController::class, 'update'])->middleware('permission:sectors.index')->name('update');
        Route::delete('/{sector}', [SectorController::class, 'destroy'])->middleware('permission:sectors.index')->name('destroy');
    });

    // Directors / M/Y (permission-guarded)
    Route::prefix('directors')->name('directors.')->group(function () {
        Route::get('/', [DirectorController::class, 'index'])->middleware('permission:directors.index')->name('index');
        Route::get('/new', [DirectorController::class, 'create'])->middleware('permission:directors.new')->name('new');
        Route::post('/', [DirectorController::class, 'store'])->middleware('permission:directors.new')->name('store');
        Route::get('/{director}', [DirectorController::class, 'show'])->middleware('permission:directors.index')->name('show');
        Route::get('/{director}/edit', [DirectorController::class, 'edit'])->middleware('permission:directors.index')->name('edit');
        Route::put('/{director}', [DirectorController::class, 'update'])->middleware('permission:directors.index')->name('update');
        Route::delete('/{director}', [DirectorController::class, 'destroy'])->middleware('permission:directors.index')->name('destroy');
    });

    // Investment Transactions (permission-guarded)
    Route::prefix('investments')->name('investments.')->group(function () {
        Route::get('/', [InvestmentTransactionController::class, 'index'])->middleware('permission:investments.index')->name('index');
        Route::post('/', [InvestmentTransactionController::class, 'store'])->middleware('permission:investments.index')->name('store');
        Route::delete('/{transaction}', [InvestmentTransactionController::class, 'destroy'])->middleware('permission:investments.index')->name('destroy');
        Route::get('/balance/{investor}', [InvestmentTransactionController::class, 'balance'])->middleware('permission:investments.index')->name('balance');
    });

    // Sector Profit Entry (permission-guarded)
    Route::prefix('profit/sector')->name('profit.sector.')->group(function () {
        Route::get('/', [SectorProfitController::class, 'index'])->middleware('permission:profit.sector')->name('index');
        Route::post('/', [SectorProfitController::class, 'store'])->middleware('permission:profit.sector')->name('store');
    });

    // Investor Profit View — the "For Sajid" page (permission-guarded)
    Route::get('/profit/investor', [InvestorProfitController::class, 'index'])
        ->middleware('permission:profit.investor')
        ->name('profit.investor.index');

    // Profit Adjustments (unified Fund A + Fund B + Direct)
    Route::prefix('adjustments')->name('adjustments.')->group(function () {
        Route::get('/', [ProfitAdjustmentController::class, 'index'])->middleware('permission:adjustments.type-c')->name('index');
        Route::post('/batch', [ProfitAdjustmentController::class, 'storeBatch'])->middleware('permission:adjustments.type-c')->name('store-batch');
        Route::post('/direct', [ProfitAdjustmentController::class, 'storeDirect'])->middleware('permission:adjustments.type-c')->name('store-direct');
        Route::delete('/{adjustment}', [ProfitAdjustmentController::class, 'destroy'])->middleware('permission:adjustments.type-c')->name('destroy');
    });

    // Month Closing & Lock (superadmin only for lock/unlock)
    Route::prefix('month-close')->name('month-close.')->group(function () {
        Route::get('/', [MonthStatusController::class, 'index'])->middleware('permission:profit.investor')->name('index');
        Route::post('/lock', [MonthStatusController::class, 'lock'])->middleware('superadmin')->name('lock');
        Route::post('/unlock', [MonthStatusController::class, 'unlock'])->middleware('superadmin')->name('unlock');
    });

    // Admin — permission management (superadmin only)
    Route::middleware('superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions');
        Route::put('/permissions/{user}/{menu}', [PermissionController::class, 'update'])->name('permissions.update');
    });
});
