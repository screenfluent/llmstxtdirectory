<?php

// Load environment variables
$env_path = __DIR__ . '/../.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Site settings
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'llms.txt Directory');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://llmstxt.directory');

// Database settings
define('DB_PATH', $_ENV['DB_PATH'] ?? __DIR__ . '/../db/votes.db');

// Error reporting
define('LOG_PATH', $_ENV['LOG_PATH'] ?? __DIR__ . '/../storage/logs');
define('DEBUG', filter_var($_ENV['DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

// Error handling setup
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Create log directory if it doesn't exist
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0775, true);
}

// Custom error handler
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $errstr in $errfile on line $errline\n";
    
    // Log to PHP's default error log instead of a custom file
    error_log($message);
    
    if (DEBUG) {
        return false; // Let PHP handle the error in debug mode
    } else {
        // Show user-friendly error in production
        if ($errno == E_ERROR || $errno == E_USER_ERROR) {
            include __DIR__ . '/../public/error.php';
            exit;
        }
    }
    return true;
}

set_error_handler('custom_error_handler');
