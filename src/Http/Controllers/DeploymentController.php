<?php

namespace ServerManager\LaravelServerManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ServerManager\LaravelServerManager\Services\SshService;
use ServerManager\LaravelServerManager\Services\DeploymentService;

class DeploymentController extends Controller
{
    protected SshService $sshService;
    protected DeploymentService $deploymentService;

    public function __construct(SshService $sshService, DeploymentService $deploymentService)
    {
        $this->sshService = $sshService;
        $this->deploymentService = $deploymentService;
    }

    public function index()
    {
        return view('server-manager::deployments.index');
    }

    public function deploy(Request $request)
    {
        $request->validate([
            'repository' => 'required|string',
            'deploy_path' => 'required|string',
            'branch' => 'string',
            'pre_deploy_commands' => 'array',
            'build_commands' => 'array',
            'post_deploy_commands' => 'array',
            'ssh_key_path' => 'nullable|string',
        ]);

        try {
            if (!$this->sshService->isConnected()) {
                $config = session('ssh_config');
                if ($config) {
                    $this->sshService->connect($config);
                } else {
                    throw new \Exception('SSH connection required');
                }
            }

            $deployConfig = [
                'repository' => $request->repository,
                'deploy_path' => $request->deploy_path,
                'branch' => $request->branch ?? 'main',
                'pre_deploy_commands' => $request->pre_deploy_commands ?? [],
                'build_commands' => $request->build_commands ?? [],
                'post_deploy_commands' => $request->post_deploy_commands ?? [],
                'ssh_key_path' => $request->ssh_key_path,
            ];

            $result = $this->deploymentService->deploy($deployConfig);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function rollback(Request $request)
    {
        $request->validate([
            'deploy_path' => 'required|string',
            'commits_back' => 'integer|min:1|max:10',
        ]);

        try {
            if (!$this->sshService->isConnected()) {
                throw new \Exception('SSH connection required');
            }

            $config = ['deploy_path' => $request->deploy_path];
            $commitsBack = $request->commits_back ?? 1;

            $result = $this->deploymentService->rollback($config, $commitsBack);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function status(Request $request)
    {
        $request->validate([
            'deploy_path' => 'required|string',
        ]);

        try {
            if (!$this->sshService->isConnected()) {
                throw new \Exception('SSH connection required');
            }

            $result = $this->deploymentService->getDeploymentStatus($request->deploy_path);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}