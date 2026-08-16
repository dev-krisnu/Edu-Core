<?php
/**
 * Faculty Dashboard
 * Course management, grading, and student analytics
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['faculty']);

$pdo = getDbConnection();
$currentUser = getCurrentUser();

try {
    $statsQueries = [
        'total_courses' => 'SELECT COUNT(*) as count FROM courses WHERE faculty_id = ?',
        'total_students' => 'SELECT COUNT(*) as count FROM users WHERE role = "student"',
        'pending_grading' => 'SELECT COUNT(*) as count FROM exams WHERE status = "completed" AND course_id IN (SELECT id FROM courses WHERE faculty_id = ?)',
        'recent_courses' => 'SELECT * FROM courses WHERE faculty_id = ? ORDER BY id DESC LIMIT 5'
    ];

    $stats = [];
    $userId = $currentUser['id'];

    $coursesStmt = $pdo->prepare($statsQueries['total_courses']);
    $coursesStmt->execute([$userId]);
    $stats['total_courses'] = $coursesStmt->fetch()['count'] ?? 0;

    $studentsStmt = $pdo->query($statsQueries['total_students']);
    $stats['total_students'] = $studentsStmt->fetch()['count'] ?? 0;

    $gradingStmt = $pdo->prepare($statsQueries['pending_grading']);
    $gradingStmt->execute([$userId]);
    $stats['pending_grading'] = $gradingStmt->fetch()['count'] ?? 0;

    $recentStmt = $pdo->prepare($statsQueries['recent_courses']);
    $recentStmt->execute([$userId]);
    $stats['recent_courses'] = $recentStmt->fetchAll();

} catch (Exception $e) {
    error_log('[Faculty Dashboard] Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .faculty-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card-faculty {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(212, 180, 254, 0.05));
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .stat-card-faculty:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.25);
            border-color: #8B5CF6;
        }

        .stat-number-faculty {
            font-size: 2.2rem;
            font-weight: 800;
            color: #D8B4FE;
            margin-bottom: 8px;
        }

        .stat-label-faculty {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .faculty-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }

        .faculty-action-btn {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(139, 92, 246, 0.08));
            border: 1px solid rgba(139, 92, 246, 0.4);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #D8B4FE;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .faculty-action-btn:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(139, 92, 246, 0.15));
            border-color: #8B5CF6;
            transform: translateY(-2px);
        }

        .action-icon-faculty {
            font-size: 1.5rem;
            color: #8B5CF6;
        }

        .course-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            transition: all 0.3s ease;
        }

        .course-card:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: #8B5CF6;
            transform: translateX(4px);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .course-name {
            font-weight: 700;
            color: #F5F4FF;
            font-size: 1rem;
        }

        .course-code {
            font-family: monospace;
            font-size: 0.75rem;
            color: #D8B4FE;
            background: rgba(139, 92, 246, 0.2);
            padding: 3px 8px;
            border-radius: 4px;
        }

        .course-stats {
            display: flex;
            gap: 20px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            backdrop-filter: blur(12px);
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
            <div class="container">
                <!-- Header -->
                <div style="margin-bottom: 40px;">
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0 0 8px 0;">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                    <h1 class="h-display" style="margin: 0;">Faculty Dashboard</h1>
                </div>

                <!-- Statistics -->
                <div class="faculty-stats">
                    <div class="stat-card-faculty">
                        <div class="stat-number-faculty"><?php echo $stats['total_courses'] ?? 0; ?></div>
                        <div class="stat-label-faculty">Active Courses</div>
                    </div>
                    <div class="stat-card-faculty">
                        <div class="stat-number-faculty"><?php echo $stats['total_students'] ?? 0; ?></div>
                        <div class="stat-label-faculty">Enrolled Students</div>
                    </div>
                    <div class="stat-card-faculty">
                        <div class="stat-number-faculty"><?php echo $stats['pending_grading'] ?? 0; ?></div>
                        <div class="stat-label-faculty">Pending Grading</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-bottom: 40px;">
                    <h2 class="section-title" style="margin-bottom: 20px;">Quick Actions</h2>
                    <div class="faculty-actions">
                        <a href="./examCreate.php" class="faculty-action-btn">
                            <i class="bi bi-plus-circle action-icon-faculty"></i>
                            <span>Create Exam</span>
                        </a>
                        <a href="./examGrading.php" class="faculty-action-btn">
                            <i class="bi bi-pencil-square action-icon-faculty"></i>
                            <span>Grade Exams</span>
                        </a>
                        <a href="./questionBank.php" class="faculty-action-btn">
                            <i class="bi bi-question-circle action-icon-faculty"></i>
                            <span>Question Bank</span>
                        </a>
                        <a href="./aiQuestionGeneration.php" class="faculty-action-btn">
                            <i class="bi bi-stars action-icon-faculty"></i>
                            <span>AI Questions</span>
                        </a>
                        <a href="./studentAnalytics.php" class="faculty-action-btn">
                            <i class="bi bi-graph-up action-icon-faculty"></i>
                            <span>Analytics</span>
                        </a>
                        <a href="./resourceUpload.php" class="faculty-action-btn">
                            <i class="bi bi-cloud-upload action-icon-faculty"></i>
                            <span>Upload Resources</span>
                        </a>
                    </div>
                </div>

                <!-- My Courses -->
                <div class="section-box">
                    <h2 class="section-title" style="margin-bottom: 20px;">My Courses</h2>
                    <div>
                        <?php if (!empty($stats['recent_courses'])): ?>
                            <?php foreach ($stats['recent_courses'] as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <div>
                                            <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                            <div class="course-code"><?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="course-stats">
                                        <div class="stat-item">
                                            <i class="bi bi-people"></i>
                                            <span>Students Enrolled</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="bi bi-pencil"></i>
                                            <span>Assignments Active</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: rgba(255, 255, 255, 0.5);">No courses assigned yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/educore.js"></script>
</body>
</html>
