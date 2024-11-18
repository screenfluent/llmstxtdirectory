<?php

if (!function_exists('loadEnv')) {
    function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('isProduction')) {
    function isProduction() {
        return env('APP_ENV') === 'production';
    }
}

if (!function_exists('isDebug')) {
    function isDebug() {
        return env('APP_DEBUG') === 'true';
    }
}

if (!function_exists('isStaging')) {
    function isStaging() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return $host === 'staging.llmstxt.directory';
    }
}

// Load environment variables
loadEnv();
