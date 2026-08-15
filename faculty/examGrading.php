<?php
/**
 * Faculty - Exam Grading
 * Grade submitted exams and enter marks
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['faculty']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'submit_grade') {
        $exam_response_id = intval($_POST['exam_response_id']);
        $marks = floatval($_POST['marks']);
        $feedback = trim($_POST['feedback'] ?? '');

        try {
            $stmt = $pdo->prepare("
                UPDATE exam_responses 
                SET obtained_marks = ?, feedback = ?, status = 'graded'
                WHERE id = ?
            ");
            $stmt->execute([$marks, $feedback, $exam_response_id]);
            $message = 'Grade submitted successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Fetch faculty's exams with submission stats
$stmt = $pdo->prepare("
    SELECT e.*, c.course_name,
           COUNT(DISTINCT er.id) as total_submissions,
           SUM(CASE WHEN er.status = 'submitted' THEN 1 ELSE 0 END) as pending_grading
    FROM exams e
    LEFT JOIN courses c ON e.course_id = c.id
    LEFT JOIN exam_responses er ON e.id = er.exam_id
    WHERE e.faculty_id = ? AND e.status = 'completed'
    GROUP BY e.id
    ORDER BY e.exam_date DESC
    LIMIT 20
");
$stmt->execute([$currentUser['id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending submissions for selected exam
$selectedExam = intval($_GET['exam'] ?? ($exams[0]['id'] ?? 0));
if ($selectedExam) {
    $stmt = $pdo->prepare("
        SELECT er.*, u.name as student_name, e.total_marks
        FROM exam_responses er
        JOIN users u ON er.student_id = u.id
        JOIN exams e ON er.exam_id = e.id
        WHERE er.exam_id = ? AND er.status IN ('submitted', 'graded')
        ORDER BY er.status ASC, er.submitted_at DESC
    ");
    $stmt->execute([$selectedExam]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $submissions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Grading - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .grading-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .exam-selector {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            margin-bottom: 24px;
            padding-bottom: 8px;
        }

        .exam-option {
            padding: 12px 16px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 8px;
            color: rgba(245, 244, 255, 0.8);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .exam-option.active {
            background: linear-gradient(120deg, #8B5CF6, #D8B4FE);
            border-color: transparent;
            color: white;
        }

        .exam-option:hover {
            background: rgba(139, 92, 246, 0.15);
        }

        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .submissions-table th {
            background: rgba(139, 92, 246, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(139, 92, 246, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .submissions-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .submissions-table tbody tr:hover {
            background: rgba(139, 92, 246, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-submitted {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
        }

        .status-graded {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .grade-input {
            width: 100%;
            max-width: 80px;
            padding: 6px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 6px;
            color: #F5F4FF;
            font-weight: 600;
            text-align: center;
        }

        .grade-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #8B5CF6, #D8B4FE);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .grade-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(139, 92, 246, 0.3);
        }

        .message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border-color: #10B981;
            color: #6EE7B7;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border-color: #EF4444;
            color: #FCA5A5;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 20px 0 12px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .no-exams {
            text-align: center;
            padding: 40px;
            background: rgba(139, 92, 246, 0.05);
            border-radius: 12px;
            color: rgba(245, 244, 255, 0.6);
        }

        @media (max-width: 768px) {
            .submissions-table {
                font-size: 0.85rem;
            }

            .submissions-table th,
            .submissions-table td {
                padding: 8px;
            }

            .grade-input {
                max-width: 60px;
            }

            .grade-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body data-role="faculty">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="grading-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Exam Grading</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Grade student exam submissions</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (count($exams) > 0): ?>
                    <!-- Exam Selector -->
                    <div class="exam-selector">
                        <?php foreach ($exams as $exam): ?>
                            <a href="?exam=<?php echo $exam['id']; ?>" 
                               class="exam-option <?php echo $exam['id'] == $selectedExam ? 'active' : ''; ?>">
                                <i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars(substr($exam['exam_name'], 0, 20)); ?>
                                <span style="font-size: 0.75rem; opacity: 0.8;">
                                    (<?php echo $exam['pending_grading']; ?> pending)
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Submissions Table -->
                    <h2 class="section-title">
                        <i class="bi bi-check-circle"></i> Student Submissions (<?php echo count($submissions); ?>)
                    </h2>

                    <?php if (count($submissions) > 0): ?>
                        <table class="submissions-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Submission Time</th>
                                    <th>Marks</th>
                                    <th>Out of</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $sub): 
                                    $submittedTime = new DateTime($sub['submitted_at']);
                                    $percentage = $sub['total_marks'] > 0 ? ($sub['obtained_marks'] / $sub['total_marks']) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($sub['student_name']); ?></strong></td>
                                        <td><?php echo $submittedTime->format('M d, h:i A'); ?></td>
                                        <td>
                                            <?php if ($sub['status'] === 'graded'): ?>
                                                <?php echo $sub['obtained_marks']; ?>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="submit_grade">
                                                    <input type="hidden" name="exam_response_id" value="<?php echo $sub['id']; ?>">
                                                    <input type="number" name="marks" class="grade-input" placeholder="0" min="0" max="<?php echo $sub['total_marks']; ?>" step="0.5">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $sub['total_marks']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($sub['status']); ?>">
                                                <?php echo ucfirst($sub['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($sub['status'] === 'submitted'): ?>
                                                    <button type="submit" class="grade-btn">
                                                        <i class="bi bi-check"></i> Submit Grade
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #6EE7B7; font-size: 0.9rem;">
                                                    <i class="bi bi-check-circle-fill"></i> Graded
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; background: rgba(139, 92, 246, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                            <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>No submissions for this exam yet.</p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="no-exams">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No completed exams to grade yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
