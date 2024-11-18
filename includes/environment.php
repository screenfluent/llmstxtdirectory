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

if (!function_exists('isSecure')) {
    function isSecure() {
        // Check for Cloudflare HTTPS
        if (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (isset($visitor['scheme']) && $visitor['scheme'] === 'https') {
                return true;
            }
        }
        
        // Check standard HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }
        
        // Check forwarded proto
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('getHost')) {
    function getHost() {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }
}

if (!function_exists('isProduction')) {
    function isProduction() {
        $host = getHost();
        return $host === 'llmstxt.directory';
    }
}

if (!function_exists('isDebug')) {
    function isDebug() {
        $host = getHost();
        if ($host === 'llmstxt.directory' || $host === 'staging.llmstxt.directory') {
            return false;
        }
        return env('APP_DEBUG') === 'true';
    }
}

if (!function_exists('isStaging')) {
    function isStaging() {
        $host = getHost();
        return $host === 'staging.llmstxt.directory';
    }
}

// Load environment variables
loadEnv();
