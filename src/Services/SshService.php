<?php

namespace ServerManager\LaravelServerManager\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Exception;

class SshService
{
    protected ?SSH2 $connection = null;
    protected array $config = [];
    protected array $shells = [];

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

    /**
     * Create a new interactive shell session
     */
    public function createShell(): ?string
    {
        if (!$this->connection) {
            throw new Exception("SSH connection not established");
        }

        try {
            $shellId = uniqid('shell_', true);
            
            // Enable PTY for interactive shell
            $this->connection->enablePTY();
            
            // Create shell
            $shell = $this->connection->getShell();
            
            if (!$shell) {
                throw new Exception("Failed to create shell");
            }

            $this->shells[$shellId] = [
                'resource' => $shell,
                'connection' => $this->connection,
                'created_at' => time(),
                'last_activity' => time()
            ];

            return $shellId;
        } catch (Exception $e) {
            throw new Exception("Failed to create shell: " . $e->getMessage());
        }
    }

    /**
     * Execute command in existing shell session
     */
    public function executeInShell(string $shellId, string $command): string
    {
        if (!isset($this->shells[$shellId])) {
            throw new Exception("Shell session not found");
        }

        $shell = $this->shells[$shellId];
        $this->shells[$shellId]['last_activity'] = time();

        try {
            // Send command to shell
            fputs($shell['resource'], $command . "\n");
            
            // Wait a moment for output
            usleep(200000); // 200ms
            
            // Read output
            $output = '';
            while (($line = fgets($shell['resource'])) !== false) {
                $output .= $line;
                // Break if we don't get more data quickly
                if (stream_select($read = [$shell['resource']], $write = null, $except = null, 0, 100000) === 0) {
                    break;
                }
            }

            return $output;
        } catch (Exception $e) {
            throw new Exception("Failed to execute command in shell: " . $e->getMessage());
        }
    }

    /**
     * Send raw input to shell
     */
    public function sendToShell(string $shellId, string $input): string
    {
        if (!isset($this->shells[$shellId])) {
            throw new Exception("Shell session not found");
        }

        $shell = $this->shells[$shellId];
        $this->shells[$shellId]['last_activity'] = time();

        try {
            // Send input to shell
            fputs($shell['resource'], $input);
            
            // Small delay to allow processing
            usleep(100000); // 100ms
            
            // Read any immediate output
            $output = '';
            while (($line = fgets($shell['resource'])) !== false) {
                $output .= $line;
                if (stream_select($read = [$shell['resource']], $write = null, $except = null, 0, 50000) === 0) {
                    break;
                }
            }

            return $output;
        } catch (Exception $e) {
            throw new Exception("Failed to send input to shell: " . $e->getMessage());
        }
    }

    /**
     * Read pending output from shell
     */
    public function readFromShell(string $shellId): string
    {
        if (!isset($this->shells[$shellId])) {
            throw new Exception("Shell session not found");
        }

        $shell = $this->shells[$shellId];

        try {
            $output = '';
            
            // Read any available output without blocking
            while (($line = fgets($shell['resource'])) !== false) {
                $output .= $line;
                if (stream_select($read = [$shell['resource']], $write = null, $except = null, 0, 10000) === 0) {
                    break;
                }
            }

            return $output;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Resize shell terminal
     */
    public function resizeShell(string $shellId, int $rows, int $cols): bool
    {
        if (!isset($this->shells[$shellId])) {
            throw new Exception("Shell session not found");
        }

        try {
            // phpseclib3 handles terminal resizing automatically in most cases
            // For more advanced terminal control, we would need additional logic
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Close shell session
     */
    public function closeShell(string $shellId): bool
    {
        if (!isset($this->shells[$shellId])) {
            return true; // Already closed
        }

        try {
            $shell = $this->shells[$shellId];
            
            // Close shell resource
            if (is_resource($shell['resource'])) {
                fclose($shell['resource']);
            }

            unset($this->shells[$shellId]);
            return true;
        } catch (Exception $e) {
            unset($this->shells[$shellId]);
            return false;
        }
    }

    /**
     * Clean up all shells when disconnecting
     */
    public function disconnect(): void
    {
        // Close all active shells
        foreach ($this->shells as $shellId => $shell) {
            $this->closeShell($shellId);
        }

        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    /**
     * Get active shells count
     */
    public function getActiveShellsCount(): int
    {
        return count($this->shells);
    }
}