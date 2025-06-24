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
        'default_rows' => 24,
        'default_cols' => 80,
        'default_mode' => 'websocket', // Only websocket mode supported
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Terminal Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for WebSocket-based full terminal functionality
    |
    */
    'websocket' => [
        'host' => env('WEBSOCKET_TERMINAL_HOST', 'localhost'),
        'port' => env('WEBSOCKET_TERMINAL_PORT', 3001),
        'ssl' => env('WEBSOCKET_TERMINAL_SSL', false),
        'jwt_secret' => env('WEBSOCKET_TERMINAL_JWT_SECRET', env('APP_KEY')),
        'token_ttl' => env('WEBSOCKET_TERMINAL_TOKEN_TTL', 3600), // 1 hour
        'server_path' => env('WEBSOCKET_TERMINAL_SERVER_PATH', base_path('terminal-server/server.js')),
        'auto_start' => env('WEBSOCKET_TERMINAL_AUTO_START', false),
        'max_connections' => env('WEBSOCKET_TERMINAL_MAX_CONNECTIONS', 100),
        'connection_timeout' => env('WEBSOCKET_TERMINAL_CONNECTION_TIMEOUT', 300000), // 5 minutes
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