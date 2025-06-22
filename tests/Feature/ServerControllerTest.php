<?php

namespace ServerManager\LaravelServerManager\Tests\Feature;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\MonitoringService;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $mockSshService;
    protected $mockMonitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSshService = Mockery::mock(SshService::class);
        $this->mockMonitoringService = Mockery::mock(MonitoringService::class);
        
        $this->app->instance(SshService::class, $this->mockSshService);
        $this->app->instance(MonitoringService::class, $this->mockMonitoringService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_servers_view()
    {
        $response = $this->get(route('server-manager.servers.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('server-manager::servers.index');
    }

    public function test_connect_with_valid_credentials_success()
    {
        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->with(Mockery::subset([
                'host' => 'test.example.com',
                'username' => 'testuser',
                'port' => 22
            ]))
            ->andReturn(true);

        $response = $this->postJson(route('server-manager.servers.connect'), [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'port' => 22,
            'password' => 'testpass'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Connected successfully'
        ]);
    }

    public function test_connect_with_invalid_credentials_fails()
    {
        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(route('server-manager.servers.connect'), [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'port' => 22,
            'password' => 'wrongpass'
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to connect'
        ]);
    }

    public function test_connect_with_private_key()
    {
        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->with(Mockery::subset([
                'host' => 'test.example.com',
                'username' => 'testuser',
                'private_key' => 'test-key-content'
            ]))
            ->andReturn(true);

        $response = $this->postJson(route('server-manager.servers.connect'), [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'port' => 22,
            'private_key' => 'test-key-content'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Connected successfully'
        ]);
    }

    public function test_connect_validation_rules()
    {
        $response = $this->postJson(route('server-manager.servers.connect'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['host', 'username']);
    }

    public function test_connect_handles_exceptions()
    {
        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        $response = $this->postJson(route('server-manager.servers.connect'), [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Connection timeout'
        ]);
    }

    public function test_test_connection_success()
    {
        $this->mockSshService
            ->shouldReceive('testConnection')
            ->once()
            ->andReturn(true);

        $response = $this->postJson(route('server-manager.servers.test'), [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Connection test successful'
        ]);
    }

    public function test_test_connection_failure()
    {
        $this->mockSshService
            ->shouldReceive('testConnection')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(route('server-manager.servers.test'), [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Connection test failed'
        ]);
    }

    public function test_disconnect_success()
    {
        $this->mockSshService
            ->shouldReceive('disconnect')
            ->once();

        $response = $this->postJson(route('server-manager.servers.disconnect'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Disconnected successfully'
        ]);
    }

    public function test_status_with_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockMonitoringService
            ->shouldReceive('getServerStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'cpu' => ['usage_percent' => 25.5],
                    'memory' => ['usage_percent' => 65.3],
                    'disk' => ['usage_percent' => 45.0]
                ]
            ]);

        $response = $this->getJson(route('server-manager.servers.status'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'cpu' => ['usage_percent' => 25.5],
                'memory' => ['usage_percent' => 65.3],
                'disk' => ['usage_percent' => 45.0]
            ]
        ]);
    }

    public function test_status_auto_reconnect()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->mockMonitoringService
            ->shouldReceive('getServerStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => []
            ]);

        // Set up session with SSH config
        session(['ssh_config' => [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]]);

        $response = $this->getJson(route('server-manager.servers.status'));

        $response->assertStatus(200);
    }

    public function test_processes_success()
    {
        $this->mockMonitoringService
            ->shouldReceive('getProcesses')
            ->once()
            ->with(10)
            ->andReturn([
                'success' => true,
                'processes' => [
                    [
                        'user' => 'root',
                        'pid' => '1',
                        'cpu' => 0.0,
                        'memory' => 0.4,
                        'command' => '/sbin/init'
                    ]
                ]
            ]);

        $response = $this->getJson(route('server-manager.servers.processes'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'processes' => [
                [
                    'user' => 'root',
                    'pid' => '1',
                    'cpu' => 0.0,
                    'memory' => 0.4,
                    'command' => '/sbin/init'
                ]
            ]
        ]);
    }

    public function test_processes_with_custom_limit()
    {
        $this->mockMonitoringService
            ->shouldReceive('getProcesses')
            ->once()
            ->with(20)
            ->andReturn([
                'success' => true,
                'processes' => []
            ]);

        $response = $this->getJson(route('server-manager.servers.processes', ['limit' => 20]));

        $response->assertStatus(200);
    }

    public function test_services_success()
    {
        $this->mockMonitoringService
            ->shouldReceive('getServiceStatus')
            ->once()
            ->with(['nginx', 'mysql', 'redis'])
            ->andReturn([
                'success' => true,
                'services' => [
                    'nginx' => [
                        'name' => 'nginx',
                        'status' => 'active',
                        'running' => true
                    ]
                ]
            ]);

        $response = $this->getJson(route('server-manager.servers.services'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'services' => [
                'nginx' => [
                    'name' => 'nginx',
                    'status' => 'active',
                    'running' => true
                ]
            ]
        ]);
    }

    public function test_services_with_custom_services()
    {
        $this->mockMonitoringService
            ->shouldReceive('getServiceStatus')
            ->once()
            ->with(['apache2', 'postgresql'])
            ->andReturn([
                'success' => true,
                'services' => []
            ]);

        $response = $this->getJson(route('server-manager.servers.services', [
            'services' => ['apache2', 'postgresql']
        ]));

        $response->assertStatus(200);
    }

    public function test_status_handles_exceptions()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockMonitoringService
            ->shouldReceive('getServerStatus')
            ->once()
            ->andThrow(new \Exception('Monitoring failed'));

        $response = $this->getJson(route('server-manager.servers.status'));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Monitoring failed'
        ]);
    }

    public function test_processes_handles_exceptions()
    {
        $this->mockMonitoringService
            ->shouldReceive('getProcesses')
            ->once()
            ->andThrow(new \Exception('Process listing failed'));

        $response = $this->getJson(route('server-manager.servers.processes'));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Process listing failed'
        ]);
    }

    public function test_services_handles_exceptions()
    {
        $this->mockMonitoringService
            ->shouldReceive('getServiceStatus')
            ->once()
            ->andThrow(new \Exception('Service status failed'));

        $response = $this->getJson(route('server-manager.servers.services'));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Service status failed'
        ]);
    }
}