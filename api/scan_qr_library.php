<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$qrCode = trim($input['qr_code'] ?? '');
$action = $input['action'] ?? 'lookup';

if (empty($qrCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'QR code is required']);
    exit;
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM library_books WHERE qr_code = ?');
$stmt->execute([$qrCode]);
$book = $stmt->fetch();

if (!$book) {
    echo json_encode(['success' => false, 'error' => 'Book not found for QR: ' . $qrCode]);
    exit;
}

if ($action === 'issue') {
    if ($book['available_copies'] <= 0) {
        echo json_encode(['success' => false, 'error' => 'No copies available']);
        exit;
    }
    $studentId = $_SESSION['user_id'];
    $dueDate = date('Y-m-d', strtotime('+14 days'));
    $db->prepare('INSERT INTO library_circulation (book_id, student_id, due_date) VALUES (?, ?, ?)')
       ->execute([$book['id'], $studentId, $dueDate]);
    $db->prepare('UPDATE library_books SET available_copies = available_copies - 1 WHERE id = ?')
       ->execute([$book['id']]);
    logAction("Issued book: {$book['title']}", 'library');
    echo json_encode(['success' => true, 'message' => "Book issued successfully! Due: {$dueDate}", 'book' => $book]);
} elseif ($action === 'return') {
    $db->prepare("UPDATE library_circulation SET returned_at = NOW() WHERE book_id = ? AND returned_at IS NULL ORDER BY id DESC LIMIT 1")
       ->execute([$book['id']]);
    $db->prepare('UPDATE library_books SET available_copies = available_copies + 1 WHERE id = ?')
       ->execute([$book['id']]);
    logAction("Returned book: {$book['title']}", 'library');
    echo json_encode(['success' => true, 'message' => 'Book returned successfully!', 'book' => $book]);
} else {
    echo json_encode([
        'success' => true,
        'message' => "Found: {$book['title']}",
        'book' => $book,
        'available' => $book['available_copies'] > 0
    ]);
}
