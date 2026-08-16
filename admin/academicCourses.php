<?php
/**
 * Admin - Academic Courses
 * Manage courses, faculty assignments, and course details
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['admin']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle course creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'add_course') {
        $course_name = trim($_POST['course_name']);
        $course_code = trim($_POST['course_code']);
        $faculty_id = intval($_POST['faculty_id']);
        $credits = intval($_POST['credits']);
        $semester = intval($_POST['semester']);

        if ($course_name && $course_code && $faculty_id && $credits && $semester) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO courses (course_name, course_code, faculty_id, credits, semester)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$course_name, $course_code, $faculty_id, $credits, $semester]);
                $message = 'Course added successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Fetch all courses with faculty info
$stmt = $pdo->prepare("
    SELECT c.*, u.name as faculty_name,
           COUNT(DISTINCT ce.student_id) as enrolled_students
    FROM courses c
    LEFT JOIN users u ON c.faculty_id = u.id
    LEFT JOIN course_enrollments ce ON c.id = ce.course_id
    GROUP BY c.id
    ORDER BY c.semester, c.course_code
");
$stmt->execute([]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch faculty for assignment
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'faculty' ORDER BY name");
$faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Courses - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .courses-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .form-section {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            border: 1px solid rgba(239, 68, 68, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .submit-btn {
            padding: 12px 24px;
            background: linear-gradient(120deg, #EF4444, #FCA5A5);
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
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .courses-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .courses-table th {
            background: rgba(239, 68, 68, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(239, 68, 68, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .courses-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(239, 68, 68, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .courses-table tbody tr:hover {
            background: rgba(239, 68, 68, 0.05);
        }

        .semester-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #EF4444, #FCA5A5);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 6px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
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
            .courses-table {
                font-size: 0.85rem;
            }

            .courses-table th,
            .courses-table td {
                padding: 8px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="admin">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="courses-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Academic Courses</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Create and manage academic courses</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Add Course Form -->
                <div class="form-section">
                    <h2 style="margin: 0 0 20px 0; color: #F5F4FF;">
                        <i class="bi bi-plus-circle"></i> Add New Course
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_course">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Course Name</label>
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    name="course_name" 
                                    placeholder="e.g., Data Structures" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Course Code</label>
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    name="course_code" 
                                    placeholder="e.g., CS201" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Assign Faculty</label>
                                <select class="form-select" name="faculty_id" required>
                                    <option value="">Select Faculty</option>
                                    <?php foreach ($faculty as $f): ?>
                                        <option value="<?php echo $f['id']; ?>">
                                            <?php echo htmlspecialchars($f['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Credits</label>
                                <input 
                                    type="number" 
                                    class="form-input" 
                                    name="credits" 
                                    min="1" 
                                    max="10" 
                                    value="3" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Semester</label>
                                <select class="form-select" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                    <option value="3">Semester 3</option>
                                    <option value="4">Semester 4</option>
                                    <option value="5">Semester 5</option>
                                    <option value="6">Semester 6</option>
                                    <option value="7">Semester 7</option>
                                    <option value="8">Semester 8</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="bi bi-check-circle"></i> Add Course
                        </button>
                    </form>
                </div>

                <!-- Courses List -->
                <h2 class="section-title">
                    <i class="bi bi-list-check"></i> All Courses (<?php echo count($courses); ?>)
                </h2>

                <?php if (count($courses) > 0): ?>
                    <table class="courses-table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Faculty</th>
                                <th>Semester</th>
                                <th>Credits</th>
                                <th>Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['faculty_name'] ?? 'Unassigned'); ?></td>
                                    <td>
                                        <span class="semester-badge">Sem <?php echo $course['semester']; ?></span>
                                    </td>
                                    <td><?php echo $course['credits']; ?></td>
                                    <td><?php echo $course['enrolled_students']; ?> students</td>
                                    <td>
                                        <button class="action-btn" onclick="alert('Edit: ' + '<?php echo addslashes($course['course_code']); ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="action-btn" onclick="alert('Delete: ' + '<?php echo addslashes($course['course_code']); ?>')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(239, 68, 68, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No courses created yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
