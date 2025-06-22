<?php

namespace ServerManager\LaravelServerManager\Tests\Feature;

use ServerManager\LaravelServerManager\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_default_configuration_structure()
    {
        $config = config('server-manager');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('ssh', $config);
        $this->assertArrayHasKey('deployment', $config);
        $this->assertArrayHasKey('monitoring', $config);
        $this->assertArrayHasKey('logs', $config);
        $this->assertArrayHasKey('security', $config);
        $this->assertArrayHasKey('ui', $config);
        $this->assertArrayHasKey('cache', $config);
    }

    public function test_ssh_configuration()
    {
        $sshConfig = config('server-manager.ssh');
        
        $this->assertArrayHasKey('timeout', $sshConfig);
        $this->assertArrayHasKey('port', $sshConfig);
        $this->assertArrayHasKey('auth_methods', $sshConfig);
        
        $this->assertIsInt($sshConfig['timeout']);
        $this->assertIsInt($sshConfig['port']);
        $this->assertIsArray($sshConfig['auth_methods']);
        
        $this->assertEquals(30, $sshConfig['timeout']);
        $this->assertEquals(22, $sshConfig['port']);
        $this->assertContains('password', $sshConfig['auth_methods']);
        $this->assertContains('key', $sshConfig['auth_methods']);
    }

    public function test_deployment_configuration()
    {
        $deploymentConfig = config('server-manager.deployment');
        
        $this->assertArrayHasKey('default_branch', $deploymentConfig);
        $this->assertArrayHasKey('timeout', $deploymentConfig);
        $this->assertArrayHasKey('backup_before_deploy', $deploymentConfig);
        $this->assertArrayHasKey('rollback_limit', $deploymentConfig);
        
        $this->assertIsString($deploymentConfig['default_branch']);
        $this->assertIsInt($deploymentConfig['timeout']);
        $this->assertIsBool($deploymentConfig['backup_before_deploy']);
        $this->assertIsInt($deploymentConfig['rollback_limit']);
        
        $this->assertEquals('main', $deploymentConfig['default_branch']);
        $this->assertEquals(300, $deploymentConfig['timeout']);
        $this->assertTrue($deploymentConfig['backup_before_deploy']);
        $this->assertEquals(5, $deploymentConfig['rollback_limit']);
    }

    public function test_monitoring_configuration()
    {
        $monitoringConfig = config('server-manager.monitoring');
        
        $this->assertArrayHasKey('refresh_interval', $monitoringConfig);
        $this->assertArrayHasKey('warning_thresholds', $monitoringConfig);
        $this->assertArrayHasKey('critical_thresholds', $monitoringConfig);
        $this->assertArrayHasKey('default_services', $monitoringConfig);
        
        $this->assertIsInt($monitoringConfig['refresh_interval']);
        $this->assertIsArray($monitoringConfig['warning_thresholds']);
        $this->assertIsArray($monitoringConfig['critical_thresholds']);
        $this->assertIsArray($monitoringConfig['default_services']);
        
        // Check threshold structure
        foreach (['cpu', 'memory', 'disk'] as $metric) {
            $this->assertArrayHasKey($metric, $monitoringConfig['warning_thresholds']);
            $this->assertArrayHasKey($metric, $monitoringConfig['critical_thresholds']);
            $this->assertIsInt($monitoringConfig['warning_thresholds'][$metric]);
            $this->assertIsInt($monitoringConfig['critical_thresholds'][$metric]);
        }
        
        // Check default services
        $this->assertContains('nginx', $monitoringConfig['default_services']);
        $this->assertContains('mysql', $monitoringConfig['default_services']);
        $this->assertContains('redis', $monitoringConfig['default_services']);
    }

    public function test_logs_configuration()
    {
        $logsConfig = config('server-manager.logs');
        
        $this->assertArrayHasKey('default_lines', $logsConfig);
        $this->assertArrayHasKey('max_lines', $logsConfig);
        $this->assertArrayHasKey('default_paths', $logsConfig);
        $this->assertArrayHasKey('auto_refresh', $logsConfig);
        $this->assertArrayHasKey('refresh_interval', $logsConfig);
        
        $this->assertIsInt($logsConfig['default_lines']);
        $this->assertIsInt($logsConfig['max_lines']);
        $this->assertIsArray($logsConfig['default_paths']);
        $this->assertIsBool($logsConfig['auto_refresh']);
        $this->assertIsInt($logsConfig['refresh_interval']);
        
        $this->assertEquals(100, $logsConfig['default_lines']);
        $this->assertEquals(1000, $logsConfig['max_lines']);
        $this->assertTrue($logsConfig['auto_refresh']);
        $this->assertEquals(5, $logsConfig['refresh_interval']);
        
        // Check default log paths
        $this->assertGreaterThan(0, count($logsConfig['default_paths']));
        foreach ($logsConfig['default_paths'] as $path) {
            $this->assertIsString($path);
            $this->assertStringStartsWith('/', $path);
        }
    }

    public function test_security_configuration()
    {
        $securityConfig = config('server-manager.security');
        
        $this->assertArrayHasKey('encrypt_credentials', $securityConfig);
        $this->assertArrayHasKey('max_concurrent_connections', $securityConfig);
        $this->assertArrayHasKey('connection_timeout', $securityConfig);
        $this->assertArrayHasKey('allowed_commands', $securityConfig);
        $this->assertArrayHasKey('blocked_commands', $securityConfig);
        
        $this->assertIsBool($securityConfig['encrypt_credentials']);
        $this->assertIsInt($securityConfig['max_concurrent_connections']);
        $this->assertIsInt($securityConfig['connection_timeout']);
        $this->assertIsArray($securityConfig['allowed_commands']);
        $this->assertIsArray($securityConfig['blocked_commands']);
        
        // Check security measures
        $this->assertTrue($securityConfig['encrypt_credentials']);
        $this->assertGreaterThan(0, $securityConfig['max_concurrent_connections']);
        $this->assertGreaterThan(0, $securityConfig['connection_timeout']);
        
        // Check command restrictions
        $this->assertContains('git', $securityConfig['allowed_commands']);
        $this->assertContains('top', $securityConfig['allowed_commands']);
        $this->assertContains('rm', $securityConfig['blocked_commands']);
        $this->assertContains('shutdown', $securityConfig['blocked_commands']);
    }

    public function test_ui_configuration()
    {
        $uiConfig = config('server-manager.ui');
        
        $this->assertArrayHasKey('theme', $uiConfig);
        $this->assertArrayHasKey('items_per_page', $uiConfig);
        $this->assertArrayHasKey('enable_realtime_updates', $uiConfig);
        $this->assertArrayHasKey('show_command_output', $uiConfig);
        
        $this->assertIsString($uiConfig['theme']);
        $this->assertIsInt($uiConfig['items_per_page']);
        $this->assertIsBool($uiConfig['enable_realtime_updates']);
        $this->assertIsBool($uiConfig['show_command_output']);
    }

    public function test_cache_configuration()
    {
        $cacheConfig = config('server-manager.cache');
        
        $this->assertArrayHasKey('driver', $cacheConfig);
        $this->assertArrayHasKey('ttl', $cacheConfig);
        $this->assertArrayHasKey('prefix', $cacheConfig);
        $this->assertArrayHasKey('enable_monitoring_cache', $cacheConfig);
        $this->assertArrayHasKey('enable_log_cache', $cacheConfig);
        
        $this->assertIsString($cacheConfig['driver']);
        $this->assertIsInt($cacheConfig['ttl']);
        $this->assertIsString($cacheConfig['prefix']);
        $this->assertIsBool($cacheConfig['enable_monitoring_cache']);
        $this->assertIsBool($cacheConfig['enable_log_cache']);
        
        $this->assertEquals('file', $cacheConfig['driver']);
        $this->assertEquals(300, $cacheConfig['ttl']);
        $this->assertEquals('server_manager_', $cacheConfig['prefix']);
    }

    public function test_threshold_validation()
    {
        $warningThresholds = config('server-manager.monitoring.warning_thresholds');
        $criticalThresholds = config('server-manager.monitoring.critical_thresholds');
        
        foreach (['cpu', 'memory', 'disk'] as $metric) {
            $warning = $warningThresholds[$metric];
            $critical = $criticalThresholds[$metric];
            
            $this->assertGreaterThan(0, $warning, "Warning threshold for {$metric} should be positive");
            $this->assertLessThanOrEqual(100, $warning, "Warning threshold for {$metric} should not exceed 100");
            $this->assertGreaterThan($warning, $critical, "Critical threshold for {$metric} should be higher than warning");
            $this->assertLessThanOrEqual(100, $critical, "Critical threshold for {$metric} should not exceed 100");
        }
    }

    public function test_config_can_be_overridden()
    {
        // Test that config can be overridden at runtime
        config(['server-manager.ssh.timeout' => 60]);
        
        $this->assertEquals(60, config('server-manager.ssh.timeout'));
        
        // Reset to original value
        config(['server-manager.ssh.timeout' => 30]);
        
        $this->assertEquals(30, config('server-manager.ssh.timeout'));
    }
}