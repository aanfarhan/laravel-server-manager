<?php

namespace ServerManager\LaravelServerManager\Tests\Unit;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\TerminalService;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Models\Server;
use Mockery;

class TerminalServiceTest extends TestCase
{
    protected $mockSshService;
    protected $terminalService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSshService = Mockery::mock(SshService::class);
        $this->terminalService = new TerminalService($this->mockSshService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_session_requires_ssh_connection()
    {
        $server = Mockery::mock(Server::class);
        $server->shouldReceive('getSshConfig')->andReturn(['host' => 'test.example.com']);
        $server->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $server->shouldReceive('getAttribute')->with('name')->andReturn('Test Server');

        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->andReturn(false);

        $result = $this->terminalService->createSession($server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to establish SSH connection', $result['message']);
    }

    public function test_create_session_success()
    {
        $server = Mockery::mock(Server::class);
        $server->shouldReceive('getSshConfig')->andReturn(['host' => 'test.example.com']);
        $server->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $server->shouldReceive('getAttribute')->with('name')->andReturn('Test Server');

        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('createShell')
            ->once()
            ->andReturn('shell_resource_123');

        $result = $this->terminalService->createSession($server);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('session_id', $result);
        $this->assertEquals('Test Server', $result['server_name']);
    }

    public function test_create_session_shell_failure()
    {
        $server = Mockery::mock(Server::class);
        $server->shouldReceive('getSshConfig')->andReturn(['host' => 'test.example.com']);
        $server->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $server->shouldReceive('getAttribute')->with('name')->andReturn('Test Server');

        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('createShell')
            ->once()
            ->andReturn(false);

        $result = $this->terminalService->createSession($server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to create shell session', $result['message']);
    }

    public function test_execute_command_invalid_session()
    {
        $result = $this->terminalService->executeCommand('invalid_session', 'ls');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Terminal session not found', $result['message']);
    }

    public function test_send_input_invalid_session()
    {
        $result = $this->terminalService->sendInput('invalid_session', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Terminal session not found', $result['message']);
    }

    public function test_get_output_invalid_session()
    {
        $result = $this->terminalService->getOutput('invalid_session');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Terminal session not found', $result['message']);
        $this->assertFalse($result['session_active']);
    }

    public function test_close_session_nonexistent()
    {
        $result = $this->terminalService->closeSession('nonexistent_session');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('already closed', $result['message']);
    }

    public function test_get_session_info_invalid()
    {
        $result = $this->terminalService->getSessionInfo('invalid_session');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Session not found', $result['message']);
    }

    public function test_get_active_sessions_empty()
    {
        $result = $this->terminalService->getActiveSessions();

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['count']);
        $this->assertIsArray($result['sessions']);
        $this->assertEmpty($result['sessions']);
    }

    public function test_cleanup_expired_sessions_none()
    {
        $expiredCount = $this->terminalService->cleanupExpiredSessions();

        $this->assertEquals(0, $expiredCount);
    }

    public function test_resize_terminal_invalid_session()
    {
        $result = $this->terminalService->resizeTerminal('invalid_session', 24, 80);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Terminal session not found', $result['message']);
    }

    public function test_generate_session_id_is_unique()
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->terminalService);
        $method = $reflection->getMethod('generateSessionId');
        $method->setAccessible(true);

        $id1 = $method->invoke($this->terminalService);
        $id2 = $method->invoke($this->terminalService);

        $this->assertNotEquals($id1, $id2);
        $this->assertStringContainsString('term_', $id1);
        $this->assertStringContainsString('term_', $id2);
    }

    public function test_service_constructor_dependency()
    {
        $this->assertInstanceOf(TerminalService::class, $this->terminalService);
        
        // Use reflection to check if SshService is properly injected
        $reflection = new \ReflectionClass($this->terminalService);
        $property = $reflection->getProperty('sshService');
        $property->setAccessible(true);
        
        $this->assertInstanceOf(SshService::class, $property->getValue($this->terminalService));
    }
}