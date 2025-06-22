# Testing Guide for Laravel Server Manager Package

This document provides comprehensive information about testing the Laravel Server Manager package.

## Test Structure

The test suite is organized into two main categories:

### Unit Tests (`tests/Unit/`)
- **SshServiceTest.php**: Tests for SSH connection functionality
- **DeploymentServiceTest.php**: Tests for git deployment operations
- **MonitoringServiceTest.php**: Tests for server monitoring capabilities
- **LogServiceTest.php**: Tests for log management features
- **ServiceProviderTest.php**: Tests for Laravel service provider registration

### Feature Tests (`tests/Feature/`)
- **ServerControllerTest.php**: Integration tests for server management endpoints
- **DeploymentControllerTest.php**: Integration tests for deployment endpoints
- **LogControllerTest.php**: Integration tests for log management endpoints
- **RoutesTest.php**: Tests for route registration and accessibility
- **ConfigurationTest.php**: Tests for package configuration structure and validation

## Running Tests

### Prerequisites

1. Install development dependencies:
```bash
composer install --dev
```

2. Ensure PHPUnit is available:
```bash
vendor/bin/phpunit --version
```

### Running All Tests
```bash
composer test
# or
vendor/bin/phpunit
```

### Running Specific Test Suites
```bash
# Unit tests only
composer test-unit

# Feature tests only
composer test-feature
```

### Running Individual Test Files
```bash
# Specific test class
vendor/bin/phpunit tests/Unit/SshServiceTest.php

# Specific test method
vendor/bin/phpunit --filter test_connect_with_password_success tests/Unit/SshServiceTest.php
```

### Test Coverage
```bash
# Generate HTML coverage report
composer test-coverage

# View coverage report
open coverage/index.html
```

## Test Configuration

### PHPUnit Configuration
The `phpunit.xml` file contains:
- Test suite definitions (Unit and Feature)
- Coverage configuration
- Environment variables for testing
- Database configuration (SQLite in-memory)

### Test Environment
Tests run with the following environment:
- **Database**: SQLite in-memory (`:memory:`)
- **Cache**: Array driver
- **Queue**: Sync driver
- **Session**: Array driver
- **Mail**: Array driver

## Mocking Strategy

### SSH Service Mocking
The SSH service is mocked in tests to avoid requiring actual SSH connections:

```php
$mockSshService = Mockery::mock(SshService::class);
$mockSshService->shouldReceive('connect')->andReturn(true);
$mockSshService->shouldReceive('execute')->andReturn([
    'output' => 'test output',
    'exit_status' => 0,
    'success' => true
]);
```

### Service Dependencies
All services that depend on SshService are tested with mocked SSH connections to ensure isolation and reliability.

## Test Categories

### 1. SSH Service Tests
- Connection establishment with password/key authentication
- Command execution and result handling
- File upload/download operations
- Connection state management
- Error handling and timeouts

### 2. Deployment Service Tests
- Git repository cloning and updating
- Pre-deploy, build, and post-deploy command execution
- Rollback functionality
- Deployment status tracking
- Error handling during deployment

### 3. Monitoring Service Tests
- System metrics collection (CPU, memory, disk)
- Process listing and filtering
- Service status checking
- Load average and uptime monitoring
- Status level calculation (ok/warning/critical)

### 4. Log Service Tests
- Log file discovery and listing
- Log content reading with line limits
- Pattern searching in log files
- Log file operations (clear, rotate, download)
- Recent error detection

### 5. Controller Tests
- HTTP request/response validation
- Input validation and error handling
- Authentication and authorization
- Session management
- Exception handling

### 6. Integration Tests
- Route registration and accessibility
- Service provider functionality
- Configuration structure validation
- View rendering
- Middleware integration

## Test Data

### Sample Test Data
Tests use realistic sample data:

```php
// Sample server status
$serverStatus = [
    'cpu' => ['usage_percent' => 25.5, 'status' => 'ok'],
    'memory' => ['usage_percent' => 65.3, 'status' => 'ok'],
    'disk' => ['usage_percent' => 45.0, 'status' => 'ok']
];

// Sample process data
$processes = [
    [
        'user' => 'root',
        'pid' => '1',
        'cpu' => 0.0,
        'memory' => 0.4,
        'command' => '/sbin/init'
    ]
];

// Sample log content
$logLines = [
    '2024-01-01 12:00:01 INFO Application started',
    '2024-01-01 12:00:02 DEBUG User logged in',
    '2024-01-01 12:00:03 ERROR Database connection failed'
];
```

## Assertion Patterns

### Common Assertions
```php
// Success/failure responses
$this->assertTrue($result['success']);
$this->assertFalse($result['success']);

// Array structure validation
$this->assertArrayHasKey('data', $result);
$this->assertIsArray($result['processes']);

// HTTP responses
$response->assertStatus(200);
$response->assertJson(['success' => true]);
$response->assertJsonValidationErrors(['field']);

// Service instantiation
$this->assertInstanceOf(SshService::class, $service);
```

### Error Testing
All services and controllers include comprehensive error testing:
- Invalid input validation
- Network/connection failures
- Command execution errors
- File system errors
- Authentication failures

## Continuous Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.1, 8.2, 8.3]
        laravel-version: [10.*, 11.*]
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, xml, sqlite3
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run tests
      run: composer test
    
    - name: Upload coverage
      run: composer test-coverage
```

## Best Practices

### 1. Test Isolation
- Each test method is independent
- Use `setUp()` and `tearDown()` methods for test preparation
- Mock external dependencies

### 2. Descriptive Test Names
```php
public function test_connect_with_valid_credentials_success()
public function test_deploy_handles_command_failure()
public function test_read_log_validation_rules()
```

### 3. Comprehensive Coverage
- Test both success and failure scenarios
- Validate input parameters
- Check error messages and status codes
- Test edge cases and boundary conditions

### 4. Mock Strategy
- Mock external services (SSH connections)
- Use dependency injection for testability
- Verify mock interactions with `shouldReceive()`

### 5. Data-Driven Tests
```php
/**
 * @dataProvider validationProvider
 */
public function test_validation($input, $expectedErrors)
{
    $response = $this->postJson($route, $input);
    $response->assertJsonValidationErrors($expectedErrors);
}
```

## Troubleshooting

### Common Issues

1. **Memory Limit**: Increase PHP memory limit for coverage reports
2. **Time Limits**: Some tests may need longer execution time
3. **File Permissions**: Ensure test storage directories are writable
4. **Mock Conflicts**: Clear Mockery after each test

### Debug Tips
```php
// Debug test output
dump($response->getContent());

// Check mock expectations
$this->assertTrue(Mockery::getContainer()->mockery_verify());

// Inspect database state
$this->assertDatabaseHas('table', ['field' => 'value']);
```

## Contributing Tests

When adding new features:

1. Write tests first (TDD approach)
2. Ensure both unit and integration tests
3. Maintain high code coverage (aim for >90%)
4. Test error conditions and edge cases
5. Update this documentation for new test patterns

## Performance Testing

For performance-critical operations:

```php
public function test_monitoring_performance()
{
    $start = microtime(true);
    
    $result = $this->monitoringService->getServerStatus();
    
    $duration = microtime(true) - $start;
    $this->assertLessThan(5.0, $duration, 'Monitoring should complete within 5 seconds');
}
```