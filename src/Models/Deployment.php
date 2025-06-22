<?php

namespace ServerManager\LaravelServerManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'name',
        'repository',
        'branch',
        'deploy_path',
        'pre_deploy_commands',
        'build_commands',
        'post_deploy_commands',
        'ssh_key_path',
        'status',
        'last_deployment_log',
        'last_deployed_at',
        'last_commit_hash'
    ];

    protected $casts = [
        'pre_deploy_commands' => 'array',
        'build_commands' => 'array',
        'post_deploy_commands' => 'array',
        'last_deployed_at' => 'datetime'
    ];

    protected $attributes = [
        'branch' => 'main',
        'status' => 'idle'
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function isDeploying(): bool
    {
        return $this->status === 'deploying';
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function updateStatus(string $status, ?string $log = null, ?string $commitHash = null): void
    {
        $updates = ['status' => $status];
        
        if ($log) {
            $updates['last_deployment_log'] = $log;
        }
        
        if ($commitHash) {
            $updates['last_commit_hash'] = $commitHash;
        }
        
        if ($status === 'success') {
            $updates['last_deployed_at'] = now();
        }

        $this->update($updates);
    }

    public function getDeployConfig(): array
    {
        return [
            'repository' => $this->repository,
            'branch' => $this->branch,
            'deploy_path' => $this->deploy_path,
            'pre_deploy_commands' => $this->pre_deploy_commands ?? [],
            'build_commands' => $this->build_commands ?? [],
            'post_deploy_commands' => $this->post_deploy_commands ?? [],
            'ssh_key_path' => $this->ssh_key_path
        ];
    }
}