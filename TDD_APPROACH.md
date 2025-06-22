# Test-Driven Development (TDD) Approach

## Overview

This project follows Test-Driven Development (TDD) principles for all new features and bug fixes. TDD ensures code quality, maintainability, and reduces bugs by writing tests before implementation.

## TDD Cycle: Red-Green-Refactor

### 🔴 Red: Write a Failing Test
1. **Write the test first** - Before writing any production code
2. **Make it fail** - Ensure the test fails for the right reason
3. **Test the behavior** - Focus on what the code should do, not how

### 🟢 Green: Make the Test Pass
1. **Write minimal code** - Just enough to make the test pass
2. **Don't optimize yet** - Focus on making it work first
3. **Get to green quickly** - Avoid over-engineering at this stage

### 🔵 Refactor: Improve the Code
1. **Clean up the code** - Improve structure without changing behavior
2. **Eliminate duplication** - Follow DRY principles
3. **Maintain green tests** - All tests must continue passing

## TDD Implementation Guidelines

### 1. Test Structure
```php
// Arrange: Set up test data and conditions
$server = Server::create([
    'name' => 'Test Server',
    'host' => 'test.example.com',
    'username' => 'testuser',
    'password' => 'testpass'
]);

// Act: Execute the code under test
$response = $this->postJson(route('server-manager.servers.connect'), [
    'server_id' => $server->id
]);

// Assert: Verify the expected outcome
$response->assertStatus(200);
$response->assertJson(['success' => true]);
```

### 2. Test Categories

#### Unit Tests
- Test individual methods and classes in isolation
- Use mocks for dependencies
- Fast execution (< 1ms per test)
- Location: `tests/Unit/`

```php
public function test_server_can_generate_ssh_config()
{
    $server = new Server([
        'host' => 'example.com',
        'port' => 22,
        'username' => 'user',
        'password' => 'pass'
    ]);
    
    $config = $server->getSshConfig();
    
    $this->assertEquals('example.com', $config['host']);
    $this->assertEquals(22, $config['port']);
}
```

#### Feature Tests
- Test complete features end-to-end
- Include HTTP requests and database interactions
- Test real user scenarios
- Location: `tests/Feature/`

```php
public function test_user_can_create_server()
{
    $serverData = [
        'name' => 'Production Server',
        'host' => '192.168.1.100',
        'username' => 'ubuntu',
        'auth_type' => 'password',
        'password' => 'secret'
    ];

    $response = $this->postJson(route('server-manager.servers.store'), $serverData);

    $response->assertStatus(200);
    $this->assertDatabaseHas('servers', ['name' => 'Production Server']);
}
```

### 3. Mock Usage Guidelines

#### When to Mock
- External services (SSH connections, APIs)
- Slow operations (file system, network)
- Dependencies with side effects

#### How to Mock
```php
protected function setUp(): void
{
    parent::setUp();
    
    $this->mockSshService = Mockery::mock(SshService::class);
    $this->app->instance(SshService::class, $this->mockSshService);
}

public function test_connection_success()
{
    $this->mockSshService
        ->shouldReceive('connect')
        ->once()
        ->with(Mockery::subset(['host' => 'test.com']))
        ->andReturn(true);
        
    // Test implementation...
}
```

### 4. Database Testing

#### Use RefreshDatabase Trait
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServerControllerTest extends TestCase
{
    use RefreshDatabase;
    
    // Tests automatically get fresh database
}
```

#### Factory Usage
```php
// Create test data using factories when available
$server = Server::factory()->create(['name' => 'Test Server']);

// Or use direct creation for simple cases
$server = Server::create([
    'name' => 'Test Server',
    'host' => 'test.example.com'
]);
```

### 5. Error Testing

#### Test Error Conditions
```php
public function test_connection_fails_with_invalid_credentials()
{
    $server = Server::create(['name' => 'Test', /*...*/]);
    
    $this->mockSshService
        ->shouldReceive('connect')
        ->once()
        ->andReturn(false);

    $response = $this->postJson(route('server-manager.servers.connect'), [
        'server_id' => $server->id
    ]);

    $response->assertStatus(400);
    $response->assertJson(['success' => false]);
}
```

#### Test Validation
```php
public function test_server_creation_requires_name()
{
    $response = $this->postJson(route('server-manager.servers.store'), [
        // Missing required 'name' field
        'host' => 'test.com'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
}
```

## TDD Best Practices

### 1. Test Naming Convention
- Use descriptive test names that explain the scenario
- Format: `test_[action]_[scenario]_[expected_result]`
- Examples:
  - `test_user_can_create_server_with_valid_data()`
  - `test_connection_fails_when_server_is_unreachable()`
  - `test_validation_fails_when_required_fields_missing()`

### 2. Test Organization
```php
class ServerControllerTest extends TestCase
{
    // Group related tests together
    
    // Happy path tests
    public function test_can_create_server() { }
    public function test_can_update_server() { }
    public function test_can_delete_server() { }
    
    // Error condition tests  
    public function test_creation_fails_with_invalid_data() { }
    public function test_update_fails_when_server_not_found() { }
    
    // Edge case tests
    public function test_handles_concurrent_connections() { }
}
```

### 3. Test Data Management
- Keep test data minimal and focused
- Use clear, descriptive test data
- Avoid shared test data between tests
- Clean up after each test (RefreshDatabase handles this)

### 4. Assertion Guidelines
- Use specific assertions
- Test one concept per test method
- Provide clear error messages

```php
// Good: Specific assertions
$this->assertEquals(200, $response->status());
$this->assertTrue($server->isConnected());
$this->assertDatabaseHas('servers', ['name' => 'Test Server']);

// Avoid: Vague assertions
$this->assertTrue($response->isSuccessful()); // Less specific
```

## Running Tests

### Command Line Usage
```bash
# Run all tests
composer test

# Run specific test suite
php vendor/bin/phpunit tests/Unit/
php vendor/bin/phpunit tests/Feature/

# Run specific test class
php vendor/bin/phpunit tests/Feature/ServerControllerTest.php

# Run specific test method
php vendor/bin/phpunit --filter="test_user_can_create_server"

# Run with coverage (when configured)
php vendor/bin/phpunit --coverage-html coverage/
```

### Continuous Integration
- All tests must pass before merging
- Maintain high test coverage (aim for 80%+)
- Run tests automatically on push/PR

## TDD Benefits in This Project

1. **Confidence in Refactoring**: Tests ensure changes don't break existing functionality
2. **Better Design**: Writing tests first leads to more testable, modular code
3. **Documentation**: Tests serve as living documentation of system behavior
4. **Reduced Debugging**: Issues are caught early in development
5. **Faster Development**: Less time spent debugging production issues

## Examples from Recent Fixes

### Problem: Route Conflicts
**Red**: Test failed because `/servers/status` was matching `/servers/{server}` route
**Green**: Reordered routes to put specific routes before parameterized ones
**Refactor**: Organized routes logically by specificity

### Problem: Missing Server Context in Tests
**Red**: Status tests failed because no server was connected in session
**Green**: Added proper server creation and session management in tests
**Refactor**: Created helper methods for common test setup

### Problem: View Rendering in Tests
**Red**: Tests failed when views tried to render with incomplete data
**Green**: Separated controller logic tests from view rendering concerns
**Refactor**: Focused tests on testable units rather than UI rendering

## Future Development

When adding new features or fixing bugs:

1. **Start with a failing test** that describes the desired behavior
2. **Write minimal code** to make the test pass
3. **Refactor** to improve code quality while keeping tests green
4. **Add edge case tests** to ensure robustness
5. **Update documentation** to reflect changes

Remember: **Red-Green-Refactor** is the core cycle that drives quality development.