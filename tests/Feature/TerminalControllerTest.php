<?php

namespace ServerManager\LaravelServerManager\Tests\Feature;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\TerminalService;
use ServerManager\LaravelServerManager\Models\Server;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TerminalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $mockTerminalService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockTerminalService = Mockery::mock(TerminalService::class);
        $this->app->instance(TerminalService::class, $this->mockTerminalService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_terminal_session_success()
    {
        $server = Server::create([
            'name' => 'Test Server',
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        $this->mockTerminalService
            ->shouldReceive('createSession')
            ->once()
            ->with(Mockery::type(Server::class))
            ->andReturn([
                'success' => true,
                'session_id' => 'test_session_123',
                'server_name' => 'Test Server',
                'message' => 'Terminal session connected to Test Server'
            ]);

        $response = $this->postJson(route('server-manager.terminal.create'), [
            'server_id' => $server->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'session_id' => 'test_session_123'
        ]);
    }

    public function test_create_terminal_session_validation()
    {
        $response = $this->postJson(route('server-manager.terminal.create'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['server_id']);
    }

    public function test_execute_command_success()
    {
        $this->mockTerminalService
            ->shouldReceive('executeCommand')
            ->once()
            ->with('test_session_123', 'ls -la')
            ->andReturn([
                'success' => true,
                'output' => 'total 8\ndrwxr-xr-x 2 user user 4096 Dec 25 10:00 .\n',
                'command' => 'ls -la'
            ]);

        $response = $this->postJson(route('server-manager.terminal.execute'), [
            'session_id' => 'test_session_123',
            'command' => 'ls -la'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'command' => 'ls -la'
        ]);
        $response->assertJsonStructure(['output']);
    }

    public function test_execute_command_validation()
    {
        $response = $this->postJson(route('server-manager.terminal.execute'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_id', 'command']);
    }

    public function test_send_input_success()
    {
        $this->mockTerminalService
            ->shouldReceive('sendInput')
            ->once()
            ->with('test_session_123', 'y\n')
            ->andReturn([
                'success' => true,
                'output' => 'y\n'
            ]);

        $response = $this->postJson(route('server-manager.terminal.input'), [
            'session_id' => 'test_session_123',
            'input' => 'y\n'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_get_output_success()
    {
        $this->mockTerminalService
            ->shouldReceive('getOutput')
            ->once()
            ->with('test_session_123')
            ->andReturn([
                'success' => true,
                'output' => 'user@server:~$ ',
                'session_active' => true
            ]);

        $response = $this->getJson(route('server-manager.terminal.output') . '?session_id=test_session_123');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'session_active' => true
        ]);
    }

    public function test_resize_terminal_success()
    {
        $this->mockTerminalService
            ->shouldReceive('resizeTerminal')
            ->once()
            ->with('test_session_123', 30, 100)
            ->andReturn([
                'success' => true,
                'message' => 'Terminal resized successfully'
            ]);

        $response = $this->postJson(route('server-manager.terminal.resize'), [
            'session_id' => 'test_session_123',
            'rows' => 30,
            'cols' => 100
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_resize_terminal_validation()
    {
        $response = $this->postJson(route('server-manager.terminal.resize'), [
            'session_id' => 'test_session_123',
            'rows' => 0, // Invalid
            'cols' => 1000 // Invalid
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rows', 'cols']);
    }

    public function test_close_terminal_session_success()
    {
        $this->mockTerminalService
            ->shouldReceive('closeSession')
            ->once()
            ->with('test_session_123')
            ->andReturn([
                'success' => true,
                'message' => 'Terminal session closed successfully'
            ]);

        $response = $this->postJson(route('server-manager.terminal.close'), [
            'session_id' => 'test_session_123'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_get_session_info_success()
    {
        $this->mockTerminalService
            ->shouldReceive('getSessionInfo')
            ->once()
            ->with('test_session_123')
            ->andReturn([
                'success' => true,
                'session' => [
                    'id' => 'test_session_123',
                    'server_id' => 1,
                    'server_name' => 'Test Server',
                    'created_at' => '2024-12-25T10:00:00.000000Z',
                    'last_activity' => '2024-12-25T10:05:00.000000Z',
                    'is_active' => true
                ]
            ]);

        $response = $this->getJson(route('server-manager.terminal.info') . '?session_id=test_session_123');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonFragment(['server_name' => 'Test Server']);
    }

    public function test_list_active_sessions_success()
    {
        $this->mockTerminalService
            ->shouldReceive('getActiveSessions')
            ->once()
            ->andReturn([
                'success' => true,
                'sessions' => [
                    [
                        'id' => 'test_session_123',
                        'server_id' => 1,
                        'server_name' => 'Test Server',
                        'is_active' => true
                    ]
                ],
                'count' => 1
            ]);

        $response = $this->getJson(route('server-manager.terminal.sessions'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1
        ]);
    }

    public function test_cleanup_expired_sessions()
    {
        $this->mockTerminalService
            ->shouldReceive('cleanupExpiredSessions')
            ->once()
            ->andReturn(2);

        $response = $this->postJson(route('server-manager.terminal.cleanup'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'expired_count' => 2
        ]);
    }

    public function test_bulk_operations_success()
    {
        $this->mockTerminalService
            ->shouldReceive('executeCommand')
            ->once()
            ->with('session1', 'pwd')
            ->andReturn(['success' => true, 'output' => '/home/user']);

        $this->mockTerminalService
            ->shouldReceive('closeSession')
            ->once()
            ->with('session2')
            ->andReturn(['success' => true, 'message' => 'Session closed']);

        $response = $this->postJson(route('server-manager.terminal.bulk'), [
            'operations' => [
                [
                    'type' => 'execute',
                    'session_id' => 'session1',
                    'data' => 'pwd'
                ],
                [
                    'type' => 'close',
                    'session_id' => 'session2'
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonCount(2, 'results');
    }

    public function test_controller_handles_exceptions()
    {
        $server = Server::create([
            'name' => 'Test Server',
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        $this->mockTerminalService
            ->shouldReceive('createSession')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $response = $this->postJson(route('server-manager.terminal.create'), [
            'server_id' => $server->id
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to create terminal session: Connection failed'
        ]);
    }
}