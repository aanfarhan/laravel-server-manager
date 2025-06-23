<?php

namespace ServerManager\LaravelServerManager\Tests\Feature;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\LogService;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $mockSshService;
    protected $mockLogService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSshService = Mockery::mock(SshService::class);
        $this->mockLogService = Mockery::mock(LogService::class);
        
        $this->app->instance(SshService::class, $this->mockSshService);
        $this->app->instance(LogService::class, $this->mockLogService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_logs_view()
    {
        $response = $this->get(route('server-manager.logs.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('server-manager::logs.index');
    }

    public function test_files_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('getLogFiles')
            ->once()
            ->with('/var/log')
            ->andReturn([
                'success' => true,
                'files' => [
                    [
                        'path' => '/var/log/nginx/access.log',
                        'size' => '2.1M',
                        'modified' => 'Jan 1 12:00'
                    ],
                    [
                        'path' => '/var/log/nginx/error.log',
                        'size' => '512K',
                        'modified' => 'Jan 1 11:30'
                    ]
                ]
            ]);

        $response = $this->getJson(route('server-manager.logs.files'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'files' => [
                [
                    'path' => '/var/log/nginx/access.log',
                    'size' => '2.1M',
                    'modified' => 'Jan 1 12:00'
                ],
                [
                    'path' => '/var/log/nginx/error.log',
                    'size' => '512K',
                    'modified' => 'Jan 1 11:30'
                ]
            ]
        ]);
    }

    public function test_files_with_custom_directory()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('getLogFiles')
            ->once()
            ->with('/custom/log/path')
            ->andReturn([
                'success' => true,
                'files' => []
            ]);

        $response = $this->getJson(route('server-manager.logs.files', [
            'directory' => '/custom/log/path'
        ]));

        $response->assertStatus(200);
    }

    public function test_files_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $response = $this->getJson(route('server-manager.logs.files'));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'SSH connection required'
        ]);
    }

    public function test_read_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('readLog')
            ->once()
            ->with('/var/log/test.log', 100)
            ->andReturn([
                'success' => true,
                'lines' => [
                    '2024-01-01 12:00:01 INFO Application started',
                    '2024-01-01 12:00:02 DEBUG User logged in',
                    '2024-01-01 12:00:03 ERROR Database connection failed'
                ],
                'total_lines' => 3,
                'file_path' => '/var/log/test.log'
            ]);

        $response = $this->getJson(route('server-manager.logs.read', [
            'path' => '/var/log/test.log',
            'lines' => 100
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'lines' => [
                '2024-01-01 12:00:01 INFO Application started',
                '2024-01-01 12:00:02 DEBUG User logged in',
                '2024-01-01 12:00:03 ERROR Database connection failed'
            ],
            'total_lines' => 3,
            'file_path' => '/var/log/test.log'
        ]);
    }

    public function test_read_log_validation_rules()
    {
        $response = $this->getJson(route('server-manager.logs.read'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['path']);
    }

    public function test_read_log_validates_lines_range()
    {
        $response = $this->getJson(route('server-manager.logs.read', [
            'path' => '/var/log/test.log',
            'lines' => 2000 // Should be max 1000
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lines']);
    }

    public function test_search_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('searchLog')
            ->once()
            ->with('/var/log/test.log', 'ERROR', 100)
            ->andReturn([
                'success' => true,
                'lines' => [
                    '2024-01-01 12:00:03 ERROR Database connection failed',
                    '2024-01-01 12:01:15 ERROR Authentication failed'
                ],
                'total_matches' => 2,
                'pattern' => 'ERROR',
                'file_path' => '/var/log/test.log'
            ]);

        $response = $this->getJson(route('server-manager.logs.search', [
            'path' => '/var/log/test.log',
            'pattern' => 'ERROR',
            'lines' => 100
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'lines' => [
                '2024-01-01 12:00:03 ERROR Database connection failed',
                '2024-01-01 12:01:15 ERROR Authentication failed'
            ],
            'total_matches' => 2,
            'pattern' => 'ERROR',
            'file_path' => '/var/log/test.log'
        ]);
    }

    public function test_search_log_validation_rules()
    {
        $response = $this->getJson(route('server-manager.logs.search'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['path', 'pattern']);
    }

    public function test_tail_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('tailLog')
            ->once()
            ->with('/var/log/test.log', 50)
            ->andReturn([
                'success' => true,
                'content' => "2024-01-01 12:05:01 INFO New request\n2024-01-01 12:05:02 DEBUG Processing",
                'lines' => [
                    '2024-01-01 12:05:01 INFO New request',
                    '2024-01-01 12:05:02 DEBUG Processing'
                ],
                'file_path' => '/var/log/test.log'
            ]);

        $response = $this->getJson(route('server-manager.logs.tail', [
            'path' => '/var/log/test.log',
            'lines' => 50
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'content' => "2024-01-01 12:05:01 INFO New request\n2024-01-01 12:05:02 DEBUG Processing",
            'lines' => [
                '2024-01-01 12:05:01 INFO New request',
                '2024-01-01 12:05:02 DEBUG Processing'
            ],
            'file_path' => '/var/log/test.log'
        ]);
    }

    public function test_tail_log_validates_lines_range()
    {
        $response = $this->getJson(route('server-manager.logs.tail', [
            'path' => '/var/log/test.log',
            'lines' => 300 // Should be max 200
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lines']);
    }

    public function test_download_log_success()
    {
        Storage::fake('local');

        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $localPath = storage_path('app/temp/test.log_' . time());
        
        // Ensure temp directory exists
        if (!is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }
        
        $this->mockLogService
            ->shouldReceive('downloadLog')
            ->once()
            ->with('/var/log/test.log', Mockery::pattern('/test\.log_\d+$/'))
            ->andReturn([
                'success' => true,
                'local_path' => $localPath,
                'remote_path' => '/var/log/test.log',
                'message' => 'Log file downloaded successfully'
            ]);

        // Create a temporary file to simulate the download
        file_put_contents($localPath, 'test log content');

        $response = $this->getJson(route('server-manager.logs.download', [
            'path' => '/var/log/test.log'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        
        // Clean up
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    public function test_download_log_handles_failure()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('downloadLog')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Download failed'
            ]);

        $response = $this->getJson(route('server-manager.logs.download', [
            'path' => '/var/log/test.log'
        ]));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Download failed'
        ]);
    }

    public function test_clear_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('clearLog')
            ->once()
            ->with('/var/log/test.log')
            ->andReturn([
                'success' => true,
                'file_path' => '/var/log/test.log',
                'message' => 'Log file cleared successfully'
            ]);

        $response = $this->postJson(route('server-manager.logs.clear'), [
            'path' => '/var/log/test.log'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'file_path' => '/var/log/test.log',
            'message' => 'Log file cleared successfully'
        ]);
    }

    public function test_clear_log_validation_rules()
    {
        $response = $this->postJson(route('server-manager.logs.clear'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['path']);
    }

    public function test_rotate_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('rotateLog')
            ->once()
            ->with('/var/log/test.log')
            ->andReturn([
                'success' => true,
                'original_path' => '/var/log/test.log',
                'rotated_path' => '/var/log/test.log.2024-01-01_12-00-00',
                'message' => 'Log file rotated successfully'
            ]);

        $response = $this->postJson(route('server-manager.logs.rotate'), [
            'path' => '/var/log/test.log'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'original_path' => '/var/log/test.log',
            'rotated_path' => '/var/log/test.log.2024-01-01_12-00-00',
            'message' => 'Log file rotated successfully'
        ]);
    }

    public function test_errors_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('getRecentErrors')
            ->once()
            ->with(['/var/log/nginx/error.log', '/var/log/syslog'], 24)
            ->andReturn([
                'success' => true,
                'errors' => [
                    [
                        'file' => '/var/log/nginx/error.log',
                        'pattern' => 'ERROR',
                        'lines' => [
                            '2024-01-01 12:00:01 ERROR Connection failed',
                            '2024-01-01 12:01:01 ERROR Timeout occurred'
                        ]
                    ]
                ],
                'hours' => 24
            ]);

        $response = $this->getJson(route('server-manager.logs.errors'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'errors' => [
                [
                    'file' => '/var/log/nginx/error.log',
                    'pattern' => 'ERROR',
                    'lines' => [
                        '2024-01-01 12:00:01 ERROR Connection failed',
                        '2024-01-01 12:01:01 ERROR Timeout occurred'
                    ]
                ]
            ],
            'hours' => 24
        ]);
    }

    public function test_errors_with_custom_parameters()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockLogService
            ->shouldReceive('getRecentErrors')
            ->once()
            ->with(['/custom/log/path.log'], 48)
            ->andReturn([
                'success' => true,
                'errors' => [],
                'hours' => 48
            ]);

        $response = $this->getJson(route('server-manager.logs.errors', [
            'paths' => ['/custom/log/path.log'],
            'hours' => 48
        ]));

        $response->assertStatus(200);
    }

    public function test_all_methods_handle_exceptions()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->andReturn(true);

        $methods = [
            ['files', 'getLogFiles', [], 'getJson'],
            ['read', 'readLog', ['path' => '/test'], 'getJson'],
            ['search', 'searchLog', ['path' => '/test', 'pattern' => 'error'], 'getJson'],
            ['tail', 'tailLog', ['path' => '/test'], 'getJson'],
            ['clear', 'clearLog', ['path' => '/test'], 'postJson'],
            ['rotate', 'rotateLog', ['path' => '/test'], 'postJson'],
            ['errors', 'getRecentErrors', [], 'getJson']
        ];

        foreach ($methods as [$route, $method, $params, $httpMethod]) {
            $this->mockLogService
                ->shouldReceive($method)
                ->andThrow(new \Exception('Service failed'));

            $response = $this->$httpMethod(route("server-manager.logs.{$route}", $params));

            $response->assertStatus(500);
            $response->assertJson([
                'success' => false,
                'message' => 'Service failed'
            ]);
        }
    }

    public function test_files_auto_reconnects_when_session_server_available()
    {
        // Create a connected server
        $server = \ServerManager\LaravelServerManager\Models\Server::create([
            'name' => 'Test Server',
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass',
            'status' => 'connected'
        ]);

        // Set session to simulate connected state
        session(['connected_server_id' => $server->id]);

        // Mock SSH service as not connected initially
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        // Mock auto-reconnect attempt
        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->with(Mockery::on(function($config) {
                return $config['host'] === 'test.example.com' && 
                       $config['username'] === 'testuser';
            }))
            ->andReturn(true);

        // Mock successful log files retrieval after reconnect
        $this->mockLogService
            ->shouldReceive('getLogFiles')
            ->once()
            ->with('/var/log')
            ->andReturn([
                'success' => true,
                'files' => [
                    ['path' => '/var/log/test.log', 'size' => '1.2MB', 'modified' => '2024-01-01 12:00:00']
                ]
            ]);

        $response = $this->getJson(route('server-manager.logs.files'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'files' => [
                ['path' => '/var/log/test.log', 'size' => '1.2MB', 'modified' => '2024-01-01 12:00:00']
            ]
        ]);
    }

    public function test_files_connects_to_available_server_when_no_session()
    {
        // Create a connected server but NO session (this is the user's scenario)
        $server = \ServerManager\LaravelServerManager\Models\Server::create([
            'name' => 'Test Server',
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass',
            'status' => 'connected'
        ]);

        // No session set - this is the key difference from the previous test
        // session(['connected_server_id' => $server->id]); // Not set!

        // Mock SSH service as not connected initially
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        // Mock auto-connect attempt to available connected server
        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->with(Mockery::on(function($config) {
                return $config['host'] === 'test.example.com' && 
                       $config['username'] === 'testuser';
            }))
            ->andReturn(true);

        // Mock successful log files retrieval after connect
        $this->mockLogService
            ->shouldReceive('getLogFiles')
            ->once()
            ->with('/var/log')
            ->andReturn([
                'success' => true,
                'files' => [
                    ['path' => '/var/log/test.log', 'size' => '1.2MB', 'modified' => '2024-01-01 12:00:00']
                ]
            ]);

        $response = $this->getJson(route('server-manager.logs.files'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'files' => [
                ['path' => '/var/log/test.log', 'size' => '1.2MB', 'modified' => '2024-01-01 12:00:00']
            ]
        ]);
    }
}