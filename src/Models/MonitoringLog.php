<?php

namespace ServerManager\LaravelServerManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'cpu_usage',
        'memory_usage',
        'disk_usage',
        'load_1min',
        'load_5min',
        'load_15min',
        'uptime_seconds',
        'process_count',
        'network_bytes_received',
        'network_bytes_transmitted',
        'additional_metrics'
    ];

    protected $casts = [
        'cpu_usage' => 'decimal:2',
        'memory_usage' => 'decimal:2',
        'disk_usage' => 'decimal:2',
        'load_1min' => 'decimal:2',
        'load_5min' => 'decimal:2',
        'load_15min' => 'decimal:2',
        'uptime_seconds' => 'integer',
        'process_count' => 'integer',
        'network_bytes_received' => 'integer',
        'network_bytes_transmitted' => 'integer',
        'additional_metrics' => 'array'
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getCpuStatusAttribute(): string
    {
        return $this->getStatusLevel($this->cpu_usage);
    }

    public function getMemoryStatusAttribute(): string
    {
        return $this->getStatusLevel($this->memory_usage);
    }

    public function getDiskStatusAttribute(): string
    {
        return $this->getStatusLevel($this->disk_usage);
    }

    private function getStatusLevel(?float $value): string
    {
        if (!$value) return 'unknown';

        $warningThreshold = config('server-manager.monitoring.warning_thresholds.cpu', 80);
        $criticalThreshold = config('server-manager.monitoring.critical_thresholds.cpu', 90);

        if ($value >= $criticalThreshold) {
            return 'critical';
        } elseif ($value >= $warningThreshold) {
            return 'warning';
        } else {
            return 'ok';
        }
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    public function scopeWithHighUsage($query, float $threshold = 80)
    {
        return $query->where(function ($query) use ($threshold) {
            $query->where('cpu_usage', '>=', $threshold)
                  ->orWhere('memory_usage', '>=', $threshold)
                  ->orWhere('disk_usage', '>=', $threshold);
        });
    }
}