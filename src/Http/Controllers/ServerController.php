<?php

namespace ServerManager\LaravelServerManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\MonitoringService;
use ServerManager\LaravelServerManager\Models\Server;
use ServerManager\LaravelServerManager\Http\Requests\ServerRequest;

class ServerController extends Controller
{
    protected SshService $sshService;
    protected MonitoringService $monitoringService;

    public function __construct(SshService $sshService, MonitoringService $monitoringService)
    {
        $this->sshService = $sshService;
        $this->monitoringService = $monitoringService;
    }

    public function index()
    {
        $servers = Server::latest()->get();
        return view('server-manager::servers.index', compact('servers'));
    }

    public function create()
    {
        return view('server-manager::servers.create');
    }

    public function store(ServerRequest $request)
    {
        try {
            $data = $request->validated();
            unset($data['auth_type']); // Remove helper field
            
            $server = Server::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Server created successfully',
                'server' => $server->load('monitoringLogs')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($server)
    {
        $server = Server::findOrFail($server);
        $server = $server->load(['monitoringLogs' => function($query) {
            $query->latest()->limit(10);
        }]);

        return view('server-manager::servers.show', compact('server'));
    }

    public function edit($server)
    {
        $server = Server::findOrFail($server);
        return view('server-manager::servers.edit', compact('server'));
    }

    public function update(ServerRequest $request, $server)
    {
        try {
            // Manually find the server instead of relying on route model binding
            $server = Server::findOrFail($server);
            
            $data = $request->validated();
            unset($data['auth_type']); // Remove helper field
            
            // Handle empty credential fields for updates (preserve existing credentials)
            if ($request->isMethod('put') || $request->isMethod('patch')) {
                if ($request->input('auth_type') === 'password' && empty($request->input('password'))) {
                    unset($data['password']);
                }
                if ($request->input('auth_type') === 'key' && empty($request->input('private_key'))) {
                    unset($data['private_key']);
                    unset($data['private_key_password']);
                }
            }
            
            // Handle credential switching logic
            if ($request->input('auth_type') === 'password') {
                // Clear private key fields when using password auth
                $data['private_key'] = null;
                $data['private_key_password'] = null;
            } elseif ($request->input('auth_type') === 'key') {
                // Clear password field when using key auth
                $data['password'] = null;
            }
            
            $server->update($data);
            $server->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Server updated successfully',
                'server' => $server->load('monitoringLogs')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($server)
    {
        try {
            $server = Server::findOrFail($server);
            
            // Disconnect if connected
            if ($server->isConnected()) {
                $this->sshService->disconnect();
            }

            $server->delete();

            return response()->json([
                'success' => true,
                'message' => 'Server deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function connect(Request $request)
    {
        $request->validate([
            'server_id' => 'required|exists:servers,id'
        ]);

        try {
            $server = Server::findOrFail($request->server_id);
            $config = $server->getSshConfig();

            $connected = $this->sshService->connect($config);

            if ($connected) {
                $server->updateConnectionStatus('connected');
                session(['connected_server_id' => $server->id]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Connected successfully to ' . $server->name,
                    'server' => $server->fresh()
                ]);
            } else {
                $server->updateConnectionStatus('error', 'Failed to connect');
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to ' . $server->name
                ], 400);
            }

        } catch (\Exception $e) {
            if (isset($server)) {
                $server->updateConnectionStatus('error', $e->getMessage());
            }
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(Request $request)
    {
        try {
            // Handle both existing server test and new credentials test
            if ($request->has('server_id')) {
                // Test existing server
                $request->validate([
                    'server_id' => 'required|exists:servers,id'
                ]);

                $server = Server::findOrFail($request->server_id);
                $config = $server->getSshConfig();
            } else {
                // Test new credentials
                $request->validate([
                    'name' => 'required|string',
                    'host' => 'required|string',
                    'port' => 'required|integer|min:1|max:65535',
                    'username' => 'required|string',
                    'auth_type' => 'required|in:password,key',
                    'password' => 'required_if:auth_type,password|string',
                    'private_key' => 'required_if:auth_type,key|string'
                ]);

                $config = [
                    'host' => $request->host,
                    'port' => $request->port,
                    'username' => $request->username,
                    'auth_type' => $request->auth_type,
                    'password' => $request->auth_type === 'password' ? $request->password : null,
                    'private_key' => $request->auth_type === 'key' ? $request->private_key : null,
                    'private_key_password' => $request->private_key_password
                ];
            }

            $result = $this->sshService->testConnection($config);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Connection test successful' : 'Connection test failed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function disconnect(Request $request)
    {
        try {
            $this->sshService->disconnect();
            
            // Determine which server to disconnect
            $serverId = $request->input('server_id') ?: session('connected_server_id');
            
            if ($serverId) {
                $server = Server::find($serverId);
                if ($server) {
                    $server->updateConnectionStatus('disconnected');
                }
                
                // Clear session if we're disconnecting the session server
                if ($serverId == session('connected_server_id')) {
                    session()->forget('connected_server_id');
                }
            } else {
                // Fallback: clear session anyway
                session()->forget('connected_server_id');
            }

            return response()->json([
                'success' => true,
                'message' => 'Disconnected successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function status(Request $request)
    {
        try {
            // Get the connected server or use the one specified
            $serverId = $request->server_id ?? session('connected_server_id');
            
            if (!$serverId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No server connected'
                ], 400);
            }

            $server = Server::findOrFail($serverId);

            if (!$this->sshService->isConnected()) {
                // If we have an explicit server_id but no session, this is likely after manual disconnect
                // Don't auto-reconnect in this case to respect user's disconnect action
                if ($request->server_id && !session('connected_server_id') && $server->status === 'disconnected') {
                    return response()->json([
                        'success' => false,
                        'message' => 'No server connected'
                    ], 400);
                }
                
                // Try to reconnect for connection errors or session-based reconnections
                $config = $server->getSshConfig();
                $connected = $this->sshService->connect($config);
                
                if (!$connected) {
                    $server->updateConnectionStatus('error', 'Connection lost');
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection to server lost'
                    ], 400);
                }
            }

            $status = $this->monitoringService->getServerStatus();

            // Store monitoring data if successful
            if ($status['success']) {
                $server->monitoringLogs()->create([
                    'cpu_usage' => $status['data']['cpu']['usage_percent'] ?? null,
                    'memory_usage' => $status['data']['memory']['usage_percent'] ?? null,
                    'disk_usage' => $status['data']['disk']['usage_percent'] ?? null,
                    'load_1min' => $status['data']['load']['1min'] ?? null,
                    'load_5min' => $status['data']['load']['5min'] ?? null,
                    'load_15min' => $status['data']['load']['15min'] ?? null,
                    'uptime_seconds' => $status['data']['uptime']['seconds'] ?? null,
                    'process_count' => $status['data']['processes']['total'] ?? null,
                    'network_bytes_received' => $status['data']['network']['bytes_received'] ?? null,
                    'network_bytes_transmitted' => $status['data']['network']['bytes_transmitted'] ?? null,
                ]);

                $server->updateConnectionStatus('connected');
            }

            $status['server'] = $server;
            return response()->json($status);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function processes(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $processes = $this->monitoringService->getProcesses($limit);

            return response()->json($processes);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function services(Request $request)
    {
        try {
            $services = $request->get('services', config('server-manager.monitoring.default_services'));
            $result = $this->monitoringService->getServiceStatus($services);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function list()
    {
        try {
            $servers = Server::select(['id', 'name', 'host', 'port', 'username', 'status', 'last_connected_at', 'created_at'])
                           ->with(['monitoringLogs' => function($query) {
                               $query->latest()->limit(1);
                           }])
                           ->latest()
                           ->get();

            return response()->json([
                'success' => true,
                'servers' => $servers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}