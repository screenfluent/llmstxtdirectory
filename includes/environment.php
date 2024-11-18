<?php

function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue; // Skip comments
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!empty($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadEnv();

function isProduction() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host === 'llmstxt.directory';
}

function isStaging() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host === 'staging.llmstxt.directory';
}

function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}
