<?php
/**
 * Parent Scorecard - Student Performance Report
 * View linked student's grades and performance
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['parent']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch linked students
$stmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.full_name, s.email
    FROM users s
    WHERE s.parent_id = ? AND s.role = 'student'
");
$stmt->execute([$currentUser['id']]);
$linkedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedStudent = intval($_GET['student'] ?? ($linkedStudents[0]['id'] ?? 0));

// Fetch student's exam results
$stmt = $pdo->prepare("
    SELECT er.obtained_marks, er.submitted_at,
           e.title AS exam_name, e.total_marks, e.start_time AS exam_date,
           c.title AS course_name,
           ROUND((er.obtained_marks / e.total_marks) * 100, 2) as percentage
    FROM exam_responses er
    JOIN exams e ON er.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    WHERE er.student_id = ? AND er.status = 'graded'
    ORDER BY er.submitted_at DESC
    LIMIT 10
");
$stmt->execute([$selectedStudent]);
$examResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate GPA/Average
$avgPercentage = count($examResults) > 0 ? array_reduce($examResults, fn($sum, $r) => $sum + $r['percentage'], 0) / count($examResults) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Scorecard - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .parent-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .student-selector {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .student-btn {
            padding: 10px 16px;
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .student-btn.active {
            background: linear-gradient(120deg, #F97316, #FDBA74);
            border-color: transparent;
            color: white;
        }

        .student-btn:hover {
            background: rgba(249, 115, 22, 0.15);
            border-color: rgba(249, 115, 22, 0.5);
        }

        .performance-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .performance-card {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(253, 186, 116, 0.1));
            border: 1px solid rgba(249, 115, 22, 0.3);
            border-radius: 12px;
            padding: 18px;
            text-align: center;
        }

        .performance-value {
            font-size: 2rem;
            font-weight: 700;
            color: #FDBA74;
            margin-bottom: 4px;
        }

        .performance-label {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            text-transform: uppercase;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(249, 115, 22, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .results-table th {
            background: rgba(249, 115, 22, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(249, 115, 22, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .results-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(249, 115, 22, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .results-table tbody tr:hover {
            background: rgba(249, 115, 22, 0.05);
        }

        .grade-badge {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            border: 2px solid;
        }

        .grade-a {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
            border-color: #10B981;
        }

        .grade-b {
            background: rgba(34, 211, 238, 0.2);
            color: #67E8F9;
            border-color: #22D3EE;
        }

        .grade-c {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
            border-color: #FB923C;
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

        .no-data {
            text-align: center;
            padding: 40px;
            background: rgba(249, 115, 22, 0.05);
            border-radius: 12px;
            color: rgba(245, 244, 255, 0.6);
        }

        @media (max-width: 768px) {
            .results-table {
                font-size: 0.85rem;
            }

            .results-table th,
            .results-table td {
                padding: 8px;
            }

            .grade-badge {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="parent">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="parent-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Student Scorecard</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Monitor your child's academic performance</p>
                </div>

                <!-- Student Selector -->
                <?php if (count($linkedStudents) > 0): ?>
                    <div class="student-selector">
                        <?php foreach ($linkedStudents as $student): ?>
                            <a href="?student=<?php echo $student['id']; ?>" 
                               class="student-btn <?php echo $student['id'] == $selectedStudent ? 'active' : ''; ?>">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($student['full_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Performance Overview -->
                <?php if (count($examResults) > 0): ?>
                    <div class="performance-cards">
                        <div class="performance-card">
                            <div class="performance-value"><?php echo number_format($avgPercentage, 1); ?>%</div>
                            <div class="performance-label">Average Score</div>
                        </div>
                        <div class="performance-card">
                            <div class="performance-value"><?php echo count($examResults); ?></div>
                            <div class="performance-label">Exams Taken</div>
                        </div>
                        <div class="performance-card">
                            <div class="performance-value"><?php echo count(array_filter($examResults, fn($r) => $r['percentage'] >= 60)); ?></div>
                            <div class="performance-label">Passed</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Results Table -->
                <h2 class="section-title">
                    <i class="bi bi-file-earmark-text"></i> Exam Results
                </h2>

                <?php if (count($examResults) > 0): ?>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Exam Name</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examResults as $result): 
                                $grade = $result['percentage'] >= 80 ? 'A' : ($result['percentage'] >= 70 ? 'B' : 'C');
                                $gradeClass = 'grade-' . strtolower($grade);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['exam_name'] ?? 'Exam'); ?></td>
                                    <td><?php echo $result['obtained_marks']; ?>/<?php echo $result['total_marks']; ?></td>
                                    <td><strong><?php echo number_format((float) $result['percentage'], 1); ?>%</strong></td>
                                    <td>
                                        <div class="grade-badge <?php echo $gradeClass; ?>">
                                            <?php echo $grade; ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($result['exam_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No exam results available yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
