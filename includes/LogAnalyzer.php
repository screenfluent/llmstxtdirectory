<?php

class LogAnalyzer {
    private $performanceLogPath;
    private $errorLogPath;
    
    public function __construct() {
        $this->performanceLogPath = __DIR__ . '/../logs/performance.log';
        $this->errorLogPath = __DIR__ . '/../logs/errors.log';
    }
    
    public function getRecentPerformanceMetrics($limit = 100) {
        if (!file_exists($this->performanceLogPath)) {
            return [];
        }
        
        $lines = array_reverse(array_slice(file($this->performanceLogPath), -$limit));
        $metrics = [];
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data) {
                $metrics[] = $data;
            }
        }
        
        return $metrics;
    }
    
    public function getPerformanceStats() {
        $metrics = $this->getRecentPerformanceMetrics(1000);
        $stats = [
            'request_duration' => [
                'avg' => 0,
                'max' => 0,
                'p95' => 0,
                'count' => 0
            ],
            'db_query_duration' => [
                'avg' => 0,
                'max' => 0,
                'p95' => 0,
                'count' => 0
            ],
            'memory_peak' => [
                'avg' => 0,
                'max' => 0,
                'count' => 0
            ]
        ];
        
        $durations = [];
        $queryDurations = [];
        $memoryUsages = [];
        
        foreach ($metrics as $metric) {
            switch ($metric['metric']) {
                case 'request_duration':
                    $durations[] = $metric['value'];
                    break;
                case 'db_query_duration':
                    $queryDurations[] = $metric['value'];
                    break;
                case 'memory_peak':
                    $memoryUsages[] = $metric['value'];
                    break;
            }
        }
        
        // Calculate stats for request durations
        if (!empty($durations)) {
            sort($durations);
            $stats['request_duration'] = [
                'avg' => array_sum($durations) / count($durations),
                'max' => max($durations),
                'p95' => $durations[(int)(count($durations) * 0.95)],
                'count' => count($durations)
            ];
        }
        
        // Calculate stats for query durations
        if (!empty($queryDurations)) {
            sort($queryDurations);
            $stats['db_query_duration'] = [
                'avg' => array_sum($queryDurations) / count($queryDurations),
                'max' => max($queryDurations),
                'p95' => $queryDurations[(int)(count($queryDurations) * 0.95)],
                'count' => count($queryDurations)
            ];
        }
        
        // Calculate stats for memory usage
        if (!empty($memoryUsages)) {
            $stats['memory_peak'] = [
                'avg' => array_sum($memoryUsages) / count($memoryUsages),
                'max' => max($memoryUsages),
                'count' => count($memoryUsages)
            ];
        }
        
        return $stats;
    }
    
    public function getRecentErrors($limit = 50) {
        if (!file_exists($this->errorLogPath)) {
            return [];
        }
        
        $lines = array_reverse(array_slice(file($this->errorLogPath), -$limit));
        $errors = [];
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data) {
                $errors[] = $data;
            }
        }
        
        return $errors;
    }
    
    public function formatDuration($ms) {
        return number_format($ms, 2) . 'ms';
    }
    
    public function formatMemory($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
