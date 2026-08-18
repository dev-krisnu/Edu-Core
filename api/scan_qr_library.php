<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['librarian']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$qrCode = trim($input['qr_code'] ?? '');
$action = $input['action'] ?? 'lookup';
$studentId = filter_var($input['student_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

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
    if (!$studentId) {
        echo json_encode(['success' => false, 'error' => 'A valid student ID is required to issue a book.']);
        exit;
    }
    $studentStmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
    $studentStmt->execute([$studentId]);
    if (!$studentStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Student not found.']);
        exit;
    }
    $dueDate = date('Y-m-d', strtotime('+14 days'));
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO library_circulation (book_id, student_id, due_date) VALUES (?, ?, ?)')
            ->execute([$book['id'], $studentId, $dueDate]);
        $db->prepare('UPDATE library_books SET available_copies = available_copies - 1 WHERE id = ?')
            ->execute([$book['id']]);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        error_log('[Library QR] Issue failed: ' . $exception->getMessage());
        echo json_encode(['success' => false, 'error' => 'Unable to issue the book.']);
        exit;
    }
    logAction("Issued book: {$book['title']}", 'library');
    echo json_encode(['success' => true, 'message' => "Book issued successfully! Due: {$dueDate}", 'book' => $book]);
} elseif ($action === 'return') {
    $db->beginTransaction();
    try {
        $returnStmt = $db->prepare("UPDATE library_circulation SET returned_at = NOW() WHERE book_id = ? AND returned_at IS NULL ORDER BY id DESC LIMIT 1");
        $returnStmt->execute([$book['id']]);
        if ($returnStmt->rowCount() !== 1) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'No active loan exists for this book.']);
            exit;
        }
        $db->prepare('UPDATE library_books SET available_copies = LEAST(total_copies, available_copies + 1) WHERE id = ?')
            ->execute([$book['id']]);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[Library QR] Return failed: ' . $exception->getMessage());
        echo json_encode(['success' => false, 'error' => 'Unable to return the book.']);
        exit;
    }
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
