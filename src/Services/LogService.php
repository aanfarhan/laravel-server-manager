<?php

namespace ServerManager\LaravelServerManager\Services;

use Exception;

class LogService
{
    protected SshService $sshService;

    public function __construct(SshService $sshService)
    {
        $this->sshService = $sshService;
    }

    public function readLog(string $logPath, int $lines = 100): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required for reading logs");
            }

            $result = $this->sshService->execute("tail -{$lines} '{$logPath}'");
            
            if (!$result['success']) {
                throw new Exception("Failed to read log file: " . $result['output']);
            }

            $logLines = array_filter(explode("\n", $result['output']));
            
            return [
                'success' => true,
                'lines' => $logLines,
                'total_lines' => count($logLines),
                'file_path' => $logPath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function searchLog(string $logPath, string $pattern, int $lines = 100): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required for searching logs");
            }

            $result = $this->sshService->execute("grep -i '{$pattern}' '{$logPath}' | tail -{$lines}");
            
            $logLines = [];
            if ($result['success'] && trim($result['output'])) {
                $logLines = array_filter(explode("\n", $result['output']));
            }
            
            return [
                'success' => true,
                'lines' => $logLines,
                'total_matches' => count($logLines),
                'pattern' => $pattern,
                'file_path' => $logPath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getLogFiles(string $directory = '/var/log'): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $result = $this->sshService->execute("find '{$directory}' -type f -name '*.log' -o -name '*.log.*' | head -50");
            
            if (!$result['success']) {
                throw new Exception("Failed to list log files: " . $result['output']);
            }

            $logFiles = array_filter(explode("\n", $result['output']));
            $filesWithInfo = [];
            
            foreach ($logFiles as $file) {
                $file = trim($file);
                if ($file) {
                    $info = $this->getLogFileInfo($file);
                    if ($info['success']) {
                        $filesWithInfo[] = array_merge(['path' => $file], $info['data']);
                    }
                }
            }
            
            return [
                'success' => true,
                'files' => $filesWithInfo
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function getLogFileInfo(string $filePath): array
    {
        try {
            $result = $this->sshService->execute("ls -lah '{$filePath}' | awk '{print $5 \" \" $6 \" \" $7 \" \" $8}'");
            
            if (!$result['success']) {
                return ['success' => false];
            }

            $parts = explode(' ', trim($result['output']));
            
            return [
                'success' => true,
                'data' => [
                    'size' => $parts[0] ?? 'Unknown',
                    'modified' => implode(' ', array_slice($parts, 1, 3))
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false];
        }
    }

    public function tailLog(string $logPath, int $lines = 50): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $result = $this->sshService->execute("tail -{$lines} '{$logPath}'");
            
            if (!$result['success']) {
                throw new Exception("Failed to tail log file: " . $result['output']);
            }

            return [
                'success' => true,
                'content' => $result['output'],
                'lines' => array_filter(explode("\n", $result['output'])),
                'file_path' => $logPath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function downloadLog(string $logPath, string $localPath): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $success = $this->sshService->downloadFile($logPath, $localPath);
            
            if (!$success) {
                throw new Exception("Failed to download log file");
            }

            return [
                'success' => true,
                'local_path' => $localPath,
                'remote_path' => $logPath,
                'message' => 'Log file downloaded successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function rotateLog(string $logPath): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $timestamp = date('Y-m-d_H-i-s');
            $rotatedPath = $logPath . '.' . $timestamp;
            
            $result = $this->sshService->execute("mv '{$logPath}' '{$rotatedPath}' && touch '{$logPath}'");
            
            if (!$result['success']) {
                throw new Exception("Failed to rotate log file: " . $result['output']);
            }

            return [
                'success' => true,
                'original_path' => $logPath,
                'rotated_path' => $rotatedPath,
                'message' => 'Log file rotated successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function clearLog(string $logPath): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $result = $this->sshService->execute("truncate -s 0 '{$logPath}'");
            
            if (!$result['success']) {
                throw new Exception("Failed to clear log file: " . $result['output']);
            }

            return [
                'success' => true,
                'file_path' => $logPath,
                'message' => 'Log file cleared successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getRecentErrors(array $logPaths, int $hours = 24): array
    {
        try {
            if (!$this->sshService->isConnected()) {
                throw new Exception("SSH connection required");
            }

            $errors = [];
            $patterns = ['ERROR', 'error', 'Error', 'FATAL', 'fatal', 'Fatal', 'CRITICAL', 'critical', 'Critical'];
            
            foreach ($logPaths as $logPath) {
                foreach ($patterns as $pattern) {
                    $result = $this->sshService->execute("find '{$logPath}' -mtime -{$hours}h -exec grep -l '{$pattern}' {} \\; 2>/dev/null | head -10");
                    
                    if ($result['success'] && trim($result['output'])) {
                        $matchingFiles = array_filter(explode("\n", $result['output']));
                        
                        foreach ($matchingFiles as $file) {
                            $file = trim($file);
                            if ($file) {
                                $errorResult = $this->sshService->execute("grep '{$pattern}' '{$file}' | tail -5");
                                if ($errorResult['success'] && trim($errorResult['output'])) {
                                    $errors[] = [
                                        'file' => $file,
                                        'pattern' => $pattern,
                                        'lines' => array_filter(explode("\n", $errorResult['output']))
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            return [
                'success' => true,
                'errors' => $errors,
                'hours' => $hours
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}