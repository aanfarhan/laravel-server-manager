<?php

namespace ServerManager\LaravelServerManager\Tests\Feature;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\DeploymentService;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeploymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $mockSshService;
    protected $mockDeploymentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSshService = Mockery::mock(SshService::class);
        $this->mockDeploymentService = Mockery::mock(DeploymentService::class);
        
        $this->app->instance(SshService::class, $this->mockSshService);
        $this->app->instance(DeploymentService::class, $this->mockDeploymentService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_deployments_view()
    {
        $response = $this->get(route('server-manager.deployments.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('server-manager::deployments.index');
    }

    public function test_deploy_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('deploy')
            ->once()
            ->with(Mockery::subset([
                'repository' => 'https://github.com/user/repo.git',
                'deploy_path' => '/var/www/html',
                'branch' => 'main'
            ]))
            ->andReturn([
                'success' => true,
                'log' => ['Deployment started', 'Repository cloned', 'Deployment completed'],
                'message' => 'Deployment completed successfully'
            ]);

        $response = $this->postJson(route('server-manager.deployments.deploy'), [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Deployment completed successfully',
            'log' => ['Deployment started', 'Repository cloned', 'Deployment completed']
        ]);
    }

    public function test_deploy_with_commands()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('deploy')
            ->once()
            ->with(Mockery::subset([
                'repository' => 'https://github.com/user/repo.git',
                'deploy_path' => '/var/www/html',
                'branch' => 'main',
                'pre_deploy_commands' => ['systemctl stop nginx'],
                'build_commands' => ['npm install', 'npm run build'],
                'post_deploy_commands' => ['systemctl start nginx']
            ]))
            ->andReturn([
                'success' => true,
                'log' => [],
                'message' => 'Deployment completed successfully'
            ]);

        $response = $this->postJson(route('server-manager.deployments.deploy'), [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html',
            'branch' => 'main',
            'pre_deploy_commands' => ['systemctl stop nginx'],
            'build_commands' => ['npm install', 'npm run build'],
            'post_deploy_commands' => ['systemctl start nginx']
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
    }

    public function test_deploy_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(route('server-manager.deployments.deploy'), [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'SSH connection required'
        ]);
    }

    public function test_deploy_auto_reconnect()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $this->mockSshService
            ->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('deploy')
            ->once()
            ->andReturn([
                'success' => true,
                'log' => [],
                'message' => 'Deployment completed successfully'
            ]);

        // Set up session with SSH config
        session(['ssh_config' => [
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]]);

        $response = $this->postJson(route('server-manager.deployments.deploy'), [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html'
        ]);

        $response->assertStatus(200);
    }

    public function test_deploy_validation_rules()
    {
        $response = $this->postJson(route('server-manager.deployments.deploy'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['repository', 'deploy_path']);
    }

    public function test_deploy_handles_exceptions()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('deploy')
            ->once()
            ->andThrow(new \Exception('Deployment failed'));

        $response = $this->postJson(route('server-manager.deployments.deploy'), [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Deployment failed'
        ]);
    }

    public function test_rollback_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('rollback')
            ->once()
            ->with(['deploy_path' => '/var/www/html'], 1)
            ->andReturn([
                'success' => true,
                'message' => 'Rolled back 1 commit(s) successfully',
                'output' => 'HEAD is now at abc123'
            ]);

        $response = $this->postJson(route('server-manager.deployments.rollback'), [
            'deploy_path' => '/var/www/html',
            'commits_back' => 1
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Rolled back 1 commit(s) successfully'
        ]);
    }

    public function test_rollback_with_custom_commits()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('rollback')
            ->once()
            ->with(['deploy_path' => '/var/www/html'], 3)
            ->andReturn([
                'success' => true,
                'message' => 'Rolled back 3 commit(s) successfully'
            ]);

        $response = $this->postJson(route('server-manager.deployments.rollback'), [
            'deploy_path' => '/var/www/html',
            'commits_back' => 3
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(route('server-manager.deployments.rollback'), [
            'deploy_path' => '/var/www/html'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'SSH connection required'
        ]);
    }

    public function test_rollback_validation_rules()
    {
        $response = $this->postJson(route('server-manager.deployments.rollback'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deploy_path']);
    }

    public function test_rollback_validates_commits_back_range()
    {
        $response = $this->postJson(route('server-manager.deployments.rollback'), [
            'deploy_path' => '/var/www/html',
            'commits_back' => 15 // Should be max 10
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['commits_back']);
    }

    public function test_rollback_handles_exceptions()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('rollback')
            ->once()
            ->andThrow(new \Exception('Rollback failed'));

        $response = $this->postJson(route('server-manager.deployments.rollback'), [
            'deploy_path' => '/var/www/html'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Rollback failed'
        ]);
    }

    public function test_status_success()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('getDeploymentStatus')
            ->once()
            ->with('/var/www/html')
            ->andReturn([
                'success' => true,
                'commit_hash' => 'abc123def456',
                'author' => 'John Doe',
                'date' => '2024-01-01 12:00:00 +0000',
                'message' => 'Latest commit message'
            ]);

        $response = $this->getJson(route('server-manager.deployments.status', [
            'deploy_path' => '/var/www/html'
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'commit_hash' => 'abc123def456',
            'author' => 'John Doe',
            'date' => '2024-01-01 12:00:00 +0000',
            'message' => 'Latest commit message'
        ]);
    }

    public function test_status_requires_ssh_connection()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false);

        $response = $this->getJson(route('server-manager.deployments.status', [
            'deploy_path' => '/var/www/html'
        ]));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'SSH connection required'
        ]);
    }

    public function test_status_validation_rules()
    {
        $response = $this->getJson(route('server-manager.deployments.status'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deploy_path']);
    }

    public function test_status_handles_exceptions()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('getDeploymentStatus')
            ->once()
            ->andThrow(new \Exception('Status check failed'));

        $response = $this->getJson(route('server-manager.deployments.status', [
            'deploy_path' => '/var/www/html'
        ]));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Status check failed'
        ]);
    }

    public function test_deploy_failure_returns_error()
    {
        $this->mockSshService
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $this->mockDeploymentService
            ->shouldReceive('deploy')
            ->once()
            ->andReturn([
                'success' => false,
                'log' => ['Deployment started', 'Error occurred'],
                'message' => 'Pre-deployment command failed'
            ]);

        $response = $this->postJson(route('server-manager.deployments.deploy'), [
            'repository' => 'https://github.com/user/repo.git',
            'deploy_path' => '/var/www/html'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Pre-deployment command failed',
            'log' => ['Deployment started', 'Error occurred']
        ]);
    }
}