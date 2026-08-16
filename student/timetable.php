<?php
/**
 * Timetable - Class Schedule
 * View course schedule and exam dates
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Get student's enrolled courses with schedule
$stmt = $pdo->prepare("
    SELECT c.*, c.title AS course_name, f.full_name AS faculty_name,
           GROUP_CONCAT(DISTINCT
               CONCAT(e.start_time, '|', e.title)
           ) AS upcoming_exams
    FROM courses c
    LEFT JOIN users f ON c.faculty_id = f.id
    LEFT JOIN exams e ON c.id = e.course_id AND e.start_time >= CURDATE()
    GROUP BY c.id
    ORDER BY c.title ASC
");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Weekly schedule data
$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$timeSlots = ['9:00 AM', '10:00 AM', '11:00 AM', '1:00 PM', '2:00 PM', '3:00 PM'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .timetable-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .timetable-grid {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .timetable-head {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
        }

        .timetable-cell {
            padding: 12px;
            text-align: center;
            border-right: 1px solid rgba(99, 102, 241, 0.1);
            font-weight: 600;
            color: #F5F4FF;
        }

        .timetable-cell:last-child {
            border-right: none;
        }

        .timetable-time {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.6);
        }

        .timetable-body {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
        }

        .time-slot {
            background: rgba(99, 102, 241, 0.05);
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            border-right: 1px solid rgba(99, 102, 241, 0.1);
            padding: 8px;
            text-align: center;
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.6);
            font-weight: 500;
        }

        .schedule-cell {
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            border-right: 1px solid rgba(99, 102, 241, 0.1);
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
        }

        .schedule-cell:last-child {
            border-right: none;
        }

        .schedule-item {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(34, 211, 238, 0.2));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 6px;
            padding: 6px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #F5F4FF;
            text-align: center;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .courses-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .course-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 18px;
            transition: all 0.3s ease;
        }

        .course-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.2);
        }

        .course-code {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.5);
            margin-bottom: 4px;
        }

        .course-name {
            font-weight: 700;
            color: #F5F4FF;
            font-size: 1rem;
            margin-bottom: 8px;
        }

        .course-faculty {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.7);
            margin-bottom: 10px;
            padding: 8px;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 6px;
        }

        .course-faculty i {
            margin-right: 4px;
            color: #22D3EE;
        }

        .exams-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(34, 211, 238, 0.2);
            color: #67E8F9;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 24px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 1024px) {
            .timetable-grid {
                overflow-x: auto;
            }

            .courses-list {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .timetable-grid {
                font-size: 0.8rem;
            }

            .timetable-cell {
                padding: 8px;
            }

            .schedule-item {
                font-size: 0.7rem;
            }

            .courses-list {
                grid-template-columns: 1fr;
            }
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
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="timetable-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Timetable</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">View your class schedule and exam dates</p>
                </div>

                <!-- Weekly Timetable -->
                <h2 class="section-title">
                    <i class="bi bi-calendar2-week"></i> Weekly Schedule
                </h2>

                <div class="timetable-grid">
                    <div class="timetable-head">
                        <div class="timetable-cell"></div>
                        <?php foreach ($weekDays as $day): ?>
                            <div class="timetable-cell"><?php echo $day; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="timetable-body">
                        <?php foreach ($timeSlots as $time): ?>
                            <div class="time-slot"><?php echo $time; ?></div>
                            <?php foreach ($weekDays as $day): ?>
                                <div class="schedule-cell">
                                    <?php 
                                        // Random course assignment for demo
                                        if (rand(0, 1) && count($courses) > 0) {
                                            $randomCourse = $courses[array_rand($courses)];
                                            echo '<div class="schedule-item">' . htmlspecialchars(substr($randomCourse['course_name'], 0, 15)) . '</div>';
                                        }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Courses List -->
                <h2 class="section-title">
                    <i class="bi bi-book"></i> Enrolled Courses (<?php echo count($courses); ?>)
                </h2>

                <?php if (count($courses) > 0): ?>
                    <div class="courses-list">
                        <?php foreach ($courses as $course): 
                            $exams = $course['upcoming_exams'] ? explode(',', $course['upcoming_exams']) : [];
                            $examCount = count(array_filter($exams));
                        ?>
                            <div class="course-card">
                                <div class="course-code"><?php echo htmlspecialchars($course['code'] ?? 'CS101'); ?></div>
                                <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                <div class="course-faculty">
                                    <i class="bi bi-person"></i>
                                    <?php echo htmlspecialchars($course['faculty_name'] ?? 'Faculty Name'); ?>
                                </div>
                                <?php if ($examCount > 0): ?>
                                    <div style="margin-top: 8px;">
                                        <span class="exams-badge">
                                            <i class="bi bi-pencil-square"></i> <?php echo $examCount; ?> Upcoming Exam(s)
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(99, 102, 241, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No courses enrolled yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
