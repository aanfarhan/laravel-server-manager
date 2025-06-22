<?php

namespace ServerManager\LaravelServerManager\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Exception;

class SshService
{
    protected ?SSH2 $connection = null;
    protected array $config = [];

    public function connect(array $config): bool
    {
        try {
            $this->config = $config;
            $this->connection = new SSH2($config['host'], $config['port'] ?? 22);

            if (isset($config['private_key'])) {
                $key = PublicKeyLoader::load($config['private_key'], $config['private_key_password'] ?? '');
                return $this->connection->login($config['username'], $key);
            } else {
                return $this->connection->login($config['username'], $config['password']);
            }
        } catch (Exception $e) {
            throw new Exception("SSH connection failed: " . $e->getMessage());
        }
    }

    public function execute(string $command): array
    {
        if (!$this->connection) {
            throw new Exception("SSH connection not established");
        }

        $output = $this->connection->exec($command);
        $exitStatus = $this->connection->getExitStatus();

        return [
            'output' => $output,
            'exit_status' => $exitStatus,
            'success' => $exitStatus === 0
        ];
    }

    public function uploadFile(string $localPath, string $remotePath): bool
    {
        if (!$this->connection) {
            throw new Exception("SSH connection not established");
        }

        return $this->connection->put($remotePath, $localPath, SSH2::SOURCE_LOCAL_FILE);
    }

    public function downloadFile(string $remotePath, string $localPath): bool
    {
        if (!$this->connection) {
            throw new Exception("SSH connection not established");
        }

        return $this->connection->get($remotePath, $localPath);
    }

    public function isConnected(): bool
    {
        return $this->connection && $this->connection->isConnected();
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    public function testConnection(array $config): bool
    {
        try {
            $testConnection = new SSH2($config['host'], $config['port'] ?? 22);
            
            if (isset($config['private_key'])) {
                $key = PublicKeyLoader::load($config['private_key'], $config['private_key_password'] ?? '');
                $result = $testConnection->login($config['username'], $key);
            } else {
                $result = $testConnection->login($config['username'], $config['password']);
            }

            $testConnection->disconnect();
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
}