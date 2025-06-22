<?php

namespace ServerManager\LaravelServerManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\MonitoringService;

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
        return view('server-manager::servers.index');
    }

    public function connect(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'username' => 'required|string',
            'port' => 'integer|min:1|max:65535',
            'password' => 'nullable|string',
            'private_key' => 'nullable|string',
            'private_key_password' => 'nullable|string',
        ]);

        try {
            $config = [
                'host' => $request->host,
                'username' => $request->username,
                'port' => $request->port ?? 22,
            ];

            if ($request->private_key) {
                $config['private_key'] = $request->private_key;
                $config['private_key_password'] = $request->private_key_password;
            } else {
                $config['password'] = $request->password;
            }

            $connected = $this->sshService->connect($config);

            if ($connected) {
                session(['ssh_config' => $config]);
                return response()->json([
                    'success' => true,
                    'message' => 'Connected successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'username' => 'required|string',
            'port' => 'integer|min:1|max:65535',
            'password' => 'nullable|string',
            'private_key' => 'nullable|string',
            'private_key_password' => 'nullable|string',
        ]);

        try {
            $config = [
                'host' => $request->host,
                'username' => $request->username,
                'port' => $request->port ?? 22,
            ];

            if ($request->private_key) {
                $config['private_key'] = $request->private_key;
                $config['private_key_password'] = $request->private_key_password;
            } else {
                $config['password'] = $request->password;
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

    public function disconnect()
    {
        $this->sshService->disconnect();
        session()->forget('ssh_config');

        return response()->json([
            'success' => true,
            'message' => 'Disconnected successfully'
        ]);
    }

    public function status()
    {
        try {
            if (!$this->sshService->isConnected()) {
                $config = session('ssh_config');
                if ($config) {
                    $this->sshService->connect($config);
                }
            }

            $status = $this->monitoringService->getServerStatus();

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
}