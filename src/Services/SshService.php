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
            $this->connection->setTerminal('xterm-256color');
            $this->connection->setWindowSize(80, 24);
            $this->connection->setTimeout(10);
            
            // Wait for initial prompt to establish shell session
            $initialOutput = $this->connection->read('$|#|%|>', SSH2::READ_REGEX);

            $this->shells[$shellId] = [
                'connection' => $this->connection,
                'created_at' => time(),
                'last_activity' => time(),
                'buffer' => $initialOutput
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
            $shell['connection']->write($command . "\n");
            
            // Read output until we get a prompt
            $output = $shell['connection']->read('$|#|%|>', SSH2::READ_REGEX);
            
            // Update buffer with new output
            $this->shells[$shellId]['buffer'] = $output;

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
            $shell['connection']->write($input);
            
            // For raw input, we may not always get a prompt back
            // Read with a shorter timeout
            $originalTimeout = $shell['connection']->getTimeout();
            $shell['connection']->setTimeout(2);
            
            try {
                $output = $shell['connection']->read('$|#|%|>', SSH2::READ_REGEX);
            } catch (Exception $e) {
                // If no prompt received, just read what's available
                $output = $shell['connection']->read();
            }
            
            // Restore original timeout
            $shell['connection']->setTimeout($originalTimeout);
            
            // Update buffer
            $this->shells[$shellId]['buffer'] = $output;

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
            // Return current buffer or try to read new output
            $currentBuffer = $this->shells[$shellId]['buffer'] ?? '';
            
            // Try to read any new output with minimal timeout
            $originalTimeout = $shell['connection']->getTimeout();
            $shell['connection']->setTimeout(1);
            
            try {
                $newOutput = $shell['connection']->read('');
                if (!empty($newOutput)) {
                    $currentBuffer .= $newOutput;
                    $this->shells[$shellId]['buffer'] = $currentBuffer;
                }
            } catch (Exception $e) {
                // No new output available
            }
            
            // Restore timeout
            $shell['connection']->setTimeout($originalTimeout);

            return $currentBuffer;
        } catch (Exception $e) {
            return $this->shells[$shellId]['buffer'] ?? '';
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
            $shell = $this->shells[$shellId];
            
            // Set new window size on the connection
            $shell['connection']->setWindowSize($cols, $rows);
            
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
            
            // Send exit command to gracefully close shell
            try {
                $shell['connection']->write("exit\n");
            } catch (Exception $e) {
                // Ignore errors when sending exit command
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