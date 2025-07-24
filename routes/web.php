<?php

use Illuminate\Support\Facades\Route;
use ServerManager\LaravelServerManager\Http\Controllers\ServerController;
use ServerManager\LaravelServerManager\Http\Controllers\LogController;
use ServerManager\LaravelServerManager\Http\Controllers\TerminalController;

Route::prefix('server-manager')->name('server-manager.')->middleware('web')->group(function () {
    
    // Server Management Routes
    Route::get('/', function() {
        return view('server-manager::dashboard');
    })->name('index');
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
    
    // Terminal Session Routes (WebSocket only)
    Route::post('/terminal/create', [TerminalController::class, 'create'])->name('terminal.create');
    
    // WebSocket Terminal Routes
    Route::post('/terminal/websocket/token', [TerminalController::class, 'generateWebSocketToken'])->name('terminal.websocket.token');
    Route::post('/terminal/websocket/revoke', [TerminalController::class, 'revokeWebSocketToken'])->name('terminal.websocket.revoke');
    Route::get('/terminal/websocket/status', [TerminalController::class, 'webSocketStatus'])->name('terminal.websocket.status');
    Route::get('/terminal/websocket/tokens', [TerminalController::class, 'listWebSocketTokens'])->name('terminal.websocket.tokens');
    Route::post('/terminal/websocket/cleanup', [TerminalController::class, 'cleanupWebSocketTokens'])->name('terminal.websocket.cleanup');
    Route::post('/terminal/websocket/start-server', [TerminalController::class, 'startWebSocketServer'])->name('terminal.websocket.start');
    Route::post('/terminal/websocket/stop-server', [TerminalController::class, 'stopWebSocketServer'])->name('terminal.websocket.stop');
    
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