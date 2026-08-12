<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../controllers/PlagiarismController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? 'text';
$text1 = $input['text1'] ?? '';
$text2 = $input['text2'] ?? '';

if (empty($text1)) {
    http_response_code(400);
    echo json_encode(['error' => 'Content is required']);
    exit;
}

$checker = new PlagiarismController();

if ($type === 'code') {
    $result = $checker->checkCodeSimilarity($text1, $text2);
} elseif ($type === 'ai_detect') {
    $result = $checker->detectAIGenerated($text1);
} else {
    $result = $checker->checkTextSimilarity($text1, $text2);
}

logAction("Plagiarism check performed ({$type})", 'plagiarism');

echo json_encode(['success' => true, 'result' => $result]);
