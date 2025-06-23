<?php

namespace ServerManager\LaravelServerManager\Tests\Unit;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\ServerManagerServiceProvider;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\TerminalService;
use ServerManager\LaravelServerManager\Services\MonitoringService;
use ServerManager\LaravelServerManager\Services\LogService;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_services()
    {
        $this->assertInstanceOf(SshService::class, $this->app->make(SshService::class));
        $this->assertInstanceOf(TerminalService::class, $this->app->make(TerminalService::class));
        $this->assertInstanceOf(MonitoringService::class, $this->app->make(MonitoringService::class));
        $this->assertInstanceOf(LogService::class, $this->app->make(LogService::class));
    }

    public function test_services_are_singletons()
    {
        $sshService1 = $this->app->make(SshService::class);
        $sshService2 = $this->app->make(SshService::class);
        
        $this->assertSame($sshService1, $sshService2);

        $terminalService1 = $this->app->make(TerminalService::class);
        $terminalService2 = $this->app->make(TerminalService::class);
        
        $this->assertSame($terminalService1, $terminalService2);

        $monitoringService1 = $this->app->make(MonitoringService::class);
        $monitoringService2 = $this->app->make(MonitoringService::class);
        
        $this->assertSame($monitoringService1, $monitoringService2);

        $logService1 = $this->app->make(LogService::class);
        $logService2 = $this->app->make(LogService::class);
        
        $this->assertSame($logService1, $logService2);
    }

    public function test_config_is_merged()
    {
        $this->assertNotNull(config('server-manager'));
        $this->assertNotNull(config('server-manager.ssh'));
        $this->assertNotNull(config('server-manager.terminal'));
        $this->assertNotNull(config('server-manager.monitoring'));
        $this->assertNotNull(config('server-manager.logs'));
    }

    public function test_routes_are_loaded()
    {
        $this->assertTrue(\Route::has('server-manager.servers.index'));
        $this->assertTrue(\Route::has('server-manager.servers.connect'));
        $this->assertTrue(\Route::has('server-manager.terminal.create'));
        $this->assertTrue(\Route::has('server-manager.terminal.execute'));
        $this->assertTrue(\Route::has('server-manager.logs.index'));
        $this->assertTrue(\Route::has('server-manager.logs.read'));
    }

    public function test_service_provider_is_deferred()
    {
        $provider = new ServerManagerServiceProvider($this->app);
        
        // Service provider should not be deferred (it provides routes and views)
        $this->assertFalse($provider->isDeferred());
    }

    public function test_terminal_service_has_ssh_dependency()
    {
        $terminalService = $this->app->make(TerminalService::class);
        
        // Use reflection to check if SshService is injected
        $reflection = new \ReflectionClass($terminalService);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals(SshService::class, $parameters[0]->getType()->getName());
    }

    public function test_monitoring_service_has_ssh_dependency()
    {
        $monitoringService = $this->app->make(MonitoringService::class);
        
        // Use reflection to check if SshService is injected
        $reflection = new \ReflectionClass($monitoringService);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals(SshService::class, $parameters[0]->getType()->getName());
    }

    public function test_log_service_has_ssh_dependency()
    {
        $logService = $this->app->make(LogService::class);
        
        // Use reflection to check if SshService is injected
        $reflection = new \ReflectionClass($logService);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals(SshService::class, $parameters[0]->getType()->getName());
    }
}