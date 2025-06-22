<?php

namespace ServerManager\LaravelServerManager\Services;

use Exception;

class MonitoringService
{
    protected SshService $sshService;

    public function __construct(SshService $sshService)
    {
        $this->sshService = $sshService;
    }

    public function getServerStatus(): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required for monitoring");
            }

            return [
                'success' => true,
                'data' => [
                    'cpu' => $this->getCpuUsage(),
                    'memory' => $this->getMemoryUsage(),
                    'disk' => $this->getDiskUsage(),
                    'load' => $this->getLoadAverage(),
                    'uptime' => $this->getUptime(),
                    'processes' => $this->getProcessCount(),
                    'network' => $this->getNetworkInfo()
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function getCpuUsage(): array
    {
        $result = $this->sshService->execute("top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1");
        
        if (!$result['success']) {
            $result = $this->sshService->execute("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage}'");
        }

        $usage = floatval(trim($result['output']));
        
        return [
            'usage_percent' => round($usage, 2),
            'status' => $this->getStatusLevel($usage, 80, 90)
        ];
    }

    protected function getMemoryUsage(): array
    {
        $result = $this->sshService->execute("free -m | awk 'NR==2{printf \"%.2f %.2f %.2f\", $3*100/$2, $3, $2}'");
        
        if ($result['success']) {
            $parts = explode(' ', trim($result['output']));
            $usagePercent = floatval($parts[0]);
            $used = floatval($parts[1]);
            $total = floatval($parts[2]);
            
            return [
                'usage_percent' => round($usagePercent, 2),
                'used_mb' => round($used, 2),
                'total_mb' => round($total, 2),
                'free_mb' => round($total - $used, 2),
                'status' => $this->getStatusLevel($usagePercent, 80, 90)
            ];
        }

        return ['error' => 'Could not retrieve memory information'];
    }

    protected function getDiskUsage(): array
    {
        $result = $this->sshService->execute("df -h / | awk 'NR==2 {print $5 \" \" $3 \" \" $2 \" \" $4}'");
        
        if ($result['success']) {
            $parts = explode(' ', trim($result['output']));
            $usagePercent = floatval(str_replace('%', '', $parts[0]));
            
            return [
                'usage_percent' => $usagePercent,
                'used' => $parts[1],
                'total' => $parts[2],
                'available' => $parts[3],
                'status' => $this->getStatusLevel($usagePercent, 80, 90)
            ];
        }

        return ['error' => 'Could not retrieve disk information'];
    }

    protected function getLoadAverage(): array
    {
        $result = $this->sshService->execute("uptime | awk -F'load average:' '{print $2}' | sed 's/,//g'");
        
        if ($result['success']) {
            $loads = array_map('trim', explode(' ', trim($result['output'])));
            
            return [
                '1min' => floatval($loads[0] ?? 0),
                '5min' => floatval($loads[1] ?? 0),
                '15min' => floatval($loads[2] ?? 0)
            ];
        }

        return ['error' => 'Could not retrieve load average'];
    }

    protected function getUptime(): array
    {
        $result = $this->sshService->execute("uptime -p");
        
        if ($result['success']) {
            return [
                'pretty' => trim($result['output']),
                'raw' => trim($result['output'])
            ];
        }

        $result = $this->sshService->execute("cat /proc/uptime | awk '{print $1}'");
        if ($result['success']) {
            $seconds = floatval(trim($result['output']));
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            
            return [
                'pretty' => sprintf("%d days, %d hours, %d minutes", $days, $hours, $minutes),
                'seconds' => $seconds
            ];
        }

        return ['error' => 'Could not retrieve uptime'];
    }

    protected function getProcessCount(): array
    {
        $result = $this->sshService->execute("ps aux | wc -l");
        
        if ($result['success']) {
            return [
                'total' => intval(trim($result['output'])) - 1
            ];
        }

        return ['error' => 'Could not retrieve process count'];
    }

    protected function getNetworkInfo(): array
    {
        $result = $this->sshService->execute("cat /proc/net/dev | grep -E '(eth|wlan|enp)' | head -1 | awk '{print $1 \" \" $2 \" \" $10}'");
        
        if ($result['success'] && trim($result['output'])) {
            $parts = explode(' ', trim($result['output']));
            $interface = rtrim($parts[0], ':');
            
            return [
                'interface' => $interface,
                'bytes_received' => intval($parts[1] ?? 0),
                'bytes_transmitted' => intval($parts[2] ?? 0)
            ];
        }

        return ['error' => 'Could not retrieve network information'];
    }

    protected function getStatusLevel(float $value, float $warningThreshold, float $criticalThreshold): string
    {
        if ($value >= $criticalThreshold) {
            return 'critical';
        } elseif ($value >= $warningThreshold) {
            return 'warning';
        } else {
            return 'ok';
        }
    }

    public function getProcesses(int $limit = 10): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $result = $this->sshService->execute("ps aux --sort=-%cpu | head -n " . ($limit + 1));
            
            if (!$result['success']) {
                throw new Exception("Failed to retrieve processes");
            }

            $lines = explode("\n", trim($result['output']));
            array_shift($lines); // Remove header
            
            $processes = [];
            foreach ($lines as $line) {
                if (trim($line)) {
                    $parts = preg_split('/\s+/', trim($line), 11);
                    if (count($parts) >= 11) {
                        $processes[] = [
                            'user' => $parts[0],
                            'pid' => $parts[1],
                            'cpu' => floatval($parts[2]),
                            'memory' => floatval($parts[3]),
                            'command' => $parts[10]
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'processes' => $processes
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getServiceStatus(array $services): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $statuses = [];
            
            foreach ($services as $service) {
                $result = $this->sshService->execute("systemctl is-active {$service}");
                $statuses[$service] = [
                    'name' => $service,
                    'status' => trim($result['output']),
                    'running' => trim($result['output']) === 'active'
                ];
            }

            return [
                'success' => true,
                'services' => $statuses
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}