<?php

namespace ServerManager\LaravelServerManager\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ServerManager\LaravelServerManager\ServerManagerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            ServerManagerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('server-manager', [
            'ssh' => [
                'timeout' => 30,
                'port' => 22,
                'auth_methods' => ['password', 'key'],
            ],
            'deployment' => [
                'default_branch' => 'main',
                'timeout' => 300,
                'backup_before_deploy' => true,
                'rollback_limit' => 5,
            ],
            'monitoring' => [
                'refresh_interval' => 30,
                'warning_thresholds' => [
                    'cpu' => 80,
                    'memory' => 80,
                    'disk' => 80,
                ],
                'critical_thresholds' => [
                    'cpu' => 90,
                    'memory' => 90,
                    'disk' => 90,
                ],
                'default_services' => [
                    'nginx',
                    'mysql',
                    'redis',
                ],
            ],
            'logs' => [
                'default_lines' => 100,
                'max_lines' => 1000,
                'default_paths' => [
                    '/var/log/nginx/error.log',
                    '/var/log/syslog',
                ],
                'auto_refresh' => true,
                'refresh_interval' => 5,
            ],
            'security' => [
                'encrypt_credentials' => true,
                'max_concurrent_connections' => 10,
                'connection_timeout' => 30,
                'allowed_commands' => [
                    'top', 'ps', 'free', 'df', 'uptime', 'who', 'w',
                    'tail', 'head', 'cat', 'grep', 'less', 'more',
                    'git',
                    'uname', 'lsb_release', 'systemctl',
                ],
                'blocked_commands' => [
                    'rm', 'rmdir', 'dd', 'mkfs', 'fdisk', 'parted',
                    'passwd', 'useradd', 'userdel', 'usermod',
                    'shutdown', 'reboot', 'halt', 'poweroff',
                ],
            ],
            'ui' => [
                'theme' => 'dark',
                'items_per_page' => 20,
                'enable_realtime_updates' => true,
                'show_command_output' => true,
            ],
            'cache' => [
                'driver' => 'file',
                'ttl' => 300,
                'prefix' => 'server_manager_',
                'enable_monitoring_cache' => true,
                'enable_log_cache' => false,
            ],
        ]);
    }
}