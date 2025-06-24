<?php

namespace ServerManager\LaravelServerManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ServerManager\LaravelServerManager\Services\WebSocketTerminalService;
use ServerManager\LaravelServerManager\Models\Server;

class TerminalController extends Controller
{
    protected WebSocketTerminalService $webSocketTerminalService;

    public function __construct(WebSocketTerminalService $webSocketTerminalService)
    {
        $this->webSocketTerminalService = $webSocketTerminalService;
    }

    /**
     * Create new WebSocket terminal session
     */
    public function create(Request $request)
    {
        $request->validate([
            'server_id' => 'required|exists:servers,id'
        ]);

        try {
            $server = Server::findOrFail($request->server_id);
            
            $result = $this->webSocketTerminalService->generateToken($server);
            
            if ($result['success']) {
                // Store token ID in user session for cleanup
                $tokens = session('websocket_tokens', []);
                $tokens[] = $result['token_id'];
                session(['websocket_tokens' => $tokens]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create terminal session: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Generate WebSocket token
     */
    public function generateWebSocketToken(Request $request)
    {
        $request->validate([
            'server_id' => 'required|exists:servers,id'
        ]);

        try {
            $server = Server::findOrFail($request->server_id);
            $result = $this->webSocketTerminalService->generateToken($server);

            if ($result['success']) {
                // Store token ID in user session for cleanup
                $tokens = session('websocket_tokens', []);
                $tokens[] = $result['token_id'];
                session(['websocket_tokens' => $tokens]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate WebSocket token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke WebSocket token
     */
    public function revokeWebSocketToken(Request $request)
    {
        $request->validate([
            'token_id' => 'required|string'
        ]);

        try {
            $result = $this->webSocketTerminalService->revokeToken($request->token_id);

            if ($result['success']) {
                // Remove from user session
                $tokens = session('websocket_tokens', []);
                $tokens = array_filter($tokens, function($id) use ($request) {
                    return $id !== $request->token_id;
                });
                session(['websocket_tokens' => array_values($tokens)]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke WebSocket token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WebSocket server status
     */
    public function webSocketStatus()
    {
        try {
            $result = $this->webSocketTerminalService->getServerStatus();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get WebSocket status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List active WebSocket tokens
     */
    public function listWebSocketTokens()
    {
        try {
            $result = $this->webSocketTerminalService->getActiveTokens();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list WebSocket tokens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup expired WebSocket tokens
     */
    public function cleanupWebSocketTokens()
    {
        try {
            $cleanedCount = $this->webSocketTerminalService->cleanupExpiredTokens();
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$cleanedCount} expired tokens",
                'cleaned_count' => $cleanedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup WebSocket tokens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start WebSocket server
     */
    public function startWebSocketServer()
    {
        try {
            $result = $this->webSocketTerminalService->startServer();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start WebSocket server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop WebSocket server
     */
    public function stopWebSocketServer()
    {
        try {
            $result = $this->webSocketTerminalService->stopServer();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop WebSocket server: ' . $e->getMessage()
            ], 500);
        }
    }
}