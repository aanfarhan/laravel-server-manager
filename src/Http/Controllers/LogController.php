<?php

namespace ServerManager\LaravelServerManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\LogService;
use ServerManager\LaravelServerManager\Models\Server;

class LogController extends Controller
{
    protected SshService $sshService;
    protected LogService $logService;

    public function __construct(SshService $sshService, LogService $logService)
    {
        $this->sshService = $sshService;
        $this->logService = $logService;
    }

    public function index()
    {
        return view('server-manager::logs.index');
    }

    /**
     * Ensure SSH connection is available, attempting auto-reconnect if needed
     */
    protected function ensureConnection(): void
    {
        if (!$this->sshService->isConnected()) {
            // Get connected server from session first
            $serverId = session('connected_server_id');
            
            // If no session server, try to find any connected server
            if (!$serverId) {
                $server = Server::where('status', 'connected')->first();
                if (!$server) {
                    throw new \Exception('SSH connection required');
                }
            } else {
                $server = Server::findOrFail($serverId);
            }
            
            // Attempt to connect
            $config = $server->getSshConfig();
            $connected = $this->sshService->connect($config);
            
            if (!$connected) {
                $server->updateConnectionStatus('error', 'Connection lost');
                throw new \Exception('SSH connection required');
            }
            
            $server->updateConnectionStatus('connected');
            
            // Set session if it wasn't already set
            if (!session('connected_server_id')) {
                session(['connected_server_id' => $server->id]);
            }
        }
    }

    public function files(Request $request)
    {
        try {
            $this->ensureConnection();

            $directory = $request->get('directory', '/var/log');
            $result = $this->logService->getLogFiles($directory);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function read(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'lines' => 'integer|min:1|max:1000',
        ]);

        try {
            $this->ensureConnection();

            $lines = $request->get('lines', config('server-manager.logs.default_lines'));
            $result = $this->logService->readLog($request->path, $lines);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'pattern' => 'required|string',
            'lines' => 'integer|min:1|max:1000',
        ]);

        try {
            $this->ensureConnection();

            $lines = $request->get('lines', config('server-manager.logs.default_lines'));
            $result = $this->logService->searchLog($request->path, $request->pattern, $lines);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tail(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'lines' => 'integer|min:1|max:200',
        ]);

        try {
            $this->ensureConnection();

            $lines = $request->get('lines', 50);
            $result = $this->logService->tailLog($request->path, $lines);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $this->ensureConnection();

            $localPath = storage_path('app/temp/' . basename($request->path) . '_' . time());
            $result = $this->logService->downloadLog($request->path, $localPath);

            if ($result['success']) {
                return response()->download($localPath)->deleteFileAfterSend();
            } else {
                return response()->json($result, 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clear(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $this->ensureConnection();

            $result = $this->logService->clearLog($request->path);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function rotate(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $this->ensureConnection();

            $result = $this->logService->rotateLog($request->path);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function errors(Request $request)
    {
        try {
            $this->ensureConnection();

            $logPaths = $request->get('paths', config('server-manager.logs.default_paths'));
            $hours = $request->get('hours', 24);

            $result = $this->logService->getRecentErrors($logPaths, $hours);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}