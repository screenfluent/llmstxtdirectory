<?php
// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/php_errors.log');

// Ensure clean output
ob_start();

// Ensure JSON response
header('Content-Type: application/json');

function sendJsonResponse($data, $statusCode = 200) {
    ob_clean();
    http_response_code($statusCode);
    echo json_encode($data);
    ob_end_flush();
    exit;
}

function checkRateLimit($ip, $limit = 5, $period = 3600) {
    $rateLimitFile = __DIR__ . '/../../storage/ratelimit.json';
    $currentTime = time();

    // Ensure storage directory exists
    $storageDir = dirname($rateLimitFile);
    if (!file_exists($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    // Load rate limit data
    $rateLimitData = [];
    if (file_exists($rateLimitFile)) {
        $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
    }

    // Clean up old entries
    foreach ($rateLimitData as $storedIp => $data) {
        if ($currentTime - $data['timestamp'] > $period) {
            unset($rateLimitData[$storedIp]);
        }
    }

    // Check rate limit
    if (isset($rateLimitData[$ip])) {
        if ($rateLimitData[$ip]['count'] >= $limit) {
            throw new Exception('Too many submissions. Please try again later.');
        }
        $rateLimitData[$ip]['count']++;
    } else {
        $rateLimitData[$ip] = [
            'count' => 1,
            'timestamp' => $currentTime
        ];
    }

    // Save rate limit data
    file_put_contents($rateLimitFile, json_encode($rateLimitData));
}

try {
    require_once __DIR__ . '/../../includes/environment.php';
    require_once __DIR__ . '/../../db/database.php';
    require_once __DIR__ . '/../../includes/helpers.php';
    require_once __DIR__ . '/../../includes/monitoring.php';

    // Initialize database
    $db = new Database();
    $db->initializeDatabase();

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Check for honeypot
    if (!empty($_POST['website'])) {
        throw new Exception('Invalid submission');
    }

    // Check rate limit
    checkRateLimit($_SERVER['REMOTE_ADDR']);

    // Validate required fields
    if (empty($_POST['llms_txt_url'])) {
        throw new Exception('llms.txt URL is required');
    }

    // Basic URL validation
    if (!filter_var($_POST['llms_txt_url'], FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }

    // Validate email if provided
    $email = $_POST['email'] ?? null;
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Normalize URL
    $url = rtrim($_POST['llms_txt_url'], '/');
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }

    // Store submission in database
    $submissionData = [
        'url' => $url,
        'email' => $email,
        'is_maintainer' => !empty($_POST['is_maintainer']),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    if (!$db->addSubmission($submissionData)) {
        throw new Exception('Failed to save submission to database');
    }

    sendJsonResponse([
        'success' => true,
        'message' => 'Thank you for your submission!'
    ]);

} catch (Exception $e) {
    error_log("Submission error: " . $e->getMessage());

    $response = [
        'success' => false,
        'message' => !isProduction() ? $e->getMessage() : 'An error occurred. Please try again.'
    ];

    if (!isProduction()) {
        $response['debug'] = [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }

    sendJsonResponse($response, 400);
}
