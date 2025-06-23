<?php

use Illuminate\Support\Facades\Route;
use ServerManager\LaravelServerManager\Http\Controllers\ServerController;
use ServerManager\LaravelServerManager\Http\Controllers\LogController;
use ServerManager\LaravelServerManager\Http\Controllers\TerminalController;

Route::prefix('server-manager')->name('server-manager.')->middleware('web')->group(function () {
    
    // Server Management Routes
    Route::get('/', [ServerController::class, 'index'])->name('index');
    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::get('/servers/list/all', [ServerController::class, 'list'])->name('servers.list');
    Route::get('/servers/status', [ServerController::class, 'status'])->name('servers.status');
    Route::get('/servers/processes', [ServerController::class, 'processes'])->name('servers.processes');
    Route::get('/servers/services', [ServerController::class, 'services'])->name('servers.services');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
    Route::post('/servers/connect', [ServerController::class, 'connect'])->name('servers.connect');
    Route::post('/servers/test-connection', [ServerController::class, 'testConnection'])->name('servers.test');
    Route::post('/servers/disconnect', [ServerController::class, 'disconnect'])->name('servers.disconnect');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::get('/servers/{server}/edit', [ServerController::class, 'edit'])->name('servers.edit');
    Route::put('/servers/{server}', [ServerController::class, 'update'])->name('servers.update');
    Route::delete('/servers/{server}', [ServerController::class, 'destroy'])->name('servers.destroy');
    
    // Terminal Session Routes
    Route::post('/terminal/create', [TerminalController::class, 'create'])->name('terminal.create');
    Route::post('/terminal/execute', [TerminalController::class, 'execute'])->name('terminal.execute');
    Route::post('/terminal/input', [TerminalController::class, 'input'])->name('terminal.input');
    Route::get('/terminal/output', [TerminalController::class, 'output'])->name('terminal.output');
    Route::post('/terminal/resize', [TerminalController::class, 'resize'])->name('terminal.resize');
    Route::post('/terminal/close', [TerminalController::class, 'close'])->name('terminal.close');
    Route::get('/terminal/info', [TerminalController::class, 'info'])->name('terminal.info');
    Route::get('/terminal/sessions', [TerminalController::class, 'sessions'])->name('terminal.sessions');
    Route::post('/terminal/cleanup', [TerminalController::class, 'cleanup'])->name('terminal.cleanup');
    Route::post('/terminal/bulk', [TerminalController::class, 'bulk'])->name('terminal.bulk');
    
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