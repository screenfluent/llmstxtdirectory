<?php

class LogAnalyzer {
    private $performanceLogPath;
    private $errorLogPath;
    private $maxLogSize = 10 * 1024 * 1024; // 10MB
    private $rotationCount = 5;
    
    public function __construct() {
        $this->performanceLogPath = __DIR__ . '/../logs/performance.log';
        $this->errorLogPath = __DIR__ . '/../logs/errors.log';
        $this->checkLogRotation();
    }

    private function checkLogRotation() {
        $this->rotateLogIfNeeded($this->performanceLogPath);
        $this->rotateLogIfNeeded($this->errorLogPath);
    }

    private function rotateLogIfNeeded($logPath) {
        if (!file_exists($logPath)) {
            return;
        }

        if (filesize($logPath) > $this->maxLogSize) {
            for ($i = $this->rotationCount; $i > 0; $i--) {
                $oldFile = $logPath . '.' . ($i - 1);
                $newFile = $logPath . '.' . $i;
                if (file_exists($oldFile)) {
                    rename($oldFile, $newFile);
                }
            }
            rename($logPath, $logPath . '.1');
            touch($logPath);
        }
    }
    
    public function getRecentPerformanceMetrics($limit = 100, $timeRange = null) {
        if (!file_exists($this->performanceLogPath)) {
            return [];
        }
        
        $files = [$this->performanceLogPath];
        for ($i = 1; $i <= $this->rotationCount; $i++) {
            $rotatedFile = $this->performanceLogPath . '.' . $i;
            if (file_exists($rotatedFile)) {
                $files[] = $rotatedFile;
            }
        }
        
        $metrics = [];
        $now = time();
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $lines = array_reverse(file($file));
            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if (!$data) continue;
                
                if ($timeRange) {
                    $metricTime = strtotime($data['timestamp']);
                    if ($now - $metricTime > $timeRange) {
                        continue;
                    }
                }
                
                $metrics[] = $data;
                if (count($metrics) >= $limit) {
                    break 2;
                }
            }
        }
        
        return $metrics;
    }
    
    public function getPerformanceStats($timeRange = null) {
        $metrics = $this->getRecentPerformanceMetrics(1000, $timeRange);
        
        $stats = [
            'request_duration' => $this->initializeStats(),
            'db_query_duration' => $this->initializeStats(),
            'memory_peak' => $this->initializeStats(),
            'routes' => []
        ];
        
        $routeStats = [];
        
        foreach ($metrics as $metric) {
            $value = floatval($metric['value']);
            
            switch ($metric['metric']) {
                case 'request_duration':
                    $this->updateStats($stats['request_duration'], $value);
                    
                    // Track route-specific stats
                    $route = $metric['tags']['route'] ?? 'unknown';
                    if (!isset($routeStats[$route])) {
                        $routeStats[$route] = $this->initializeStats();
                    }
                    $this->updateStats($routeStats[$route], $value);
                    break;
                    
                case 'db_query_duration':
                    $this->updateStats($stats['db_query_duration'], $value);
                    break;
                    
                case 'memory_peak':
                    $this->updateStats($stats['memory_peak'], $value);
                    break;
            }
        }
        
        // Calculate final statistics
        $this->finalizeStats($stats['request_duration']);
        $this->finalizeStats($stats['db_query_duration']);
        $this->finalizeStats($stats['memory_peak']);
        
        // Process route statistics
        $stats['routes'] = $this->processRouteStats($routeStats);
        
        return $stats;
    }
    
    private function initializeStats() {
        return [
            'avg' => 0,
            'max' => 0,
            'min' => PHP_FLOAT_MAX,
            'p95' => 0,
            'p99' => 0,
            'median' => 0,
            'count' => 0,
            'values' => []
        ];
    }
    
    private function updateStats(&$stats, $value) {
        $stats['values'][] = $value;
        $stats['count']++;
        $stats['max'] = max($stats['max'], $value);
        $stats['min'] = min($stats['min'], $value);
    }
    
    private function finalizeStats(&$stats) {
        if (empty($stats['values'])) {
            return;
        }
        
        sort($stats['values']);
        $count = count($stats['values']);
        
        $stats['avg'] = array_sum($stats['values']) / $count;
        $stats['median'] = $stats['values'][(int)($count * 0.5)];
        $stats['p95'] = $stats['values'][(int)($count * 0.95)];
        $stats['p99'] = $stats['values'][(int)($count * 0.99)];
        
        // Calculate standard deviation
        $mean = $stats['avg'];
        $variance = array_reduce($stats['values'], function($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0) / $count;
        $stats['std_dev'] = sqrt($variance);
        
        // Clean up values array to save memory
        unset($stats['values']);
    }
    
    private function processRouteStats($routeStats) {
        $processed = [];
        foreach ($routeStats as $route => $stats) {
            if (empty($stats['values'])) continue;
            
            $this->finalizeStats($stats);
            $processed[$route] = $stats;
        }
        
        return $processed;
    }
    
    public function getRecentErrors($limit = 20, $timeRange = null) {
        if (!file_exists($this->errorLogPath)) {
            return [];
        }
        
        $files = [$this->errorLogPath];
        for ($i = 1; $i <= $this->rotationCount; $i++) {
            $rotatedFile = $this->errorLogPath . '.' . $i;
            if (file_exists($rotatedFile)) {
                $files[] = $rotatedFile;
            }
        }
        
        $errors = [];
        $now = time();
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $lines = array_reverse(file($file));
            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if (!$data) continue;
                
                if ($timeRange) {
                    $errorTime = strtotime($data['timestamp']);
                    if ($now - $errorTime > $timeRange) {
                        continue;
                    }
                }
                
                $errors[] = $data;
                if (count($errors) >= $limit) {
                    break 2;
                }
            }
        }
        
        return $errors;
    }

    public function cleanOldLogs($maxAge = 604800) { // 7 days by default
        $this->cleanOldRotatedLogs($this->performanceLogPath, $maxAge);
        $this->cleanOldRotatedLogs($this->errorLogPath, $maxAge);
    }

    private function cleanOldRotatedLogs($basePath, $maxAge) {
        for ($i = 1; $i <= $this->rotationCount; $i++) {
            $rotatedFile = $basePath . '.' . $i;
            if (file_exists($rotatedFile) && (time() - filemtime($rotatedFile) > $maxAge)) {
                unlink($rotatedFile);
            }
        }
    }
    
    // Debug method to inspect log contents
    public function debugInspectLogs() {
        $metrics = $this->getRecentPerformanceMetrics(10);
        error_log("Recent metrics: " . print_r($metrics, true));
        
        if (file_exists($this->performanceLogPath)) {
            error_log("Performance log exists, size: " . filesize($this->performanceLogPath));
            $sample = file_get_contents($this->performanceLogPath, false, null, 0, 1000);
            error_log("Sample log content: " . $sample);
        } else {
            error_log("Performance log does not exist");
        }
    }
}
