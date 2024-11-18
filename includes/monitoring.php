<?php

function logPerformanceMetric($metric, $value, $tags = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'metric' => $metric,
        'value' => $value,
        'tags' => $tags,
        'environment' => isProduction() ? 'production' : 'staging'
    ];

    $logFile = __DIR__ . '/../logs/performance.log';
    $directory = dirname($logFile);
    
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    file_put_contents(
        $logFile,
        json_encode($logEntry) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

function startRequestTiming() {
    return microtime(true);
}

function endRequestTiming($start, $route = '') {
    $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
    logPerformanceMetric('request_duration', $duration, ['route' => $route]);
}

function logDatabaseQuery($query, $duration) {
    logPerformanceMetric('db_query_duration', $duration, ['query' => substr($query, 0, 100)]);
}

// Memory usage monitoring
function logMemoryUsage() {
    $memory = memory_get_peak_usage(true);
    logPerformanceMetric('memory_peak', $memory);
}

// Error monitoring
function logError($error, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $error,
        'context' => $context,
        'environment' => isProduction() ? 'production' : 'staging'
    ];

    $logFile = __DIR__ . '/../logs/errors.log';
    $directory = dirname($logFile);
    
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    file_put_contents(
        $logFile,
        json_encode($logEntry) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

// Register error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError($errstr, [
        'file' => $errfile,
        'line' => $errline,
        'type' => $errno
    ]);
    return false; // Continue with PHP's internal error handler
});

// Register shutdown function for fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError($error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
            'type' => $error['type']
        ]);
    }
    logMemoryUsage();
});
