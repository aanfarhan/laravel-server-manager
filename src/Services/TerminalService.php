<?php

namespace ServerManager\LaravelServerManager\Services;

use ServerManager\LaravelServerManager\Models\Server;
use Illuminate\Support\Facades\Log;

class TerminalService
{
    protected SshService $sshService;
    protected array $activeSessions = [];

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

            // Store session information
            $this->activeSessions[$sessionId] = [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'shell' => $shell,
                'created_at' => now(),
                'last_activity' => now(),
                'is_active' => true
            ];

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
            if (!isset($this->activeSessions[$sessionId])) {
                throw new \Exception('Terminal session not found or expired');
            }

            $session = $this->activeSessions[$sessionId];
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // Update last activity
            $this->activeSessions[$sessionId]['last_activity'] = now();

            // Execute command via SSH shell
            $output = $this->sshService->executeInShell($session['shell'], $command);

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
            if (!isset($this->activeSessions[$sessionId])) {
                throw new \Exception('Terminal session not found or expired');
            }

            $session = $this->activeSessions[$sessionId];
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // Update last activity
            $this->activeSessions[$sessionId]['last_activity'] = now();

            // Send input to shell
            $output = $this->sshService->sendToShell($session['shell'], $input);

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
            if (!isset($this->activeSessions[$sessionId])) {
                throw new \Exception('Terminal session not found or expired');
            }

            $session = $this->activeSessions[$sessionId];
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // Get any pending output from shell
            $output = $this->sshService->readFromShell($session['shell']);

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
            if (!isset($this->activeSessions[$sessionId])) {
                return [
                    'success' => true,
                    'message' => 'Session already closed or not found'
                ];
            }

            $session = $this->activeSessions[$sessionId];

            // Close shell if active
            if (isset($session['shell'])) {
                $this->sshService->closeShell($session['shell']);
            }

            // Mark session as inactive
            $this->activeSessions[$sessionId]['is_active'] = false;

            // Remove from active sessions
            unset($this->activeSessions[$sessionId]);

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
        if (!isset($this->activeSessions[$sessionId])) {
            return [
                'success' => false,
                'message' => 'Session not found'
            ];
        }

        $session = $this->activeSessions[$sessionId];

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
        
        foreach ($this->activeSessions as $sessionId => $session) {
            $sessions[] = [
                'id' => $sessionId,
                'server_id' => $session['server_id'],
                'server_name' => $session['server_name'],
                'created_at' => $session['created_at']->toISOString(),
                'last_activity' => $session['last_activity']->toISOString(),
                'is_active' => $session['is_active']
            ];
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
        
        foreach ($this->activeSessions as $sessionId => $session) {
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
            if (!isset($this->activeSessions[$sessionId])) {
                throw new \Exception('Terminal session not found or expired');
            }

            $session = $this->activeSessions[$sessionId];
            
            if (!$session['is_active']) {
                throw new \Exception('Terminal session is not active');
            }

            // Resize the terminal
            $this->sshService->resizeShell($session['shell'], $rows, $cols);

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