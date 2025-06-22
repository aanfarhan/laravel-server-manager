<?php

namespace ServerManager\LaravelServerManager\Tests\Unit;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\MonitoringService;
use ServerManager\LaravelServerManager\Services\SshService;
use Mockery;

class MonitoringServiceTest extends TestCase
{
    protected MonitoringService $monitoringService;
    protected $mockSshService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSshService = Mockery::mock(SshService::class);
        $this->monitoringService = new MonitoringService($this->mockSshService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_server_status_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $result = $this->monitoringService->getServerStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH connection required', $result['message']);
    }

    public function test_get_server_status_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock CPU usage
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1")
            ->once()
            ->andReturn([
                'output' => '25.5',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock memory usage
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("free -m | awk 'NR==2{printf \"%.2f %.2f %.2f\", $3*100/$2, $3, $2}'")
            ->once()
            ->andReturn([
                'output' => '65.30 2613.00 4000.00',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock disk usage
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("df -h / | awk 'NR==2 {print $5 \" \" $3 \" \" $2 \" \" $4}'")
            ->once()
            ->andReturn([
                'output' => '45% 18G 40G 20G',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock load average
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("uptime | awk -F'load average:' '{print $2}' | sed 's/,//g'")
            ->once()
            ->andReturn([
                'output' => ' 0.25 0.30 0.35',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock uptime
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('uptime -p')
            ->once()
            ->andReturn([
                'output' => 'up 2 days, 5 hours, 30 minutes',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock process count
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('ps aux | wc -l')
            ->once()
            ->andReturn([
                'output' => '156',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock network info
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("cat /proc/net/dev | grep -E '(eth|wlan|enp)' | head -1 | awk '{print $1 \" \" $2 \" \" $10}'")
            ->once()
            ->andReturn([
                'output' => 'eth0: 1234567890 987654321',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->monitoringService->getServerStatus();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('cpu', $result['data']);
        $this->assertArrayHasKey('memory', $result['data']);
        $this->assertArrayHasKey('disk', $result['data']);
        $this->assertArrayHasKey('load', $result['data']);
        $this->assertArrayHasKey('uptime', $result['data']);
        $this->assertArrayHasKey('processes', $result['data']);
        $this->assertArrayHasKey('network', $result['data']);

        // Check CPU data
        $this->assertEquals(25.5, $result['data']['cpu']['usage_percent']);
        $this->assertEquals('ok', $result['data']['cpu']['status']);

        // Check memory data
        $this->assertEquals(65.30, $result['data']['memory']['usage_percent']);
        $this->assertEquals(2613.00, $result['data']['memory']['used_mb']);
        $this->assertEquals(4000.00, $result['data']['memory']['total_mb']);
        $this->assertEquals('ok', $result['data']['memory']['status']);

        // Check disk data
        $this->assertEquals(45, $result['data']['disk']['usage_percent']);
        $this->assertEquals('18G', $result['data']['disk']['used']);
        $this->assertEquals('40G', $result['data']['disk']['total']);
        $this->assertEquals('20G', $result['data']['disk']['available']);
        $this->assertEquals('ok', $result['data']['disk']['status']);

        // Check load average
        $this->assertEquals(0.25, $result['data']['load']['1min']);
        $this->assertEquals(0.30, $result['data']['load']['5min']);
        $this->assertEquals(0.35, $result['data']['load']['15min']);

        // Check process count
        $this->assertEquals(155, $result['data']['processes']['total']); // -1 for header

        // Check network
        $this->assertEquals('eth0', $result['data']['network']['interface']);
        $this->assertEquals(1234567890, $result['data']['network']['bytes_received']);
        $this->assertEquals(987654321, $result['data']['network']['bytes_transmitted']);
    }

    public function test_get_processes_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $result = $this->monitoringService->getProcesses();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH connection required', $result['message']);
    }

    public function test_get_processes_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $processOutput = "USER         PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND\n" .
                        "root           1  0.0  0.4 225468  9012 ?        Ss   Jan01   0:01 /sbin/init\n" .
                        "www-data    1234  5.2  2.1 456789 42000 ?        S    12:00   0:30 nginx: worker process\n" .
                        "mysql       5678  3.1  8.5 789012 170000 ?       Sl   Jan01   2:15 /usr/sbin/mysqld";

        $this->mockSshService
            ->shouldReceive('execute')
            ->with('ps aux --sort=-%cpu | head -n 11')
            ->once()
            ->andReturn([
                'output' => $processOutput,
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->monitoringService->getProcesses(10);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('processes', $result);
        $this->assertCount(3, $result['processes']);

        // Check first process
        $this->assertEquals('root', $result['processes'][0]['user']);
        $this->assertEquals('1', $result['processes'][0]['pid']);
        $this->assertEquals(0.0, $result['processes'][0]['cpu']);
        $this->assertEquals(0.4, $result['processes'][0]['memory']);
        $this->assertEquals('/sbin/init', $result['processes'][0]['command']);

        // Check second process
        $this->assertEquals('www-data', $result['processes'][1]['user']);
        $this->assertEquals('1234', $result['processes'][1]['pid']);
        $this->assertEquals(5.2, $result['processes'][1]['cpu']);
        $this->assertEquals(2.1, $result['processes'][1]['memory']);
        $this->assertEquals('nginx: worker process', $result['processes'][1]['command']);
    }

    public function test_get_service_status_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $result = $this->monitoringService->getServiceStatus(['nginx']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH connection required', $result['message']);
    }

    public function test_get_service_status_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock nginx service status
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('systemctl is-active nginx')
            ->once()
            ->andReturn([
                'output' => 'active',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock mysql service status
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('systemctl is-active mysql')
            ->once()
            ->andReturn([
                'output' => 'inactive',
                'exit_status' => 3,
                'success' => true
            ]);

        $result = $this->monitoringService->getServiceStatus(['nginx', 'mysql']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('services', $result);
        $this->assertCount(2, $result['services']);

        // Check nginx status
        $this->assertEquals('nginx', $result['services']['nginx']['name']);
        $this->assertEquals('active', $result['services']['nginx']['status']);
        $this->assertTrue($result['services']['nginx']['running']);

        // Check mysql status
        $this->assertEquals('mysql', $result['services']['mysql']['name']);
        $this->assertEquals('inactive', $result['services']['mysql']['status']);
        $this->assertFalse($result['services']['mysql']['running']);
    }

    public function test_status_level_calculation()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->monitoringService);
        $method = $reflection->getMethod('getStatusLevel');
        $method->setAccessible(true);

        // Test OK status
        $this->assertEquals('ok', $method->invoke($this->monitoringService, 50, 80, 90));
        
        // Test warning status
        $this->assertEquals('warning', $method->invoke($this->monitoringService, 85, 80, 90));
        
        // Test critical status
        $this->assertEquals('critical', $method->invoke($this->monitoringService, 95, 80, 90));
        
        // Test boundary conditions
        $this->assertEquals('ok', $method->invoke($this->monitoringService, 79.9, 80, 90));
        $this->assertEquals('warning', $method->invoke($this->monitoringService, 80.0, 80, 90));
        $this->assertEquals('warning', $method->invoke($this->monitoringService, 89.9, 80, 90));
        $this->assertEquals('critical', $method->invoke($this->monitoringService, 90.0, 80, 90));
    }

    public function test_cpu_usage_fallback_command()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock first CPU command failure
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1")
            ->once()
            ->andReturn([
                'output' => '',
                'exit_status' => 1,
                'success' => false
            ]);

        // Mock fallback CPU command success
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage}'")
            ->once()
            ->andReturn([
                'output' => '75.2',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock other commands with minimal responses
        $this->mockSshService->shouldReceive('execute')->andReturn(['output' => '', 'success' => false]);

        $result = $this->monitoringService->getServerStatus();

        $this->assertTrue($result['success']);
        $this->assertEquals(75.2, $result['data']['cpu']['usage_percent']);
    }
}