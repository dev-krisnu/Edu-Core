<?php
/**
 * Student Dashboard
 * Main landing page for students with overview, courses, exams, and announcements
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$pdo = getDbConnection();
$currentUser = getCurrentUser();

// Get student stats
try {
    // Get enrolled courses
    $coursesStmt = $pdo->prepare('
        SELECT COUNT(*) as total_courses FROM courses
    ');
    $coursesStmt->execute();
    $courseStats = $coursesStmt->fetch();

    // Get upcoming exams
    $examsStmt = $pdo->prepare('
        SELECT e.*, c.title AS course_title 
        FROM exams e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.start_time >= CURDATE() 
        ORDER BY e.start_time ASC 
        LIMIT 5
    ');
    $examsStmt->execute();
    $upcomingExams = $examsStmt->fetchAll();

    $noticesStmt = $pdo->prepare('
        SELECT * FROM notices 
        WHERE is_public = 1 
        ORDER BY created_at DESC 
        LIMIT 5
    ');
    $noticesStmt->execute();
    $recentNotices = $noticesStmt->fetchAll();

    // Get pending assignments
    $assignmentsStmt = $pdo->prepare('
        SELECT COUNT(*) as pending 
        FROM exam_questions
    ');
    $assignmentsStmt->execute();
    $assignmentStats = $assignmentsStmt->fetch();

} catch (Exception $e) {
    error_log('[Dashboard] DB Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(34, 211, 238, 0.05));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.25);
            border-color: rgba(99, 102, 241, 0.6);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #6366F1, #22D3EE);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #F5F4FF;
            margin: 8px 0;
        }

        .stat-label {
            font-size: 0.875rem;
            color: rgba(245, 244, 255, 0.7);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #F5F4FF;
            margin: 0;
        }

        .view-all-link {
            color: #67E8F9;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .view-all-link:hover {
            color: #22D3EE;
        }

        .exam-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .exam-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateX(4px);
        }

        .exam-info {
            flex: 1;
        }

        .exam-name {
            font-weight: 600;
            color: #F5F4FF;
            margin-bottom: 4px;
        }

        .exam-date {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
        }

        .exam-badge {
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .notice-item {
            background: rgba(255, 255, 255, 0.03);
            border-left: 3px solid #6366F1;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .notice-item:hover {
            background: rgba(99, 102, 241, 0.1);
            padding-left: 20px;
        }

        .notice-title {
            font-weight: 600;
            color: #F5F4FF;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }

        .notice-date {
            font-size: 0.75rem;
            color: rgba(245, 244, 255, 0.5);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }

        .action-btn {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.4);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #F5F4FF;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.35), rgba(34, 211, 238, 0.2));
            border-color: #6366F1;
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 1.5rem;
            color: #22D3EE;
        }
    </style>
</head>
<body class="portal-page" data-role="student">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="container">
                <!-- Header -->
                <div style="margin-bottom: 40px;">
                    <p style="color: rgba(245, 244, 255, 0.6); margin: 0 0 8px 0;">Welcome back!</p>
                    <h1 class="h-display" style="margin: 0;">Dashboard</h1>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="./exam.php" class="action-btn">
                        <i class="bi bi-pencil-square action-icon"></i>
                        <span>Take Exam</span>
                    </a>
                    <a href="./librarySearch.php" class="action-btn">
                        <i class="bi bi-book action-icon"></i>
                        <span>Library</span>
                    </a>
                    <a href="./feePayment.php" class="action-btn">
                        <i class="bi bi-credit-card action-icon"></i>
                        <span>Pay Fees</span>
                    </a>
                    <a href="./helpDesk.php" class="action-btn">
                        <i class="bi bi-chat-left-text action-icon"></i>
                        <span>Help Desk</span>
                    </a>
                </div>

                <!-- Stats Grid -->
                <div class="dashboard-grid">
                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="bi bi-book-half"></i>
                        </div>
                        <div class="stat-number"><?php echo $courseStats['total_courses'] ?? 0; ?></div>
                        <div class="stat-label">Enrolled Courses</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-number"><?php echo count($upcomingExams); ?></div>
                        <div class="stat-label">Upcoming Exams</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <div class="stat-number"><?php echo $assignmentStats['pending'] ?? 0; ?></div>
                        <div class="stat-label">New Resources</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="bi bi-percent"></i>
                        </div>
                        <div class="stat-number">4.2</div>
                        <div class="stat-label">CGPA</div>
                    </div>
                </div>

                <!-- Upcoming Exams -->
                <section style="margin-bottom: 40px;">
                    <div class="section-header">
                        <h2 class="section-title">Upcoming Exams</h2>
                        <a href="./exam.php" class="view-all-link">View All →</a>
                    </div>
                    <div style="display: grid; gap: 12px;">
                        <?php if (!empty($upcomingExams)): ?>
                            <?php foreach ($upcomingExams as $exam): ?>
                                <div class="exam-card">
                                    <div class="exam-info">
                                        <div class="exam-name"><?php echo htmlspecialchars($exam['course_title'] ?? $exam['title'] ?? 'Exam'); ?></div>
                                        <div class="exam-date">
                                            <i class="bi bi-calendar3"></i> 
                                            <?php echo date('M d, Y \a\t H:i', strtotime($exam['start_time'] ?? 'now')); ?>
                                        </div>
                                    </div>
                                    <div class="exam-badge">Start Exam</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: rgba(245, 244, 255, 0.5);">No upcoming exams scheduled</p>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Recent Notices -->
                <section style="margin-bottom: 40px;">
                    <div class="section-header">
                        <h2 class="section-title">Recent Notices</h2>
                        <a href="./noticeBoard.php" class="view-all-link">View All →</a>
                    </div>
                    <div>
                        <?php if (!empty($recentNotices)): ?>
                            <?php foreach ($recentNotices as $notice): ?>
                                <div class="notice-item">
                                    <div class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></div>
                                    <div class="notice-date">
                                        <?php echo date('M d, Y', strtotime($notice['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: rgba(245, 244, 255, 0.5);">No notices available</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="../assets/js/educore.js"></script>
</body>
</html>
