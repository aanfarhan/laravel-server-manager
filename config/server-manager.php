<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SSH Connection Settings
    |--------------------------------------------------------------------------
    |
    | These are the default settings for SSH connections. You can override
    | these settings when creating individual connections.
    |
    */
    'ssh' => [
        'timeout' => 30,
        'port' => 22,
        'auth_methods' => ['password', 'key'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Terminal Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for web-based terminal sessions
    |
    */
    'terminal' => [
        'session_timeout' => 3600, // 1 hour in seconds
        'max_sessions_per_server' => 5,
        'output_buffer_size' => 8192,
        'default_rows' => 24,
        'default_cols' => 80,
        'polling_interval' => 500, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for server monitoring
    |
    */
    'monitoring' => [
        'refresh_interval' => 30, // seconds
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
            'apache2',
            'mysql',
            'postgresql',
            'redis',
            'php-fpm',
            'supervisor',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for log viewing and management
    |
    */
    'logs' => [
        'default_lines' => 100,
        'max_lines' => 1000,
        'default_paths' => [
            '/var/log/nginx/access.log',
            '/var/log/nginx/error.log',
            '/var/log/apache2/access.log',
            '/var/log/apache2/error.log',
            '/var/log/mysql/error.log',
            '/var/log/syslog',
            '/var/log/auth.log',
        ],
        'auto_refresh' => true,
        'refresh_interval' => 5, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration
    |
    */
    'security' => [
        'encrypt_credentials' => true,
        'max_concurrent_connections' => 10,
        'connection_timeout' => 30,
        'allowed_commands' => [
            // Monitoring commands
            'top', 'ps', 'free', 'df', 'uptime', 'who', 'w',
            // Log commands
            'tail', 'head', 'cat', 'grep', 'less', 'more',
            // Git commands
            'git',
            // System info commands
            'uname', 'lsb_release', 'systemctl',
        ],
        'blocked_commands' => [
            'rm', 'rmdir', 'dd', 'mkfs', 'fdisk', 'parted',
            'passwd', 'useradd', 'userdel', 'usermod',
            'shutdown', 'reboot', 'halt', 'poweroff',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    |
    | User interface configuration
    |
    */
    'ui' => [
        'theme' => 'dark',
        'items_per_page' => 20,
        'enable_realtime_updates' => true,
        'show_command_output' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Caching configuration for performance optimization
    |
    */
    'cache' => [
        'driver' => 'file',
        'ttl' => 300, // 5 minutes
        'prefix' => 'server_manager_',
        'enable_monitoring_cache' => true,
        'enable_log_cache' => false,
    ],
];