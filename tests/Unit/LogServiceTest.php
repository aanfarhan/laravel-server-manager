<?php

namespace ServerManager\LaravelServerManager\Tests\Unit;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\LogService;
use ServerManager\LaravelServerManager\Services\SshService;
use Mockery;

class LogServiceTest extends TestCase
{
    protected LogService $logService;
    protected $mockSshService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSshService = Mockery::mock(SshService::class);
        $this->logService = new LogService($this->mockSshService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_read_log_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $result = $this->logService->readLog('/var/log/test.log');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH connection required', $result['message']);
    }

    public function test_read_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $logContent = "2024-01-01 12:00:01 INFO Application started\n" .
                     "2024-01-01 12:00:02 DEBUG User logged in\n" .
                     "2024-01-01 12:00:03 ERROR Database connection failed";

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("tail -100 '/var/log/test.log'")
            ->once()
            ->andReturn([
                'output' => $logContent,
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->readLog('/var/log/test.log', 100);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('lines', $result);
        $this->assertArrayHasKey('total_lines', $result);
        $this->assertArrayHasKey('file_path', $result);
        
        $this->assertEquals('/var/log/test.log', $result['file_path']);
        $this->assertEquals(3, $result['total_lines']);
        $this->assertContains('2024-01-01 12:00:01 INFO Application started', $result['lines']);
        $this->assertContains('2024-01-01 12:00:02 DEBUG User logged in', $result['lines']);
        $this->assertContains('2024-01-01 12:00:03 ERROR Database connection failed', $result['lines']);
    }

    public function test_read_log_with_custom_lines()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("tail -50 '/var/log/test.log'")
            ->once()
            ->andReturn([
                'output' => 'log content',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->readLog('/var/log/test.log', 50);

        $this->assertTrue($result['success']);
    }

    public function test_read_log_handles_command_failure()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("tail -100 '/var/log/nonexistent.log'")
            ->once()
            ->andReturn([
                'output' => 'tail: cannot open \'/var/log/nonexistent.log\' for reading: No such file or directory',
                'exit_status' => 1,
                'success' => false
            ]);

        $result = $this->logService->readLog('/var/log/nonexistent.log');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to read log file', $result['message']);
    }

    public function test_search_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $searchResults = "2024-01-01 12:00:03 ERROR Database connection failed\n" .
                        "2024-01-01 12:01:15 ERROR Authentication failed\n" .
                        "2024-01-01 12:02:30 ERROR File not found";

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("grep -i 'ERROR' '/var/log/test.log' | tail -100")
            ->once()
            ->andReturn([
                'output' => $searchResults,
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->searchLog('/var/log/test.log', 'ERROR', 100);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('lines', $result);
        $this->assertArrayHasKey('total_matches', $result);
        $this->assertArrayHasKey('pattern', $result);
        $this->assertArrayHasKey('file_path', $result);
        
        $this->assertEquals('ERROR', $result['pattern']);
        $this->assertEquals('/var/log/test.log', $result['file_path']);
        $this->assertEquals(3, $result['total_matches']);
        $this->assertContains('2024-01-01 12:00:03 ERROR Database connection failed', $result['lines']);
    }

    public function test_search_log_no_matches()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("grep -i 'NONEXISTENT' '/var/log/test.log' | tail -100")
            ->once()
            ->andReturn([
                'output' => '',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->searchLog('/var/log/test.log', 'NONEXISTENT');

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['total_matches']);
        $this->assertEmpty($result['lines']);
    }

    public function test_get_log_files_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $fileList = "/var/log/nginx/access.log\n" .
                   "/var/log/nginx/error.log\n" .
                   "/var/log/syslog\n" .
                   "/var/log/auth.log";

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("find '/var/log' -type f -name '*.log' -o -name '*.log.*' | head -50")
            ->once()
            ->andReturn([
                'output' => $fileList,
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock file info for each file
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("ls -lah '/var/log/nginx/access.log' | awk '{print $5 \" \" $6 \" \" $7 \" \" $8}'")
            ->once()
            ->andReturn([
                'output' => '2.1M Jan 1 12:00',
                'exit_status' => 0,
                'success' => true
            ]);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("ls -lah '/var/log/nginx/error.log' | awk '{print $5 \" \" $6 \" \" $7 \" \" $8}'")
            ->once()
            ->andReturn([
                'output' => '512K Jan 1 11:30',
                'exit_status' => 0,
                'success' => true
            ]);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("ls -lah '/var/log/syslog' | awk '{print $5 \" \" $6 \" \" $7 \" \" $8}'")
            ->once()
            ->andReturn([
                'output' => '1.5M Jan 1 12:15',
                'exit_status' => 0,
                'success' => true
            ]);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("ls -lah '/var/log/auth.log' | awk '{print $5 \" \" $6 \" \" $7 \" \" $8}'")
            ->once()
            ->andReturn([
                'output' => '256K Jan 1 10:45',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->getLogFiles('/var/log');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('files', $result);
        $this->assertCount(4, $result['files']);
        
        $this->assertEquals('/var/log/nginx/access.log', $result['files'][0]['path']);
        $this->assertEquals('2.1M', $result['files'][0]['size']);
        $this->assertEquals('Jan 1 12:00', $result['files'][0]['modified']);
    }

    public function test_tail_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $tailContent = "2024-01-01 12:05:01 INFO New request\n" .
                      "2024-01-01 12:05:02 DEBUG Processing\n" .
                      "2024-01-01 12:05:03 INFO Request completed";

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("tail -50 '/var/log/test.log'")
            ->once()
            ->andReturn([
                'output' => $tailContent,
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->tailLog('/var/log/test.log', 50);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('lines', $result);
        $this->assertArrayHasKey('file_path', $result);
        
        $this->assertEquals('/var/log/test.log', $result['file_path']);
        $this->assertEquals($tailContent, $result['content']);
        $this->assertCount(3, $result['lines']);
    }

    public function test_download_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('downloadFile')
            ->with('/var/log/test.log', '/local/path/test.log')
            ->once()
            ->andReturn(true);

        $result = $this->logService->downloadLog('/var/log/test.log', '/local/path/test.log');

        $this->assertTrue($result['success']);
        $this->assertEquals('/local/path/test.log', $result['local_path']);
        $this->assertEquals('/var/log/test.log', $result['remote_path']);
        $this->assertStringContainsString('downloaded successfully', $result['message']);
    }

    public function test_download_log_failure()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('downloadFile')
            ->with('/var/log/test.log', '/local/path/test.log')
            ->once()
            ->andReturn(false);

        $result = $this->logService->downloadLog('/var/log/test.log', '/local/path/test.log');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to download log file', $result['message']);
    }

    public function test_rotate_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with(Mockery::pattern("/mv '\/var\/log\/test\.log' '\/var\/log\/test\.log\.\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}' && touch '\/var\/log\/test\.log'/"))
            ->once()
            ->andReturn([
                'output' => '',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->rotateLog('/var/log/test.log');

        $this->assertTrue($result['success']);
        $this->assertEquals('/var/log/test.log', $result['original_path']);
        $this->assertStringContainsString('rotated successfully', $result['message']);
    }

    public function test_clear_log_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("truncate -s 0 '/var/log/test.log'")
            ->once()
            ->andReturn([
                'output' => '',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->clearLog('/var/log/test.log');

        $this->assertTrue($result['success']);
        $this->assertEquals('/var/log/test.log', $result['file_path']);
        $this->assertStringContainsString('cleared successfully', $result['message']);
    }

    public function test_get_recent_errors_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $logPaths = ['/var/log/test1.log', '/var/log/test2.log'];

        // Mock find command for each log and pattern
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("find '/var/log/test1.log' -mtime -24h -exec grep -l 'ERROR' {} \\; 2>/dev/null | head -10")
            ->once()
            ->andReturn([
                'output' => '/var/log/test1.log',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock grep for errors in found file
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("grep 'ERROR' '/var/log/test1.log' | tail -5")
            ->once()
            ->andReturn([
                'output' => "2024-01-01 12:00:01 ERROR Database error\n2024-01-01 12:01:01 ERROR Auth error",
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock other pattern searches (returning empty results)
        $this->mockSshService
            ->shouldReceive('execute')
            ->andReturn([
                'output' => '',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->logService->getRecentErrors($logPaths, 24);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('hours', $result);
        $this->assertEquals(24, $result['hours']);
        
        if (!empty($result['errors'])) {
            $this->assertEquals('/var/log/test1.log', $result['errors'][0]['file']);
            $this->assertEquals('ERROR', $result['errors'][0]['pattern']);
            $this->assertCount(2, $result['errors'][0]['lines']);
        }
    }

    public function test_all_methods_require_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->andReturn(false);

        $methods = [
            ['searchLog', ['/path', 'pattern']],
            ['getLogFiles', []],
            ['tailLog', ['/path']],
            ['downloadLog', ['/remote', '/local']],
            ['rotateLog', ['/path']],
            ['clearLog', ['/path']],
            ['getRecentErrors', [['/path']]]
        ];

        foreach ($methods as [$method, $args]) {
            $result = $this->logService->$method(...$args);
            $this->assertFalse($result['success'], "Method {$method} should require SSH connection");
            $this->assertStringContainsString('SSH connection required', $result['message']);
        }
    }
}