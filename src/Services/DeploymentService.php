<?php

namespace ServerManager\LaravelServerManager\Services;

use Exception;

class DeploymentService
{
    protected SshService $sshService;

    public function __construct(SshService $sshService)
    {
        $this->sshService = $sshService;
    }

    public function deploy(array $config): array
    {
        $deploymentLog = [];
        
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required for deployment");
            }

            $deploymentLog[] = "Starting deployment process...";

            if (isset($config['pre_deploy_commands'])) {
                $deploymentLog[] = "Executing pre-deployment commands...";
                foreach ($config['pre_deploy_commands'] as $command) {
                    $result = $this->sshService->execute($command);
                    $deploymentLog[] = "Command: {$command}";
                    $deploymentLog[] = "Output: " . trim($result['output']);
                    
                    if (!$result['success']) {
                        throw new Exception("Pre-deployment command failed: {$command}");
                    }
                }
            }

            $deploymentLog[] = "Cloning/updating repository...";
            $this->handleRepository($config, $deploymentLog);

            if (isset($config['build_commands'])) {
                $deploymentLog[] = "Executing build commands...";
                foreach ($config['build_commands'] as $command) {
                    $result = $this->sshService->execute("cd {$config['deploy_path']} && {$command}");
                    $deploymentLog[] = "Command: {$command}";
                    $deploymentLog[] = "Output: " . trim($result['output']);
                    
                    if (!$result['success']) {
                        throw new Exception("Build command failed: {$command}");
                    }
                }
            }

            if (isset($config['post_deploy_commands'])) {
                $deploymentLog[] = "Executing post-deployment commands...";
                foreach ($config['post_deploy_commands'] as $command) {
                    $result = $this->sshService->execute("cd {$config['deploy_path']} && {$command}");
                    $deploymentLog[] = "Command: {$command}";
                    $deploymentLog[] = "Output: " . trim($result['output']);
                    
                    if (!$result['success']) {
                        throw new Exception("Post-deployment command failed: {$command}");
                    }
                }
            }

            $deploymentLog[] = "Deployment completed successfully!";

            return [
                'success' => true,
                'log' => $deploymentLog,
                'message' => 'Deployment completed successfully'
            ];

        } catch (Exception $e) {
            $deploymentLog[] = "Deployment failed: " . $e->getMessage();
            
            return [
                'success' => false,
                'log' => $deploymentLog,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function handleRepository(array $config, array &$deploymentLog): void
    {
        $deployPath = $config['deploy_path'];
        $repository = $config['repository'];
        $branch = $config['branch'] ?? 'main';

        $checkDirResult = $this->sshService->execute("[ -d '{$deployPath}/.git' ] && echo 'exists' || echo 'not_exists'");
        
        if (strpos($checkDirResult['output'], 'exists') !== false) {
            $deploymentLog[] = "Repository exists, pulling latest changes...";
            
            $result = $this->sshService->execute("cd {$deployPath} && git fetch origin");
            if (!$result['success']) {
                throw new Exception("Failed to fetch from repository");
            }
            
            $result = $this->sshService->execute("cd {$deployPath} && git reset --hard origin/{$branch}");
            if (!$result['success']) {
                throw new Exception("Failed to reset to latest commit");
            }
            
            $deploymentLog[] = "Repository updated successfully";
        } else {
            $deploymentLog[] = "Cloning repository...";
            
            $this->sshService->execute("mkdir -p " . dirname($deployPath));
            
            $cloneCommand = "git clone";
            
            if (isset($config['ssh_key_path'])) {
                $cloneCommand = "GIT_SSH_COMMAND='ssh -i {$config['ssh_key_path']} -o StrictHostKeyChecking=no' git clone";
            }
            
            $result = $this->sshService->execute("{$cloneCommand} -b {$branch} {$repository} {$deployPath}");
            
            if (!$result['success']) {
                throw new Exception("Failed to clone repository: " . $result['output']);
            }
            
            $deploymentLog[] = "Repository cloned successfully";
        }
    }

    public function rollback(array $config, int $commitsBack = 1): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required for rollback");
            }

            $deployPath = $config['deploy_path'];
            
            $result = $this->sshService->execute("cd {$deployPath} && git reset --hard HEAD~{$commitsBack}");
            
            if (!$result['success']) {
                throw new Exception("Rollback failed: " . $result['output']);
            }

            return [
                'success' => true,
                'message' => "Rolled back {$commitsBack} commit(s) successfully",
                'output' => $result['output']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getDeploymentStatus(string $deployPath): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $result = $this->sshService->execute("cd {$deployPath} && git log -1 --pretty=format:'%H|%an|%ad|%s' --date=iso");
            
            if (!$result['success']) {
                throw new Exception("Failed to get deployment status");
            }

            $parts = explode('|', $result['output']);
            
            return [
                'success' => true,
                'commit_hash' => $parts[0] ?? '',
                'author' => $parts[1] ?? '',
                'date' => $parts[2] ?? '',
                'message' => $parts[3] ?? ''
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}