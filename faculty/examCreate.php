<?php
/**
 * Exam Create - Create and Schedule Exams
 * Setup exams with questions and configuration
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

// Handle exam creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'create_exam') {
        $exam_name = trim($_POST['exam_name']);
        $course_id = intval($_POST['course_id']);
        $exam_date = trim($_POST['exam_date']);
        $exam_time = trim($_POST['exam_time']);
        $duration = intval($_POST['duration']);
        $total_marks = intval($_POST['total_marks']);

        if ($exam_name && $course_id && $exam_date && $duration && $total_marks) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO exams (title, course_id, created_by, start_time, duration_minutes, total_marks, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
                ");
                $stmt->execute([$exam_name, $course_id, $currentUser['id'], $exam_date . ' ' . $exam_time, $duration, $total_marks]);
                $message = 'Exam created successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Fetch faculty's courses
$stmt = $pdo->prepare("SELECT * FROM courses WHERE faculty_id = ? ORDER BY title");
$stmt->execute([$currentUser['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch faculty's exams
$stmt = $pdo->prepare("
    SELECT e.*, c.title AS course_name
    FROM exams e
    LEFT JOIN courses c ON e.course_id = c.id
    WHERE e.created_by = ?
    ORDER BY e.start_time DESC
    LIMIT 20
");
$stmt->execute([$currentUser['id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Create - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .exam-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .form-section {
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 6px;
            color: rgba(245, 244, 255, 0.8);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-input,
        .form-select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(139, 92, 246, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .submit-btn {
            padding: 12px 24px;
            background: linear-gradient(120deg, #8B5CF6, #D8B4FE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-start;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }

        .exams-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .exams-table th {
            background: rgba(139, 92, 246, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(139, 92, 246, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .exams-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .exams-table tbody tr:hover {
            background: rgba(139, 92, 246, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-scheduled {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .action-btn {
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

        .action-btn:hover {
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
            font-size: 1.2rem;
            font-weight: 700;
            margin: 28px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .exams-table {
                font-size: 0.85rem;
            }

            .exams-table th,
            .exams-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="faculty">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="exam-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Create Exam</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Schedule and configure new exams for your courses</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Exam Creation Form -->
                <div class="form-section">
                    <h2 style="margin: 0 0 20px 0; color: #F5F4FF;">
                        <i class="bi bi-pencil-square"></i> New Exam
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="create_exam">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Exam Name</label>
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    name="exam_name" 
                                    placeholder="e.g., Midterm Exam" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Course</label>
                                <select class="form-select" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Exam Date</label>
                                <input 
                                    type="date" 
                                    class="form-input" 
                                    name="exam_date" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Start Time</label>
                                <input 
                                    type="time" 
                                    class="form-input" 
                                    name="exam_time" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Duration (minutes)</label>
                                <input 
                                    type="number" 
                                    class="form-input" 
                                    name="duration" 
                                    min="30" 
                                    max="300" 
                                    value="60" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Total Marks</label>
                                <input 
                                    type="number" 
                                    class="form-input" 
                                    name="total_marks" 
                                    min="10" 
                                    max="500" 
                                    value="100" 
                                    required
                                >
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="bi bi-check-circle"></i> Create Exam
                        </button>
                    </form>
                </div>

                <!-- Existing Exams -->
                <h2 class="section-title">
                    <i class="bi bi-list-check"></i> My Exams (<?php echo count($exams); ?>)
                </h2>

                <?php if (count($exams) > 0): ?>
                    <table class="exams-table">
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Course</th>
                                <th>Date & Time</th>
                                <th>Duration</th>
                                <th>Marks</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): 
                                $examDate = new DateTime($exam['start_time']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $examDate->format('M d, Y h:i A'); ?></td>
                                    <td><?php echo $exam['duration_minutes']; ?> min</td>
                                    <td><?php echo $exam['total_marks']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($exam['status'] ?? 'scheduled'); ?>">
                                            <?php echo ucfirst($exam['status'] ?? 'scheduled'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="alert('Edit: ' + '<?php echo addslashes($exam['exam_name']); ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(139, 92, 246, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No exams created yet. Create your first exam above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
