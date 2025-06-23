<?php

namespace ServerManager\LaravelServerManager\Services;

use ServerManager\LaravelServerManager\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class WettyService
{
    protected string $cachePrefix = 'wetty_instance_';
    protected string $wettyPath;
    protected int $basePort;
    
    public function __construct()
    {
        $this->wettyPath = config('server-manager.wetty.path', 'wetty');
        $this->basePort = config('server-manager.wetty.base_port', 3000);
    }

    /**
     * Start a wetty instance for a server
     */
    public function startInstance(Server $server): array
    {
        try {
            $instanceId = $this->generateInstanceId();
            $port = $this->findAvailablePort();
            
            // Check if wetty is installed
            if (!$this->isWettyInstalled()) {
                throw new \Exception('Wetty is not installed. Please run: npm install -g wetty');
            }
            
            // Build wetty command
            $command = [
                $this->wettyPath,
                '--host', '127.0.0.1',
                '--port', (string)$port,
                '--ssh-host', $server->host,
                '--ssh-port', (string)$server->port,
                '--ssh-user', $server->username,
                '--title', 'Terminal - ' . $server->name,
                '--base', '/wetty/' . $instanceId . '/'
            ];

            // Add SSH key if configured
            if ($server->private_key_path && file_exists($server->private_key_path)) {
                $command[] = '--ssh-key';
                $command[] = $server->private_key_path;
            }

            // Start wetty process
            $process = new Process($command);
            $process->setTimeout(null);
            $process->start();

            // Wait a moment for the process to start
            sleep(1);

            if (!$process->isRunning()) {
                $error = $process->getErrorOutput() ?: $process->getOutput();
                throw new \Exception('Failed to start wetty: ' . $error);
            }

            // Store instance information in cache
            $instanceData = [
                'instance_id' => $instanceId,
                'server_id' => $server->id,
                'server_name' => $server->name,
                'port' => $port,
                'pid' => $process->getPid(),
                'url' => "http://127.0.0.1:{$port}/wetty/{$instanceId}/",
                'created_at' => now(),
                'last_activity' => now(),
                'is_active' => true
            ];
            
            Cache::put($this->cachePrefix . $instanceId, $instanceData, now()->addHours(2));
            
            // Track active instances
            $instances = Cache::get('wetty_instances', []);
            $instances[] = $instanceId;
            Cache::put('wetty_instances', $instances, now()->addHours(2));

            Log::info("Wetty instance started", [
                'instance_id' => $instanceId,
                'server_id' => $server->id,
                'port' => $port,
                'pid' => $process->getPid()
            ]);

            return [
                'success' => true,
                'instance_id' => $instanceId,
                'url' => $instanceData['url'],
                'port' => $port,
                'message' => "Wetty terminal started for {$server->name}"
            ];

        } catch (\Exception $e) {
            Log::error("Failed to start wetty instance", [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to start wetty terminal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Stop a wetty instance
     */
    public function stopInstance(string $instanceId): array
    {
        try {
            $instance = Cache::get($this->cachePrefix . $instanceId);
            if (!$instance) {
                return [
                    'success' => true,
                    'message' => 'Instance already stopped or not found'
                ];
            }

            // Kill the process
            if (isset($instance['pid'])) {
                $this->killProcess($instance['pid']);
            }

            // Remove from cache
            Cache::forget($this->cachePrefix . $instanceId);
            
            // Remove from active instances tracking
            $instances = Cache::get('wetty_instances', []);
            $instances = array_filter($instances, fn($id) => $id !== $instanceId);
            Cache::put('wetty_instances', $instances, now()->addHours(2));

            Log::info("Wetty instance stopped", [
                'instance_id' => $instanceId,
                'server_id' => $instance['server_id']
            ]);

            return [
                'success' => true,
                'message' => 'Wetty terminal stopped successfully'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to stop wetty instance", [
                'instance_id' => $instanceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to stop wetty terminal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get instance information
     */
    public function getInstance(string $instanceId): array
    {
        $instance = Cache::get($this->cachePrefix . $instanceId);
        if (!$instance) {
            return [
                'success' => false,
                'message' => 'Instance not found'
            ];
        }

        // Check if process is still running
        $isRunning = $this->isProcessRunning($instance['pid']);
        
        if (!$isRunning) {
            // Clean up dead instance
            $this->stopInstance($instanceId);
            return [
                'success' => false,
                'message' => 'Instance is no longer running'
            ];
        }

        return [
            'success' => true,
            'instance' => [
                'id' => $instanceId,
                'server_id' => $instance['server_id'],
                'server_name' => $instance['server_name'],
                'port' => $instance['port'],
                'url' => $instance['url'],
                'created_at' => $instance['created_at']->toISOString(),
                'last_activity' => $instance['last_activity']->toISOString(),
                'is_active' => $isRunning
            ]
        ];
    }

    /**
     * List all active instances
     */
    public function getActiveInstances(): array
    {
        $instances = [];
        $instanceIds = Cache::get('wetty_instances', []);
        
        foreach ($instanceIds as $instanceId) {
            $result = $this->getInstance($instanceId);
            if ($result['success']) {
                $instances[] = $result['instance'];
            }
        }

        return [
            'success' => true,
            'instances' => $instances,
            'count' => count($instances)
        ];
    }

    /**
     * Clean up expired or dead instances
     */
    public function cleanupInstances(): int
    {
        $cleanedCount = 0;
        $instanceIds = Cache::get('wetty_instances', []);
        
        foreach ($instanceIds as $instanceId) {
            $instance = Cache::get($this->cachePrefix . $instanceId);
            if (!$instance) {
                $cleanedCount++;
                continue;
            }
            
            // Check if process is still running
            if (!$this->isProcessRunning($instance['pid'])) {
                $this->stopInstance($instanceId);
                $cleanedCount++;
            }
        }

        if ($cleanedCount > 0) {
            Log::info("Cleaned up dead wetty instances", [
                'cleaned_count' => $cleanedCount
            ]);
        }

        return $cleanedCount;
    }

    /**
     * Check if wetty is installed
     */
    public function isWettyInstalled(): bool
    {
        $process = new Process(['which', $this->wettyPath]);
        $process->run();
        
        return $process->isSuccessful();
    }

    /**
     * Get wetty installation status and version
     */
    public function getWettyStatus(): array
    {
        if (!$this->isWettyInstalled()) {
            return [
                'installed' => false,
                'message' => 'Wetty is not installed',
                'install_command' => 'npm install -g wetty'
            ];
        }

        // Get version
        $process = new Process([$this->wettyPath, '--version']);
        $process->run();
        
        $version = 'unknown';
        if ($process->isSuccessful()) {
            $version = trim($process->getOutput());
        }

        return [
            'installed' => true,
            'version' => $version,
            'path' => $this->wettyPath
        ];
    }

    /**
     * Find an available port for wetty
     */
    protected function findAvailablePort(): int
    {
        $port = $this->basePort;
        $maxAttempts = 100;
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            if ($this->isPortAvailable($port)) {
                return $port;
            }
            $port++;
        }
        
        throw new \Exception('No available ports found in range');
    }

    /**
     * Check if a port is available
     */
    protected function isPortAvailable(int $port): bool
    {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($socket) {
            fclose($socket);
            return false; // Port is in use
        }
        return true; // Port is available
    }

    /**
     * Check if a process is running
     */
    protected function isProcessRunning(int $pid): bool
    {
        return file_exists("/proc/{$pid}") || (function_exists('posix_kill') && posix_kill($pid, 0));
    }

    /**
     * Kill a process
     */
    protected function killProcess(int $pid): void
    {
        if ($this->isProcessRunning($pid)) {
            if (function_exists('posix_kill')) {
                posix_kill($pid, SIGTERM);
                sleep(1);
                if ($this->isProcessRunning($pid)) {
                    posix_kill($pid, SIGKILL);
                }
            } else {
                $process = new Process(['kill', '-TERM', (string)$pid]);
                $process->run();
                sleep(1);
                if ($this->isProcessRunning($pid)) {
                    $process = new Process(['kill', '-KILL', (string)$pid]);
                    $process->run();
                }
            }
        }
    }

    /**
     * Generate unique instance ID
     */
    protected function generateInstanceId(): string
    {
        return uniqid('wetty_', true) . '_' . time();
    }
}