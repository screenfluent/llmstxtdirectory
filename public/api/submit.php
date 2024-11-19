<?php
require_once __DIR__ . '/../../includes/environment.php';
require_once __DIR__ . '/../../db/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Validate required fields
if (empty($_POST['llms_txt_url'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'llms.txt URL is required'
    ]);
    exit;
}

// Validate URL format
if (!filter_var($_POST['llms_txt_url'], FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid llms.txt URL format'
    ]);
    exit;
}

// Validate email if provided
if (!empty($_POST['contact_email']) && !filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Initialize database
$db = new Database();

// Check if URL already exists
if ($db->getImplementationByUrl($_POST['llms_txt_url'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'This llms.txt URL has already been submitted'
    ]);
    exit;
}

// Extract domain for name
$urlParts = parse_url($_POST['llms_txt_url']);
$domain = str_replace('www.', '', $urlParts['host']);
$name = ucfirst($domain);

// Prepare data for insertion
$data = [
    'name' => $name,
    'description' => 'Implementation from ' . $domain,
    'llms_txt_url' => trim($_POST['llms_txt_url']),
    'is_draft' => 1,
    'has_full' => 0,
    'is_featured' => 0,
    'is_requested' => 0,
    'is_maintainer' => !empty($_POST['is_maintainer']) ? 1 : 0,
    'contact_email' => !empty($_POST['contact_email']) ? trim($_POST['contact_email']) : null
];

try {
    $db->addImplementation($data);
    echo json_encode([
        'success' => true,
        'message' => 'llms.txt submitted successfully! It will be reviewed before being published.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit llms.txt. Please try again later.'
    ]);
}
