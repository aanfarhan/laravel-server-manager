<?php

namespace ServerManager\LaravelServerManager\Tests\Unit;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\DeploymentService;
use ServerManager\LaravelServerManager\Services\SshService;
use Mockery;

class DeploymentServiceTest extends TestCase
{
    protected DeploymentService $deploymentService;
    protected $mockSshService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSshService = Mockery::mock(SshService::class);
        $this->deploymentService = new DeploymentService($this->mockSshService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deploy_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $config = [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main'
        ];

        $result = $this->deploymentService->deploy($config);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH connection required', $result['message']);
    }

    public function test_deploy_with_new_repository()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock all SSH execute calls to return success
        $this->mockSshService
            ->shouldReceive('execute')
            ->andReturn([
                'output' => 'not_exists',
                'exit_status' => 0,
                'success' => true
            ]);

        $config = [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main'
        ];

        $result = $this->deploymentService->deploy($config);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Deployment completed successfully', $result['message']);
        $this->assertIsArray($result['log']);
    }

    public function test_deploy_with_existing_repository()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock checking if directory exists (exists)
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("[ -d '/var/www/html/.git' ] && echo 'exists' || echo 'not_exists'")
            ->once()
            ->andReturn([
                'output' => 'exists',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock git fetch
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && git fetch origin')
            ->once()
            ->andReturn([
                'output' => 'Fetching origin...',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock git reset
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && git reset --hard origin/main')
            ->once()
            ->andReturn([
                'output' => 'HEAD is now at abc123 Latest commit',
                'exit_status' => 0,
                'success' => true
            ]);

        $config = [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main'
        ];

        $result = $this->deploymentService->deploy($config);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Deployment completed successfully', $result['message']);
    }

    public function test_deploy_with_pre_deploy_commands()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock pre-deploy command
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('systemctl stop nginx')
            ->once()
            ->andReturn([
                'output' => 'Stopped nginx',
                'exit_status' => 0,
                'success' => true
            ]);

        // Mock repository handling
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("[ -d '/var/www/html/.git' ] && echo 'exists' || echo 'not_exists'")
            ->once()
            ->andReturn([
                'output' => 'exists',
                'exit_status' => 0,
                'success' => true
            ]);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && git fetch origin')
            ->once()
            ->andReturn(['output' => '', 'exit_status' => 0, 'success' => true]);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && git reset --hard origin/main')
            ->once()
            ->andReturn(['output' => '', 'exit_status' => 0, 'success' => true]);

        $config = [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main',
            'pre_deploy_commands' => ['systemctl stop nginx']
        ];

        $result = $this->deploymentService->deploy($config);

        $this->assertTrue($result['success']);
    }

    public function test_deploy_with_build_commands()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock repository handling
        $this->mockSshService
            ->shouldReceive('execute')
            ->with("[ -d '/var/www/html/.git' ] && echo 'exists' || echo 'not_exists'")
            ->once()
            ->andReturn([
                'output' => 'exists',
                'exit_status' => 0,
                'success' => true
            ]);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && git fetch origin')
            ->once()
            ->andReturn(['output' => '', 'exit_status' => 0, 'success' => true]);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && git reset --hard origin/main')
            ->once()
            ->andReturn(['output' => '', 'exit_status' => 0, 'success' => true]);

        // Mock build command
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && npm install')
            ->once()
            ->andReturn([
                'output' => 'npm packages installed',
                'exit_status' => 0,
                'success' => true
            ]);

        $config = [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main',
            'build_commands' => ['npm install']
        ];

        $result = $this->deploymentService->deploy($config);

        $this->assertTrue($result['success']);
    }

    public function test_deploy_handles_command_failure()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        // Mock failed pre-deploy command
        $this->mockSshService
            ->shouldReceive('execute')
            ->with('invalid-command')
            ->once()
            ->andReturn([
                'output' => 'Command not found',
                'exit_status' => 127,
                'success' => false
            ]);

        $config = [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main',
            'pre_deploy_commands' => ['invalid-command']
        ];

        $result = $this->deploymentService->deploy($config);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Pre-deployment command failed', $result['message']);
    }

    public function test_rollback_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $config = ['deploy_path' => '/var/www/html'];
        $result = $this->deploymentService->rollback($config, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH connection required', $result['message']);
    }

    public function test_rollback_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with('cd /var/www/html && git reset --hard HEAD~1')
            ->once()
            ->andReturn([
                'output' => 'HEAD is now at abc123 Previous commit',
                'exit_status' => 0,
                'success' => true
            ]);

        $config = ['deploy_path' => '/var/www/html'];
        $result = $this->deploymentService->rollback($config, 1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Rolled back 1 commit(s) successfully', $result['message']);
    }

    public function test_get_deployment_status_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockSshService
            ->shouldReceive('execute')
            ->with("cd /var/www/html && git log -1 --pretty=format:'%H|%an|%ad|%s' --date=iso")
            ->once()
            ->andReturn([
                'output' => 'abc123def456|John Doe|2024-01-01 12:00:00 +0000|Latest commit message',
                'exit_status' => 0,
                'success' => true
            ]);

        $result = $this->deploymentService->getDeploymentStatus('/var/www/html');

        $this->assertTrue($result['success']);
        $this->assertEquals('abc123def456', $result['commit_hash']);
        $this->assertEquals('John Doe', $result['author']);
        $this->assertEquals('2024-01-01 12:00:00 +0000', $result['date']);
        $this->assertEquals('Latest commit message', $result['message']);
    }

    public function test_get_deployment_status_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $result = $this->deploymentService->getDeploymentStatus('/var/www/html');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH connection required', $result['message']);
    }
}