<?php

use App\Http\Controllers\Api\MarketChartController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatasetExportController;
use Illuminate\Support\Facades\Route;

Route::get('/api/chart/{pair}', [MarketChartController::class, 'getCandles'])
    ->name('api.chart.candles');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/export/csv', [DatasetExportController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export/json', [DatasetExportController::class, 'exportJson'])->name('export.json');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
