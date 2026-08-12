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
$topic = $input['topic'] ?? '';
$syllabus = $input['syllabus'] ?? '';
$count = (int) ($input['count'] ?? 5);
$difficulty = $input['difficulty'] ?? 'medium';
$bloomLevel = $input['bloom_level'] ?? 'Apply';

if (empty($topic)) {
    http_response_code(400);
    echo json_encode(['error' => 'Topic is required']);
    exit;
}

$ai = new AIController();
$questions = $ai->generateQuestions($topic, $syllabus, $count, $difficulty, $bloomLevel);

logAction("Generated {$count} AI questions on: {$topic}", 'ai');

echo json_encode(['success' => true, 'questions' => $questions, 'count' => count($questions)]);
