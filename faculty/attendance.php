<?php
/**
 * Faculty - Attendance Marking
 * Mark and track student attendance
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

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'save_attendance') {
        $date = trim($_POST['attendance_date']);
        $courseId = intval($_POST['course_id']);
        $attendance = $_POST['attendance'] ?? [];

        try {
            foreach ($attendance as $studentId => $status) {
                $stmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, course_id, attendance_date, status)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = ?
                ");
                $stmt->execute([$studentId, $courseId, $date, $status, $status]);
            }
            $message = 'Attendance saved successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Fetch faculty courses
$stmt = $pdo->prepare("SELECT * FROM courses WHERE faculty_id = ? ORDER BY course_name");
$stmt->execute([$currentUser['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected course and date
$selectedCourse = intval($_GET['course'] ?? ($courses[0]['id'] ?? 0));
$selectedDate = trim($_GET['date'] ?? date('Y-m-d'));

// Fetch enrolled students and their attendance
if ($selectedCourse) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.name, u.email,
               COALESCE(a.status, '') as attendance_status
        FROM users u
        JOIN course_enrollments ce ON u.id = ce.student_id
        LEFT JOIN attendance a ON u.id = a.student_id 
            AND a.course_id = ? 
            AND a.attendance_date = ?
        WHERE ce.course_id = ? AND u.role = 'student'
        ORDER BY u.name
    ");
    $stmt->execute([$selectedCourse, $selectedDate, $selectedCourse]);
    $enrolledStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $enrolledStudents = [];
}

// Attendance statistics
$stmt = $pdo->prepare("
    SELECT 
        student_id,
        COUNT(*) as total_classes,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count
    FROM attendance
    WHERE course_id = ?
    GROUP BY student_id
    ORDER BY student_id
");
$stmt->execute([$selectedCourse]);
$stats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats[$row['student_id']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .attendance-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .filter-section {
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .filter-input {
            padding: 8px;
            background: rgba(139, 92, 246, 0.05);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 6px;
            color: #F5F4FF;
            font-family: inherit;
        }

        .filter-input:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .students-table th {
            background: rgba(139, 92, 246, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(139, 92, 246, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .students-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .students-table tbody tr:hover {
            background: rgba(139, 92, 246, 0.05);
        }

        .attendance-radio {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .radio-btn {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #8B5CF6;
        }

        .attendance-percentage {
            font-weight: 600;
            color: #D8B4FE;
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
            margin-top: 16px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
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

        @media (max-width: 768px) {
            .students-table {
                font-size: 0.85rem;
            }

            .students-table th,
            .students-table td {
                padding: 8px;
            }

            .filter-section {
                grid-template-columns: 1fr;
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
            <div class="attendance-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Attendance Marking</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Mark and track student attendance</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <form method="GET" class="filter-section">
                    <select name="course" class="filter-input" onchange="this.form.submit()">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $course['id'] == $selectedCourse ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" class="filter-input" value="<?php echo htmlspecialchars($selectedDate); ?>" onchange="this.form.submit()">
                </form>

                <?php if ($selectedCourse && count($enrolledStudents) > 0): ?>
                    <h2 class="section-title">
                        <i class="bi bi-check-circle"></i> Mark Attendance (<?php echo count($enrolledStudents); ?> students)
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_attendance">
                        <input type="hidden" name="course_id" value="<?php echo $selectedCourse; ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo $selectedDate; ?>">

                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>Roll No.</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Attendance</th>
                                    <th>Total</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrolledStudents as $student): 
                                    $stat = $stats[$student['id']] ?? null;
                                    $percentage = $stat ? round(($stat['present_count'] / $stat['total_classes']) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo $student['id']; ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>
                                            <div class="attendance-radio">
                                                <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                       value="present" class="radio-btn"
                                                       <?php echo $student['attendance_status'] === 'present' ? 'checked' : ''; ?>>
                                                <label style="margin: 0; cursor: pointer; color: #6EE7B7;">Present</label>
                                                
                                                <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                       value="absent" class="radio-btn"
                                                       <?php echo $student['attendance_status'] === 'absent' ? 'checked' : ''; ?>
                                                       style="margin-left: 12px;">
                                                <label style="margin: 0; cursor: pointer; color: #FCA5A5;">Absent</label>
                                            </div>
                                        </td>
                                        <td><?php echo $stat['total_classes'] ?? 0; ?></td>
                                        <td>
                                            <span class="attendance-percentage">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <button type="submit" class="submit-btn">
                            <i class="bi bi-check-circle"></i> Save Attendance
                        </button>
                    </form>

                <?php elseif ($selectedCourse): ?>
                    <div style="text-align: center; padding: 40px; background: rgba(139, 92, 246, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No enrolled students in this course.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
