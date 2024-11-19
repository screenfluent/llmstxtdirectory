<?php
// Start output buffering
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/php_errors.log');

// Ensure JSON response
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../includes/environment.php';
    require_once __DIR__ . '/../../db/database.php';
    require_once __DIR__ . '/../../includes/helpers.php';
    require_once __DIR__ . '/../../includes/monitoring.php';

    // Initialize database
    $db = new Database();
    
    // Check if tables exist and create them if they don't
    $db->initializeDatabase();

    // Log request data
    error_log(sprintf(
        "Submission request: Method=%s, Data=%s",
        $_SERVER['REQUEST_METHOD'],
        json_encode($_POST)
    ));

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Check for honeypot
    if (!empty($_POST['website'])) {
        throw new Exception('Invalid submission');
    }

    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    $rateLimit = 5; // submissions per hour
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

    // Clean up old entries (older than 1 hour)
    foreach ($rateLimitData as $storedIp => $data) {
        if ($currentTime - $data['timestamp'] > 3600) {
            unset($rateLimitData[$storedIp]);
        }
    }

    // Check rate limit
    if (isset($rateLimitData[$ip])) {
        if ($rateLimitData[$ip]['count'] >= $rateLimit) {
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
        'is_maintainer' => true,
        'ip_address' => $ip,
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    error_log("Attempting database submission: " . json_encode($submissionData));

    $result = $db->addSubmission($submissionData);

    if (!$result) {
        throw new Exception('Failed to save submission to database');
    }

    // Clear output buffer
    ob_clean();

    // Send success response
    $response = [
        'success' => true,
        'message' => 'Thank you for your submission!'
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    // Log the error
    error_log("Submission error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());

    // Clear output buffer
    ob_clean();

    // Send error response
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

    http_response_code(400);
    echo json_encode($response);
    exit;
}
