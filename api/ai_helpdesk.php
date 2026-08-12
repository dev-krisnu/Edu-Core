<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../controllers/AIController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

$user = getCurrentUser();
$context = $user ? "Role: {$user['role']}, Name: {$user['full_name']}" : 'Guest user';

$ai = new AIController();
$response = $ai->helpdeskResponse($message, $context);

echo json_encode(['success' => true, 'response' => $response]);
