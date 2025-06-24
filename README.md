# Laravel Server Manager Package

A comprehensive Laravel package for server management with SSH connectivity, git deployment, monitoring, and log viewing capabilities.

## Features

- **SSH Connection Management**: Connect to remote servers using password or private key authentication
- **Terminal Access**: Dual-mode terminal support (Simple + WebSocket with xterm.js)
- **Git Deployment**: Deploy applications from git repositories with customizable build scripts
- **Server Monitoring**: Real-time monitoring of CPU, memory, disk usage, processes, and services
- **Log Management**: View, search, download, and manage server log files
- **Web Interface**: Clean, responsive web interface built with Tailwind CSS and Alpine.js

## Installation

1. Install the package via Composer:

```bash
composer require omniglies/laravel-server-manager
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag=config --provider="ServerManager\LaravelServerManager\ServerManagerServiceProvider"
```

3. Publish and run migrations (optional, for storing server configurations):

```bash
php artisan vendor:publish --tag=migrations --provider="ServerManager\LaravelServerManager\ServerManagerServiceProvider"
php artisan migrate
```

4. Publish views (optional, for customization):

```bash
php artisan vendor:publish --tag=views --provider="ServerManager\LaravelServerManager\ServerManagerServiceProvider"
```

5. For full terminal functionality, install Node.js dependencies and start the WebSocket server:

```bash
cd vendor/omniglies/laravel-server-manager/terminal-server
npm install
```

## Configuration

The package publishes a configuration file to `config/server-manager.php`. You can customize:

- SSH connection settings
- Terminal configurations (Simple and WebSocket modes)
- WebSocket server settings and authentication
- Deployment configurations
- Monitoring thresholds
- Log management settings
- Security restrictions
- UI preferences

### WebSocket Terminal Configuration

Add these environment variables to your `.env` file:

```env
# WebSocket Terminal Server
WEBSOCKET_TERMINAL_HOST=localhost
WEBSOCKET_TERMINAL_PORT=3001
WEBSOCKET_TERMINAL_SSL=false
WEBSOCKET_TERMINAL_JWT_SECRET=your-jwt-secret-here
WEBSOCKET_TERMINAL_TOKEN_TTL=3600
WEBSOCKET_TERMINAL_MAX_CONNECTIONS=100
```

## Usage

### Web Interface

Visit `/server-manager` in your Laravel application to access the web interface.

### Terminal Access

The package provides two terminal modes:

#### Simple Terminal Mode (Default)
- Works immediately, no additional setup required
- Execute commands independently
- View command output in real-time
- Perfect for basic server administration

#### WebSocket Terminal Mode (Full Terminal)
Provides complete terminal functionality with interactive programs.

**Start the WebSocket server:**

```bash
# Development
cd vendor/omniglies/laravel-server-manager/terminal-server
npm run dev

# Production
npm start

# Or with PM2
pm2 start server.js --name "terminal-server"
```

**Features:**
- Real-time terminal emulation via xterm.js
- Interactive programs (nano, vim, top, htop)
- Full keyboard support and terminal resizing
- Copy/paste functionality
- WebSocket-based communication

### SSH Connection

```php
use ServerManager\LaravelServerManager\Services\SshService;

$sshService = app(SshService::class);

$config = [
    'host' => 'your-server.com',
    'username' => 'user',
    'password' => 'password', // or use 'private_key'
    'port' => 22
];

$connected = $sshService->connect($config);

if ($connected) {
    $result = $sshService->execute('ls -la');
    echo $result['output'];
}
```

### Deployment

```php
use ServerManager\LaravelServerManager\Services\DeploymentService;

$deploymentService = app(DeploymentService::class);

$config = [
    'repository' => 'https://github.com/user/repo.git',
    'deploy_path' => '/var/www/html',
    'branch' => 'main',
    'build_commands' => ['npm install', 'npm run build'],
    'post_deploy_commands' => ['php artisan migrate', 'php artisan cache:clear']
];

$result = $deploymentService->deploy($config);
```

### Monitoring

```php
use ServerManager\LaravelServerManager\Services\MonitoringService;

$monitoringService = app(MonitoringService::class);

$status = $monitoringService->getServerStatus();
$processes = $monitoringService->getProcesses(10);
$services = $monitoringService->getServiceStatus(['nginx', 'mysql']);
```

### Log Management

```php
use ServerManager\LaravelServerManager\Services\LogService;

$logService = app(LogService::class);

$logs = $logService->readLog('/var/log/nginx/error.log', 100);
$searchResults = $logService->searchLog('/var/log/syslog', 'error', 50);
$logFiles = $logService->getLogFiles('/var/log');
```

## API Routes

The package provides several API endpoints:

### Server Management
- `GET /server-manager/servers/status` - Get server status
- `POST /server-manager/servers/connect` - Connect to server
- `POST /server-manager/servers/disconnect` - Disconnect from server
- `GET /server-manager/servers/processes` - Get running processes
- `GET /server-manager/servers/services` - Get service status

### Deployment
- `POST /server-manager/deployments/deploy` - Deploy application
- `POST /server-manager/deployments/rollback` - Rollback deployment
- `GET /server-manager/deployments/status` - Get deployment status

### Terminal Management
- `POST /server-manager/terminal/create` - Create terminal session (Simple or WebSocket mode)
- `POST /server-manager/terminal/execute` - Execute command in Simple mode
- `POST /server-manager/terminal/close` - Close terminal session
- `POST /server-manager/terminal/websocket/token` - Generate WebSocket authentication token
- `POST /server-manager/terminal/websocket/revoke` - Revoke WebSocket token
- `GET /server-manager/terminal/websocket/status` - Check WebSocket server status
- `POST /server-manager/terminal/websocket/start-server` - Start WebSocket server
- `POST /server-manager/terminal/websocket/stop-server` - Stop WebSocket server

### Log Management
- `GET /server-manager/logs/files` - List log files
- `GET /server-manager/logs/read` - Read log file
- `GET /server-manager/logs/search` - Search in log file
- `GET /server-manager/logs/tail` - Tail log file
- `GET /server-manager/logs/download` - Download log file
- `POST /server-manager/logs/clear` - Clear log file
- `POST /server-manager/logs/rotate` - Rotate log file

## Security

The package includes several security features:

- Command filtering (allowed/blocked commands)
- Connection limits
- Credential encryption options
- SSH key authentication support
- CSRF protection on all forms

Make sure to:
- Use strong SSH credentials
- Limit SSH access to specific IP addresses
- Regularly rotate SSH keys
- Monitor server access logs

## Requirements

- PHP 8.2+
- Laravel 10.0+, 11.0+, or 12.0+
- phpseclib/phpseclib ^3.0
- firebase/php-jwt ^6.0
- Node.js 18+ (for WebSocket terminal server)

### Node.js Dependencies (for WebSocket Terminal)
- ws ^8.14.2
- ssh2 ^1.15.0
- jsonwebtoken ^9.0.2
- dotenv ^16.3.1

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, please create an issue in the GitHub repository or contact the maintainers.