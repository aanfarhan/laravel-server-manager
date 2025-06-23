# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel Server Manager Package - A comprehensive package for server management with SSH connectivity, git deployment, monitoring, and log viewing capabilities. The package provides both web interface and programmatic API access for managing remote servers.

## Essential Commands

### Testing
- `composer test` - Run all tests (PHPUnit with both Unit and Feature test suites)
- `composer test-unit` - Run only unit tests
- `composer test-feature` - Run only feature tests
- `composer test-coverage` - Generate test coverage report (outputs to coverage/ directory)
- `php vendor/bin/phpunit --filter="test_method_name"` - Run specific test method
- `php vendor/bin/phpunit tests/Feature/ServerControllerTest.php` - Run specific test class

### Development Dependencies
- `composer install` - Install all dependencies including dev dependencies
- `composer dump-autoload` - Refresh autoloader after file structure changes

## Architecture Overview

### Core Services Architecture
The package follows a service-oriented architecture with dependency injection:

- **SshService**: Core SSH connection management using phpseclib/phpseclib
- **MonitoringService**: Server monitoring (CPU, memory, disk, processes, services)
- **DeploymentService**: Git deployment automation with build scripts
- **LogService**: Log file management and operations

All services are registered as singletons in `ServerManagerServiceProvider` and depend on `SshService` for remote operations.

### Request Flow Pattern
1. **Controller** receives HTTP request
2. **FormRequest** validates input data
3. **Controller** calls appropriate **Service**
4. **Service** uses **SshService** for remote operations
5. **Model** persists state and monitoring data
6. Response returned as JSON API or view

### Database Models
- **Server**: Server configurations with encrypted credentials
- **Deployment**: Deployment history and status tracking  
- **MonitoringLog**: Time-series monitoring data storage

### Route Organization Critical Note
Routes are **order-dependent** in `routes/web.php`. Specific routes (like `/servers/status`) must be defined BEFORE parameterized routes (like `/servers/{server}`) to prevent route conflicts. This has been a source of bugs.

## Testing Strategy

### TDD Approach (Mandatory)
This project follows strict Test-Driven Development. Always follow the Red-Green-Refactor cycle:

1. **Red**: Write failing test first
2. **Green**: Write minimal code to pass
3. **Refactor**: Improve while keeping tests green

### Test Structure
- `tests/Unit/` - Service layer tests with full mocking
- `tests/Feature/` - HTTP controller tests with database integration
- All tests use `RefreshDatabase` trait for database isolation
- SSH operations are mocked using Mockery to avoid real connections

### Critical Test Patterns
```php
// Service mocking pattern used throughout
$this->mockSshService = Mockery::mock(SshService::class);
$this->app->instance(SshService::class, $this->mockSshService);

// Controller manual lookup pattern (route model binding had issues)
$server = Server::findOrFail($server); // Instead of type-hinted parameter
```

## Key Technical Constraints

### Credential Encryption
Server passwords and private keys are automatically encrypted using Laravel's `Crypt` facade before database storage. The `Server` model handles encryption/decryption transparently, but this has caused bugs when updating servers without providing new credentials.

### Session-Based Connection Tracking
SSH connections are tracked via `session('connected_server_id')`. This creates state dependencies between requests and has implications for disconnect/reconnect logic.

### Manual Disconnect Detection
The status endpoint includes auto-reconnect logic that must distinguish between connection failures (auto-reconnect allowed) and manual disconnects (auto-reconnect prevented). Uses combination of explicit `server_id` parameter, missing session, and `disconnected` status.

## Common Development Patterns

### Service Method Testing
```php
// All services require SSH connection - test this dependency
public function test_method_requires_ssh_connection()
{
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('SSH connection required');
    
    $service->method();
}
```

### Controller Update Pattern
```php
// Handle credential preservation for updates
if ($request->isMethod('put') && empty($request->input('password'))) {
    unset($data['password']); // Preserve existing encrypted credential
}
```

### Route Model Binding Workaround
```php
// Manual lookup used instead of route model binding due to test issues
$server = Server::findOrFail($server);
```

## Configuration Management

### Package Installation Sequence
1. `composer require omniglies/laravel-server-manager`
2. `php artisan vendor:publish --tag=config --provider="ServerManager\LaravelServerManager\ServerManagerServiceProvider"`
3. `php artisan vendor:publish --tag=migrations --provider="ServerManager\LaravelServerManager\ServerManagerServiceProvider"`
4. `php artisan migrate`

### Service Registration
All core services are registered as singletons in the service provider. The package auto-discovers via Laravel's package discovery.

## Security Considerations

### Credential Handling
- Passwords/keys encrypted before storage using Laravel Crypt
- Form validation prevents credential corruption during updates
- SSH connections isolated per session

### Command Filtering
The package includes command filtering capabilities (configured in `config/server-manager.php`) for restricting dangerous operations.

## Known Issues & Patterns

### Empty Credential Form Handling
JavaScript forms send empty strings for credentials rather than omitting fields entirely. The controller must detect and unset empty credential fields to preserve existing encrypted values during updates.

### Auto-Reconnect Logic
Status endpoint auto-reconnects on connection loss, but must be prevented after manual disconnect. Detection logic: explicit `server_id` + missing session + `disconnected` status.

### Test Database Transactions
Some tests may encounter "already active transaction" errors. This is related to PHPUnit's database handling and RefreshDatabase trait usage.