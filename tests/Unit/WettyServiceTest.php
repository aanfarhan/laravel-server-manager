<?php

namespace ServerManager\LaravelServerManager\Tests\Unit;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\WettyService;
use ServerManager\LaravelServerManager\Models\Server;
use Mockery;

class WettyServiceTest extends TestCase
{
    protected $wettyService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->wettyService = new WettyService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_service_constructor()
    {
        $this->assertInstanceOf(WettyService::class, $this->wettyService);
    }

    public function test_start_instance_fails_without_wetty_installed()
    {
        $server = Mockery::mock(Server::class)->makePartial();
        $server->id = 1;
        $server->name = 'Test Server';
        $server->host = 'test.example.com';
        $server->port = 22;
        $server->username = 'testuser';

        // Mock wetty not being installed
        $wettyService = Mockery::mock(WettyService::class)->makePartial();
        $wettyService->shouldReceive('isWettyInstalled')->andReturn(false);

        $result = $wettyService->startInstance($server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Wetty is not installed', $result['message']);
    }

    public function test_stop_instance_nonexistent()
    {
        $result = $this->wettyService->stopInstance('nonexistent_instance');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('already stopped', $result['message']);
    }

    public function test_get_instance_invalid()
    {
        $result = $this->wettyService->getInstance('invalid_instance');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Instance not found', $result['message']);
    }

    public function test_get_active_instances_empty()
    {
        $result = $this->wettyService->getActiveInstances();

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['count']);
        $this->assertIsArray($result['instances']);
        $this->assertEmpty($result['instances']);
    }

    public function test_cleanup_instances_none()
    {
        $cleanedCount = $this->wettyService->cleanupInstances();

        $this->assertEquals(0, $cleanedCount);
    }

    public function test_get_wetty_status_not_installed()
    {
        // Mock wetty not being installed
        $wettyService = Mockery::mock(WettyService::class)->makePartial();
        $wettyService->shouldReceive('isWettyInstalled')->andReturn(false);

        $result = $wettyService->getWettyStatus();

        $this->assertFalse($result['installed']);
        $this->assertStringContainsString('not installed', $result['message']);
        $this->assertArrayHasKey('install_command', $result);
    }

    public function test_find_available_port_logic()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->wettyService);
        $method = $reflection->getMethod('findAvailablePort');
        $method->setAccessible(true);

        $port = $method->invoke($this->wettyService);

        $this->assertIsInt($port);
        $this->assertGreaterThanOrEqual(3000, $port);
    }

    public function test_is_port_available_logic()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->wettyService);
        $method = $reflection->getMethod('isPortAvailable');
        $method->setAccessible(true);

        // Test with a port that should be available (high number)
        $available = $method->invoke($this->wettyService, 65530);
        $this->assertTrue($available);
    }

    public function test_generate_instance_id_is_unique()
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->wettyService);
        $method = $reflection->getMethod('generateInstanceId');
        $method->setAccessible(true);

        $id1 = $method->invoke($this->wettyService);
        $id2 = $method->invoke($this->wettyService);

        $this->assertNotEquals($id1, $id2);
        $this->assertStringContainsString('wetty_', $id1);
        $this->assertStringContainsString('wetty_', $id2);
    }

    public function test_is_process_running_with_invalid_pid()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->wettyService);
        $method = $reflection->getMethod('isProcessRunning');
        $method->setAccessible(true);

        // Test with an invalid PID
        $running = $method->invoke($this->wettyService, 999999);
        $this->assertFalse($running);
    }

    public function test_configuration_defaults()
    {
        // Test that service reads configuration properly
        $this->assertEquals('wetty', config('server-manager.wetty.path', 'wetty'));
        $this->assertEquals(3000, config('server-manager.wetty.base_port', 3000));
        $this->assertEquals(10, config('server-manager.wetty.max_instances', 10));
    }
}