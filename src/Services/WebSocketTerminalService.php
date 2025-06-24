<?php

namespace ServerManager\LaravelServerManager\Services;

use ServerManager\LaravelServerManager\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class WebSocketTerminalService
{
    protected string $jwtSecret;
    protected string $cachePrefix = 'ws_terminal_token_';
    protected int $tokenTtl;

    public function __construct()
    {
        $this->jwtSecret = config('server-manager.websocket.jwt_secret', config('app.key'));
        $this->tokenTtl = config('server-manager.websocket.token_ttl', 3600); // 1 hour
    }

    /**
     * Generate authentication token for WebSocket terminal
     */
    public function generateToken(Server $server): array
    {
        try {
            $tokenId = uniqid('ws_', true);
            
            $payload = [
                'iss' => config('app.url'),
                'sub' => $tokenId,
                'iat' => time(),
                'exp' => time() + $this->tokenTtl,
                'server_id' => $server->id,
                'server_name' => $server->name,
                'host' => $server->host,
                'port' => $server->port,
                'username' => $server->username,
            ];

            // Add authentication method
            if ($server->private_key_path && file_exists($server->private_key_path)) {
                $payload['privateKey'] = file_get_contents($server->private_key_path);
            } elseif ($server->password) {
                $payload['password'] = decrypt($server->password);
            } else {
                throw new \Exception('No authentication method available for server');
            }

            $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

            // Store token info in cache for tracking
            Cache::put($this->cachePrefix . $tokenId, [
                'server_id' => $server->id,
                'created_at' => now(),
                'used' => false,
                'websocket_url' => $this->getWebSocketUrl()
            ], now()->addSeconds($this->tokenTtl));

            Log::info("WebSocket terminal token generated", [
                'token_id' => $tokenId,
                'server_id' => $server->id,
                'server_name' => $server->name
            ]);

            return [
                'success' => true,
                'token' => $token,
                'token_id' => $tokenId,
                'websocket_url' => $this->getWebSocketUrl(),
                'expires_at' => now()->addSeconds($this->tokenTtl)->toISOString(),
                'server_name' => $server->name
            ];

        } catch (\Exception $e) {
            Log::error("Failed to generate WebSocket terminal token", [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate terminal token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate and decode token
     */
    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $payload = (array) $decoded;

            // Check if token exists in cache
            $tokenInfo = Cache::get($this->cachePrefix . $payload['sub']);
            if (!$tokenInfo) {
                throw new \Exception('Token not found or expired');
            }

            // Mark token as used
            $tokenInfo['used'] = true;
            $tokenInfo['used_at'] = now();
            Cache::put($this->cachePrefix . $payload['sub'], $tokenInfo, now()->addSeconds($this->tokenTtl));

            return [
                'success' => true,
                'payload' => $payload,
                'token_info' => $tokenInfo
            ];

        } catch (\Exception $e) {
            Log::warning("WebSocket terminal token validation failed", [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...'
            ]);

            return [
                'success' => false,
                'message' => 'Invalid or expired token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Revoke a token
     */
    public function revokeToken(string $tokenId): array
    {
        try {
            $tokenInfo = Cache::get($this->cachePrefix . $tokenId);
            if (!$tokenInfo) {
                return [
                    'success' => true,
                    'message' => 'Token already expired or not found'
                ];
            }

            Cache::forget($this->cachePrefix . $tokenId);

            Log::info("WebSocket terminal token revoked", [
                'token_id' => $tokenId,
                'server_id' => $tokenInfo['server_id']
            ]);

            return [
                'success' => true,
                'message' => 'Token revoked successfully'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to revoke WebSocket terminal token", [
                'token_id' => $tokenId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to revoke token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get active tokens for monitoring
     */
    public function getActiveTokens(): array
    {
        try {
            $pattern = $this->cachePrefix . '*';
            $keys = Cache::getRedis()->keys($pattern);
            $tokens = [];

            foreach ($keys as $key) {
                $tokenId = str_replace($this->cachePrefix, '', $key);
                $tokenInfo = Cache::get($key);
                
                if ($tokenInfo) {
                    $tokens[] = [
                        'token_id' => $tokenId,
                        'server_id' => $tokenInfo['server_id'],
                        'created_at' => $tokenInfo['created_at'],
                        'used' => $tokenInfo['used'] ?? false,
                        'used_at' => $tokenInfo['used_at'] ?? null
                    ];
                }
            }

            return [
                'success' => true,
                'tokens' => $tokens,
                'count' => count($tokens)
            ];

        } catch (\Exception $e) {
            Log::error("Failed to get active WebSocket tokens", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get active tokens: ' . $e->getMessage(),
                'tokens' => [],
                'count' => 0
            ];
        }
    }

    /**
     * Cleanup expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $pattern = $this->cachePrefix . '*';
            $keys = Cache::getRedis()->keys($pattern);
            $cleanedCount = 0;

            foreach ($keys as $key) {
                $tokenInfo = Cache::get($key);
                
                // If token info is null, it means it expired naturally
                if (!$tokenInfo) {
                    $cleanedCount++;
                    continue;
                }

                // Check if token was created too long ago (extra safety)
                if ($tokenInfo['created_at']->addSeconds($this->tokenTtl * 2)->isPast()) {
                    Cache::forget($key);
                    $cleanedCount++;
                }
            }

            if ($cleanedCount > 0) {
                Log::info("Cleaned up expired WebSocket terminal tokens", [
                    'cleaned_count' => $cleanedCount
                ]);
            }

            return $cleanedCount;

        } catch (\Exception $e) {
            Log::error("Failed to cleanup expired WebSocket tokens", [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Get WebSocket server status
     */
    public function getServerStatus(): array
    {
        try {
            $websocketUrl = $this->getWebSocketUrl();
            $host = parse_url($websocketUrl, PHP_URL_HOST);
            $port = parse_url($websocketUrl, PHP_URL_PORT);

            // Try to connect to WebSocket server
            $socket = @fsockopen($host, $port, $errno, $errstr, 5);
            
            if ($socket) {
                fclose($socket);
                $status = 'running';
                $message = 'WebSocket terminal server is running';
            } else {
                $status = 'stopped';
                $message = "WebSocket terminal server is not responding: $errstr ($errno)";
            }

            $activeTokens = $this->getActiveTokens();

            return [
                'success' => true,
                'status' => $status,
                'message' => $message,
                'websocket_url' => $websocketUrl,
                'active_tokens' => $activeTokens['count'],
                'server_config' => [
                    'host' => $host,
                    'port' => $port,
                    'token_ttl' => $this->tokenTtl
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to check server status: ' . $e->getMessage(),
                'websocket_url' => $this->getWebSocketUrl(),
                'active_tokens' => 0
            ];
        }
    }

    /**
     * Get WebSocket server URL
     */
    protected function getWebSocketUrl(): string
    {
        $host = config('server-manager.websocket.host', 'localhost');
        $port = config('server-manager.websocket.port', 3001);
        $ssl = config('server-manager.websocket.ssl', false);
        
        $protocol = $ssl ? 'wss' : 'ws';
        
        return "{$protocol}://{$host}:{$port}";
    }

    /**
     * Start WebSocket server (if managed by Laravel)
     */
    public function startServer(): array
    {
        try {
            $serverPath = config('server-manager.websocket.server_path');
            
            if (!$serverPath || !file_exists($serverPath)) {
                return [
                    'success' => false,
                    'message' => 'WebSocket server path not configured or not found'
                ];
            }

            // Check if server is already running
            $status = $this->getServerStatus();
            if ($status['status'] === 'running') {
                return [
                    'success' => true,
                    'message' => 'WebSocket server is already running',
                    'websocket_url' => $status['websocket_url']
                ];
            }

            // Start server in background
            $command = "cd " . dirname($serverPath) . " && npm start > /dev/null 2>&1 &";
            exec($command);

            // Wait a moment for server to start
            sleep(2);

            // Check if it started successfully
            $newStatus = $this->getServerStatus();
            
            if ($newStatus['status'] === 'running') {
                Log::info("WebSocket terminal server started successfully");
                
                return [
                    'success' => true,
                    'message' => 'WebSocket terminal server started successfully',
                    'websocket_url' => $newStatus['websocket_url']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to start WebSocket server: ' . $newStatus['message']
                ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to start WebSocket terminal server", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to start WebSocket server: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Stop WebSocket server (if managed by Laravel)
     */
    public function stopServer(): array
    {
        try {
            // Get server process and kill it
            $port = config('server-manager.websocket.port', 3001);
            $command = "lsof -ti tcp:{$port} | xargs kill -9 2>/dev/null";
            exec($command);

            Log::info("WebSocket terminal server stop command executed");

            return [
                'success' => true,
                'message' => 'WebSocket terminal server stop command executed'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to stop WebSocket terminal server", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to stop WebSocket server: ' . $e->getMessage()
            ];
        }
    }
}