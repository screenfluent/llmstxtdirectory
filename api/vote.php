<?php
require_once __DIR__ . '/../db/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['implementationId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Implementation ID required']);
    exit;
}

$db = new Database();
$result = $db->addVote($data['implementationId']);
echo json_encode($result);
