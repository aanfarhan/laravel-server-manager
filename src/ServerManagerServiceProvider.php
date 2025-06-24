<?php

namespace ServerManager\LaravelServerManager;

use Illuminate\Support\ServiceProvider;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\MonitoringService;
use ServerManager\LaravelServerManager\Services\LogService;
use ServerManager\LaravelServerManager\Services\TerminalService;
use ServerManager\LaravelServerManager\Services\WebSocketTerminalService;

class ServerManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SshService::class);
        $this->app->singleton(MonitoringService::class);
        $this->app->singleton(LogService::class);
        $this->app->singleton(TerminalService::class);
        $this->app->singleton(WebSocketTerminalService::class);

        $this->mergeConfigFrom(
            __DIR__ . '/../config/server-manager.php',
            'server-manager'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/server-manager.php' => config_path('server-manager.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/server-manager'),
        ], 'views');

        // Load routes with web middleware for session and CSRF support
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'server-manager');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}