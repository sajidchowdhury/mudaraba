<?php

use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/design-system', [DesignSystemController::class, 'index']);
