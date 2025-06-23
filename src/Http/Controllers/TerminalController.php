<?php

namespace ServerManager\LaravelServerManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ServerManager\LaravelServerManager\Services\TerminalService;
use ServerManager\LaravelServerManager\Services\WettyService;
use ServerManager\LaravelServerManager\Models\Server;

class TerminalController extends Controller
{
    protected TerminalService $terminalService;
    protected WettyService $wettyService;

    public function __construct(TerminalService $terminalService, WettyService $wettyService)
    {
        $this->terminalService = $terminalService;
        $this->wettyService = $wettyService;
    }

    /**
     * Create new terminal session
     */
    public function create(Request $request)
    {
        $request->validate([
            'server_id' => 'required|exists:servers,id',
            'mode' => 'sometimes|in:simple,wetty'
        ]);

        try {
            $server = Server::findOrFail($request->server_id);
            $mode = $request->input('mode', 'simple');
            
            if ($mode === 'wetty') {
                $result = $this->wettyService->startInstance($server);
                
                if ($result['success']) {
                    // Store instance ID in user session for cleanup
                    $instances = session('wetty_instances', []);
                    $instances[] = $result['instance_id'];
                    session(['wetty_instances' => $instances]);
                }
            } else {
                $result = $this->terminalService->createSession($server);
                
                if ($result['success']) {
                    // Store session ID in user session for cleanup
                    $sessions = session('terminal_sessions', []);
                    $sessions[] = $result['session_id'];
                    session(['terminal_sessions' => $sessions]);
                }
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
     * Execute command in terminal session
     */
    public function execute(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'command' => 'required|string'
        ]);

        try {
            $result = $this->terminalService->executeCommand(
                $request->session_id,
                $request->command
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute command: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send input to terminal session
     */
    public function input(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'input' => 'required|string'
        ]);

        try {
            $result = $this->terminalService->sendInput(
                $request->session_id,
                $request->input
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send input: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get terminal output (polling endpoint)
     */
    public function output(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string'
        ]);

        try {
            $result = $this->terminalService->getOutput($request->session_id);
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get output: ' . $e->getMessage(),
                'session_active' => false
            ], 500);
        }
    }

    /**
     * Resize terminal
     */
    public function resize(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'rows' => 'required|integer|min:1|max:200',
            'cols' => 'required|integer|min:1|max:500'
        ]);

        try {
            $result = $this->terminalService->resizeTerminal(
                $request->session_id,
                $request->rows,
                $request->cols
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resize terminal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close terminal session
     */
    public function close(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string'
        ]);

        try {
            $result = $this->terminalService->closeSession($request->session_id);

            if ($result['success']) {
                // Remove from user session
                $sessions = session('terminal_sessions', []);
                $sessions = array_filter($sessions, function($id) use ($request) {
                    return $id !== $request->session_id;
                });
                session(['terminal_sessions' => array_values($sessions)]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close terminal session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get session info
     */
    public function info(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string'
        ]);

        try {
            $result = $this->terminalService->getSessionInfo($request->session_id);
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get session info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List active sessions
     */
    public function sessions()
    {
        try {
            $result = $this->terminalService->getActiveSessions();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup expired sessions
     */
    public function cleanup()
    {
        try {
            $expiredCount = $this->terminalService->cleanupExpiredSessions();
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$expiredCount} expired sessions",
                'expired_count' => $expiredCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk terminal operations (for advanced use cases)
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'operations' => 'required|array',
            'operations.*.type' => 'required|in:execute,input,close',
            'operations.*.session_id' => 'required|string',
            'operations.*.data' => 'sometimes|string'
        ]);

        $results = [];

        try {
            foreach ($request->operations as $operation) {
                $sessionId = $operation['session_id'];
                $type = $operation['type'];
                $data = $operation['data'] ?? '';

                switch ($type) {
                    case 'execute':
                        $result = $this->terminalService->executeCommand($sessionId, $data);
                        break;
                    case 'input':
                        $result = $this->terminalService->sendInput($sessionId, $data);
                        break;
                    case 'close':
                        $result = $this->terminalService->closeSession($sessionId);
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Unknown operation type'];
                }

                $results[] = [
                    'operation' => $operation,
                    'result' => $result
                ];
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage(),
                'results' => $results
            ], 500);
        }
    }

    /**
     * Start wetty instance
     */
    public function startWetty(Request $request)
    {
        $request->validate([
            'server_id' => 'required|exists:servers,id'
        ]);

        try {
            $server = Server::findOrFail($request->server_id);
            $result = $this->wettyService->startInstance($server);

            if ($result['success']) {
                // Store instance ID in user session for cleanup
                $instances = session('wetty_instances', []);
                $instances[] = $result['instance_id'];
                session(['wetty_instances' => $instances]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start wetty instance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop wetty instance
     */
    public function stopWetty(Request $request)
    {
        $request->validate([
            'instance_id' => 'required|string'
        ]);

        try {
            $result = $this->wettyService->stopInstance($request->instance_id);

            if ($result['success']) {
                // Remove from user session
                $instances = session('wetty_instances', []);
                $instances = array_filter($instances, function($id) use ($request) {
                    return $id !== $request->instance_id;
                });
                session(['wetty_instances' => array_values($instances)]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop wetty instance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wetty instance info
     */
    public function wettyInfo(Request $request)
    {
        $request->validate([
            'instance_id' => 'required|string'
        ]);

        try {
            $result = $this->wettyService->getInstance($request->instance_id);
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get wetty instance info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List active wetty instances
     */
    public function wettyInstances()
    {
        try {
            $result = $this->wettyService->getActiveInstances();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list wetty instances: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup dead wetty instances
     */
    public function wettyCleanup()
    {
        try {
            $cleanedCount = $this->wettyService->cleanupInstances();
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$cleanedCount} dead instances",
                'cleaned_count' => $cleanedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup wetty instances: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wetty installation status
     */
    public function wettyStatus()
    {
        try {
            $result = $this->wettyService->getWettyStatus();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check wetty status: ' . $e->getMessage()
            ], 500);
        }
    }
}