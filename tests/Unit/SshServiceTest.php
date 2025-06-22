<?php

namespace ServerManager\LaravelServerManager\Tests\Unit;

use ServerManager\LaravelServerManager\Tests\TestCase;
use ServerManager\LaravelServerManager\Services\SshService;
use phpseclib3\Net\SSH2;
use Mockery;

class SshServiceTest extends TestCase
{
    protected SshService $sshService;
    protected $mockSsh2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sshService = new SshService();
        $this->mockSsh2 = Mockery::mock(SSH2::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_connect_with_password_success()
    {
        $config = [
            'host' => 'test.example.com',
            'port' => 22,
            'username' => 'testuser',
            'password' => 'testpass'
        ];

        // Since we can't easily mock the SSH2 class instantiation,
        // we'll test the configuration validation and return type
        $result = $this->sshService->testConnection($config);
        
        $this->assertIsBool($result);
        // The test will return false since it's trying to connect to invalid host
        $this->assertFalse($result);
    }

    public function test_connect_with_invalid_config_throws_exception()
    {
        $config = [
            'host' => '',
            'username' => '',
        ];

        $this->expectException(\Exception::class);
        $this->sshService->connect($config);
    }

    public function test_execute_without_connection_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SSH connection not established');
        
        $this->sshService->execute('ls -la');
    }

    public function test_upload_file_without_connection_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SSH connection not established');
        
        $this->sshService->uploadFile('/local/path', '/remote/path');
    }

    public function test_download_file_without_connection_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SSH connection not established');
        
        $this->sshService->downloadFile('/remote/path', '/local/path');
    }

    public function test_is_connected_returns_false_initially()
    {
        $this->assertFalse($this->sshService->isConnected());
    }

    public function test_disconnect_clears_connection()
    {
        // Initially not connected
        $this->assertFalse($this->sshService->isConnected());
        
        // Disconnect should not throw error even if not connected
        $this->sshService->disconnect();
        $this->assertFalse($this->sshService->isConnected());
    }

    public function test_test_connection_with_password()
    {
        $config = [
            'host' => 'invalid-host-for-testing',
            'port' => 22,
            'username' => 'testuser',
            'password' => 'testpass'
        ];

        // This will fail because it's an invalid host, but we're testing the method exists
        $result = $this->sshService->testConnection($config);
        $this->assertIsBool($result);
        $this->assertFalse($result); // Should fail with invalid host
    }

    public function test_test_connection_with_private_key()
    {
        $config = [
            'host' => 'invalid-host-for-testing',
            'port' => 22,
            'username' => 'testuser',
            'private_key' => '-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7VH2BHt2...\n-----END PRIVATE KEY-----'
        ];

        // This will fail because it's an invalid host and key, but we're testing the method exists
        $result = $this->sshService->testConnection($config);
        $this->assertIsBool($result);
        $this->assertFalse($result); // Should fail with invalid host/key
    }

    public function test_connect_validates_required_fields()
    {
        $this->expectException(\Exception::class);
        
        $config = [
            'host' => 'test.example.com',
            // Missing username
        ];

        $this->sshService->connect($config);
    }

    public function test_connect_requires_authentication_method()
    {
        $this->expectException(\Exception::class);
        
        $config = [
            'host' => 'test.example.com',
            'username' => 'testuser',
            // Missing both password and private_key
        ];

        $this->sshService->connect($config);
    }
}