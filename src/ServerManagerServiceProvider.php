<?php

namespace ServerManager\LaravelServerManager;

use Illuminate\Support\ServiceProvider;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\DeploymentService;
use ServerManager\LaravelServerManager\Services\MonitoringService;
use ServerManager\LaravelServerManager\Services\LogService;

class ServerManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SshService::class);
        $this->app->singleton(DeploymentService::class);
        $this->app->singleton(MonitoringService::class);
        $this->app->singleton(LogService::class);

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

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'server-manager');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}