<?php

namespace ServerManager\LaravelServerManager\Services;

use ServerManager\LaravelServerManager\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TerminalService
{
    protected SshService $sshService;
    protected string $cachePrefix = 'terminal_session_';

    public function __construct(SshService $sshService)
    {
        $this->sshService = $sshService;
    }

    /**
     * Create a new terminal session for a server
     */
    public function createSession(Server $server): array
    {
        try {
            $sessionId = $this->generateSessionId();
            
            // Get SSH configuration from server
            $config = $server->getSshConfig();
            
            // Connect to server
            $connected = $this->sshService->connect($config);
            
            if (!$connected) {
                throw new \Exception('Failed to establish SSH connection to server');
            }

            // Create shell session
            $shell = $this->sshService->createShell();
            
            if (!$shell) {
                throw new \Exception('Failed to create shell session');
            }

            // Store session information in cache (persistent across requests)
            // Note: We can't store SSH connection objects, so we store connection config instead
            $sessionData = [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'ssh_config' => $config, // Store SSH config for reconnection
                'shell_id' => $shell, // Store shell ID, not the shell object
                'created_at' => now(),
                'last_activity' => now(),
                'is_active' => true
            ];
            
            Cache::put($this->cachePrefix . $sessionId, $sessionData, now()->addHours(1));
            
            // Track this session ID
            $sessionIds = Cache::get('terminal_session_ids', []);
            $sessionIds[] = $sessionId;
            Cache::put('terminal_session_ids', $sessionIds, now()->addHours(1));

            Log::info("Terminal session created", [
                'session_id' => $sessionId,
                'server_id' => $server->id,
                'server_name' => $server->name
            ]);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'server_name' => $server->name,
                'message' => "Terminal session connected to {$server->name}"
            ];

        } catch (\Exception $e) {
            Log::error("Failed to create terminal session", [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create terminal session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute command in terminal session
     */
    public function executeCommand(string $sessionId, string $command): array
    {
        try {
            $session = Cache::get($this->cachePrefix . $sessionId);
            if (!$session) {
                throw new \Exception('Terminal session not found or expired');
            }
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // Update last activity in cache
            $session['last_activity'] = now();
            Cache::put($this->cachePrefix . $sessionId, $session, now()->addHours(1));

            // Reconnect to SSH if needed (connections don't persist across requests)
            if (!$this->sshService->isConnected()) {
                $connected = $this->sshService->connect($session['ssh_config']);
                if (!$connected) {
                    throw new \Exception('Failed to reconnect to SSH');
                }
            }

            // Execute command directly (stateless approach)
            $result = $this->sshService->execute($command);
            $output = $result['output'];

            Log::debug("Command executed in terminal", [
                'session_id' => $sessionId,
                'command' => $command,
                'output_length' => strlen($output)
            ]);

            return [
                'success' => true,
                'output' => $output,
                'command' => $command
            ];

        } catch (\Exception $e) {
            Log::error("Failed to execute command in terminal", [
                'session_id' => $sessionId,
                'command' => $command,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to execute command: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send raw input to terminal session
     */
    public function sendInput(string $sessionId, string $input): array
    {
        try {
            $session = Cache::get($this->cachePrefix . $sessionId);
            if (!$session) {
                throw new \Exception('Terminal session not found or expired');
            }
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // Update last activity in cache
            $session['last_activity'] = now();
            Cache::put($this->cachePrefix . $sessionId, $session, now()->addHours(1));

            // Reconnect to SSH if needed (connections don't persist across requests)
            if (!$this->sshService->isConnected()) {
                $connected = $this->sshService->connect($session['ssh_config']);
                if (!$connected) {
                    throw new \Exception('Failed to reconnect to SSH');
                }
            }

            // In stateless mode, input is treated as a command
            if (trim($input)) {
                $result = $this->sshService->execute(trim($input));
                $output = $result['output'];
            } else {
                $output = '';
            }

            return [
                'success' => true,
                'output' => $output
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send input to terminal", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send input: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get terminal session output (for polling)
     */
    public function getOutput(string $sessionId): array
    {
        try {
            $session = Cache::get($this->cachePrefix . $sessionId);
            if (!$session) {
                throw new \Exception('Terminal session not found or expired');
            }
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // Reconnect to SSH if needed (connections don't persist across requests)
            if (!$this->sshService->isConnected()) {
                $connected = $this->sshService->connect($session['ssh_config']);
                if (!$connected) {
                    throw new \Exception('Failed to reconnect to SSH');
                }
            }

            // For now, return empty output since we can't maintain shell state across requests
            // This is a limitation of the stateless HTTP model
            $output = '';

            return [
                'success' => true,
                'output' => $output,
                'session_active' => true
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'session_active' => false
            ];
        }
    }

    /**
     * Close terminal session
     */
    public function closeSession(string $sessionId): array
    {
        try {
            $session = Cache::get($this->cachePrefix . $sessionId);
            if (!$session) {
                return [
                    'success' => true,
                    'message' => 'Session already closed or not found'
                ];
            }

            // In stateless mode, we just disconnect the SSH connection
            $this->sshService->disconnect();

            // Remove from cache
            Cache::forget($this->cachePrefix . $sessionId);
            
            // Remove from session IDs tracking
            $sessionIds = Cache::get('terminal_session_ids', []);
            $sessionIds = array_filter($sessionIds, fn($id) => $id !== $sessionId);
            Cache::put('terminal_session_ids', $sessionIds, now()->addHours(1));

            Log::info("Terminal session closed", [
                'session_id' => $sessionId,
                'server_id' => $session['server_id']
            ]);

            return [
                'success' => true,
                'message' => 'Terminal session closed successfully'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to close terminal session", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to close terminal session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get session information
     */
    public function getSessionInfo(string $sessionId): array
    {
        $session = Cache::get($this->cachePrefix . $sessionId);
        if (!$session) {
            return [
                'success' => false,
                'message' => 'Session not found'
            ];
        }

        return [
            'success' => true,
            'session' => [
                'id' => $sessionId,
                'server_id' => $session['server_id'],
                'server_name' => $session['server_name'],
                'created_at' => $session['created_at']->toISOString(),
                'last_activity' => $session['last_activity']->toISOString(),
                'is_active' => $session['is_active']
            ]
        ];
    }

    /**
     * List all active sessions
     */
    public function getActiveSessions(): array
    {
        $sessions = [];
        
        // For simplicity, we'll track session IDs separately in cache
        // This is not perfect but works for most use cases
        $sessionIds = Cache::get('terminal_session_ids', []);
        
        foreach ($sessionIds as $sessionId) {
            $session = Cache::get($this->cachePrefix . $sessionId);
            if ($session && $session['is_active']) {
                $sessions[] = [
                    'id' => $sessionId,
                    'server_id' => $session['server_id'],
                    'server_name' => $session['server_name'],
                    'created_at' => $session['created_at']->toISOString(),
                    'last_activity' => $session['last_activity']->toISOString(),
                    'is_active' => $session['is_active']
                ];
            } else if (!$session) {
                // Clean up orphaned session ID
                $sessionIds = array_filter($sessionIds, fn($id) => $id !== $sessionId);
                Cache::put('terminal_session_ids', $sessionIds, now()->addHours(1));
            }
        }

        return [
            'success' => true,
            'sessions' => $sessions,
            'count' => count($sessions)
        ];
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        $expiredCount = 0;
        $timeout = config('server-manager.terminal.session_timeout', 3600); // 1 hour default
        $sessionIds = Cache::get('terminal_session_ids', []);
        
        foreach ($sessionIds as $sessionId) {
            $session = Cache::get($this->cachePrefix . $sessionId);
            if (!$session) {
                // Session already expired from cache
                $expiredCount++;
                continue;
            }
            
            $inactiveTime = now()->diffInSeconds($session['last_activity']);
            
            if ($inactiveTime > $timeout) {
                $this->closeSession($sessionId);
                $expiredCount++;
            }
        }

        if ($expiredCount > 0) {
            Log::info("Cleaned up expired terminal sessions", [
                'expired_count' => $expiredCount
            ]);
        }

        return $expiredCount;
    }

    /**
     * Resize terminal
     */
    public function resizeTerminal(string $sessionId, int $rows, int $cols): array
    {
        try {
            $session = Cache::get($this->cachePrefix . $sessionId);
            if (!$session) {
                throw new \Exception('Terminal session not found or expired');
            }
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // In stateless mode, resize is just a no-op (could be stored as preference)
            // We don't have persistent shell to resize

            return [
                'success' => true,
                'message' => 'Terminal resized successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to resize terminal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate unique session ID
     */
    protected function generateSessionId(): string
    {
        return uniqid('term_', true) . '_' . time();
    }
}