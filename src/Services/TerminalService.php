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
            $sessionData = [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'ssh_config' => $config, // Store SSH config for reconnection
                'created_at' => now(),
                'last_activity' => now(),
                'is_active' => true,
                'current_path' => '~', // Track current directory
                'command_buffer' => '', // Track current command being typed
                'prompt' => ($config['username'] ?? 'user') . '@' . $server->name . ':~$ ' // Current prompt
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
                'message' => "Terminal session connected to {$server->name}",
                'initial_output' => "Welcome to " . $server->name . "\r\n" . $sessionData['prompt']
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
            
            // Update current directory if it's a cd command
            if (strpos($command, 'cd ') === 0) {
                $newPath = $this->getUpdatedPath($session['current_path'], $command);
                $session['current_path'] = $newPath;
                $session['prompt'] = $this->buildPrompt($session, $newPath);
                
                // For cd commands, append the new prompt to show directory change
                $output .= "\n" . $session['prompt'];
            }

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

            // Handle terminal input character by character (like a real PTY)
            $output = $this->processTerminalInput($sessionId, $input, $session);

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
     * Process terminal input character by character (like a real PTY)
     */
    private function processTerminalInput(string $sessionId, string $input, array $session): string
    {
        $output = '';
        
        foreach (str_split($input) as $char) {
            $charCode = ord($char);
            
            switch ($charCode) {
                case 13: // Enter key (CR)
                case 10: // Line feed (LF)
                    // Execute the command
                    $command = trim($session['command_buffer']);
                    $output .= "\r\n"; // New line
                    
                    if (!empty($command)) {
                        // Execute command via SSH
                        if ($this->sshService->isConnected()) {
                            $result = $this->sshService->execute($command);
                            $output .= $result['output'];
                            
                            // Update current directory if cd command
                            if (strpos($command, 'cd ') === 0) {
                                $newPath = $this->getUpdatedPath($session['current_path'], $command);
                                $session['current_path'] = $newPath;
                                $session['prompt'] = $this->buildPrompt($session, $newPath);
                            }
                        } else {
                            $output .= "Error: SSH connection lost\r\n";
                        }
                    }
                    
                    // Reset command buffer and show new prompt
                    $session['command_buffer'] = '';
                    $output .= $session['prompt'];
                    break;
                    
                case 127: // Backspace/Delete
                case 8:   // Backspace
                    if (!empty($session['command_buffer'])) {
                        // Remove last character from buffer
                        $session['command_buffer'] = substr($session['command_buffer'], 0, -1);
                        // Send backspace sequence to terminal
                        $output .= "\x08 \x08"; // Backspace, space, backspace
                    }
                    break;
                    
                case 3: // Ctrl+C
                    $output .= "^C\r\n" . $session['prompt'];
                    $session['command_buffer'] = '';
                    break;
                    
                case 4: // Ctrl+D (EOF)
                    if (empty($session['command_buffer'])) {
                        $output .= "exit\r\n";
                        // Could mark session as closed here
                    }
                    break;
                    
                default:
                    // Regular character - add to buffer and echo
                    if ($charCode >= 32 && $charCode <= 126) { // Printable ASCII
                        $session['command_buffer'] .= $char;
                        $output .= $char; // Echo the character
                    }
                    break;
            }
        }
        
        // Update session in cache
        $session['last_activity'] = now();
        Cache::put($this->cachePrefix . $sessionId, $session, now()->addHours(1));
        
        return $output;
    }
    
    /**
     * Build prompt string
     */
    private function buildPrompt(array $session, string $currentPath): string
    {
        $server = $session['server_name'];
        $user = $session['ssh_config']['username'] ?? 'user';
        return "{$user}@{$server}:{$currentPath}$ ";
    }
    
    /**
     * Get updated path after cd command
     */
    private function getUpdatedPath(string $currentPath, string $command): string
    {
        if (preg_match('/^cd\s+(.+)$/', $command, $matches)) {
            $newPath = trim($matches[1]);
            
            if ($newPath === '~' || $newPath === '') {
                return '~';
            } elseif ($newPath === '..') {
                // Go up one directory
                if ($currentPath === '~') {
                    return '~';
                }
                $parts = explode('/', $currentPath);
                array_pop($parts);
                return empty($parts) ? '/' : implode('/', $parts);
            } elseif (strpos($newPath, '/') === 0) {
                // Absolute path
                return $newPath;
            } else {
                // Relative path
                return $currentPath === '~' ? "~/{$newPath}" : "{$currentPath}/{$newPath}";
            }
        }
        
        return $currentPath;
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