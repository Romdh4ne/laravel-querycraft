<?php

use Illuminate\Support\Facades\Route;
use Romdh4ne\QueryCraft\Http\Controllers\DashboardController;

Route::prefix(config('querycraft.dashboard_route', 'querycraft'))->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('querycraft.dashboard');
    Route::post('/analyze', [DashboardController::class, 'analyze'])->name('querycraft.analyze');
    Route::get('/config', [DashboardController::class, 'getConfig'])->name('querycraft.config.get');
    Route::post('/config', [DashboardController::class, 'saveConfig'])->name('querycraft.config.save');
    Route::delete('/config', [DashboardController::class, 'resetConfig'])->name('querycraft.config.reset');
    Route::get('/routes', [DashboardController::class, 'routes'])->name('querycraft.routes');
});