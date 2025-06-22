<?php

use Illuminate\Support\Facades\Route;
use ServerManager\LaravelServerManager\Http\Controllers\ServerController;
use ServerManager\LaravelServerManager\Http\Controllers\DeploymentController;
use ServerManager\LaravelServerManager\Http\Controllers\LogController;

Route::prefix('server-manager')->name('server-manager.')->group(function () {
    
    // Server Management Routes
    Route::get('/', [ServerController::class, 'index'])->name('index');
    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::post('/servers/connect', [ServerController::class, 'connect'])->name('servers.connect');
    Route::post('/servers/test-connection', [ServerController::class, 'testConnection'])->name('servers.test');
    Route::post('/servers/disconnect', [ServerController::class, 'disconnect'])->name('servers.disconnect');
    Route::get('/servers/status', [ServerController::class, 'status'])->name('servers.status');
    Route::get('/servers/processes', [ServerController::class, 'processes'])->name('servers.processes');
    Route::get('/servers/services', [ServerController::class, 'services'])->name('servers.services');
    
    // Deployment Routes
    Route::get('/deployments', [DeploymentController::class, 'index'])->name('deployments.index');
    Route::post('/deployments/deploy', [DeploymentController::class, 'deploy'])->name('deployments.deploy');
    Route::post('/deployments/rollback', [DeploymentController::class, 'rollback'])->name('deployments.rollback');
    Route::get('/deployments/status', [DeploymentController::class, 'status'])->name('deployments.status');
    
    // Log Management Routes
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/files', [LogController::class, 'files'])->name('logs.files');
    Route::get('/logs/read', [LogController::class, 'read'])->name('logs.read');
    Route::get('/logs/search', [LogController::class, 'search'])->name('logs.search');
    Route::get('/logs/tail', [LogController::class, 'tail'])->name('logs.tail');
    Route::get('/logs/download', [LogController::class, 'download'])->name('logs.download');
    Route::post('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');
    Route::post('/logs/rotate', [LogController::class, 'rotate'])->name('logs.rotate');
    Route::get('/logs/errors', [LogController::class, 'errors'])->name('logs.errors');
    
});