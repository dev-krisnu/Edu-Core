<?php
/**
 * Exam Results - Student Grade Report
 * View exam results, scores, and performance analytics
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch exam results
$stmt = $pdo->prepare("
    SELECT e.*, c.course_name, e.total_marks, e.obtained_marks,
           ROUND((e.obtained_marks / e.total_marks) * 100, 2) as percentage,
           CASE 
               WHEN (e.obtained_marks / e.total_marks) >= 0.9 THEN 'A+' 
               WHEN (e.obtained_marks / e.total_marks) >= 0.8 THEN 'A'
               WHEN (e.obtained_marks / e.total_marks) >= 0.7 THEN 'B'
               WHEN (e.obtained_marks / e.total_marks) >= 0.6 THEN 'C'
               ELSE 'D' 
           END as grade
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ? AND e.status = 'completed'
    ORDER BY e.exam_date DESC
");
$stmt->execute([$currentUser['id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalExams = count($results);
$avgPercentage = $totalExams > 0 ? array_reduce($results, fn($sum, $r) => $sum + $r['percentage'], 0) / $totalExams : 0;
$passedExams = count(array_filter($results, fn($r) => $r['percentage'] >= 60));
$failedExams = $totalExams - $passedExams;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .results-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: #6366F1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(245, 244, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .results-table thead {
            background: rgba(99, 102, 241, 0.1);
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
        }

        .results-table th {
            padding: 14px;
            text-align: left;
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .results-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            color: rgba(245, 244, 255, 0.9);
        }

        .results-table tbody tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .grade-badge {
            display: inline-block;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            text-align: center;
        }

        .grade-a {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
            border: 2px solid #10B981;
        }

        .grade-b {
            background: rgba(34, 211, 238, 0.2);
            color: #67E8F9;
            border: 2px solid #22D3EE;
        }

        .grade-c {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
            border: 2px solid #FB923C;
        }

        .grade-d {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
            border: 2px solid #EF4444;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366F1, #22D3EE);
            transition: width 0.3s ease;
        }

        .result-item {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: 60px 1fr auto;
            gap: 16px;
            align-items: center;
        }

        .result-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .result-title {
            font-weight: 600;
            color: #F5F4FF;
            font-size: 0.95rem;
        }

        .result-course {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.6);
        }

        .result-score {
            text-align: right;
        }

        .result-percentage {
            font-size: 1.3rem;
            font-weight: 700;
            color: #6366F1;
        }

        .result-date {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.5);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            background: rgba(99, 102, 241, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            color: rgba(245, 244, 255, 0.6);
        }

        @media (max-width: 768px) {
            .result-item {
                grid-template-columns: 1fr;
            }

            .result-item > div:last-child {
                grid-column: 1;
            }
        }
    </style>
</head>
<body data-role="student">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="results-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Exam Results</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">View your exam scores and performance analytics</p>
                </div>

                <!-- Statistics -->
                <?php if ($totalExams > 0): ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $totalExams; ?></div>
                            <div class="stat-label">Total Exams</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #22D3EE;"><?php echo number_format($avgPercentage, 1); ?>%</div>
                            <div class="stat-label">Average Score</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #10B981;"><?php echo $passedExams; ?></div>
                            <div class="stat-label">Passed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #EF4444;"><?php echo $failedExams; ?></div>
                            <div class="stat-label">Failed</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Results -->
                <?php if (count($results) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($results as $result): 
                            $gradeClass = 'grade-' . strtolower($result['grade']);
                            $percentage = $result['percentage'];
                        ?>
                            <div class="result-item">
                                <div class="grade-badge <?php echo $gradeClass; ?>">
                                    <?php echo $result['grade']; ?>
                                </div>
                                <div class="result-details">
                                    <div class="result-title"><?php echo htmlspecialchars($result['course_name']); ?></div>
                                    <div class="result-course">Exam: <?php echo date('M d, Y', strtotime($result['exam_date'])); ?></div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                    </div>
                                </div>
                                <div class="result-score">
                                    <div class="result-percentage"><?php echo number_format($percentage, 1); ?>%</div>
                                    <div class="result-date"><?php echo $result['obtained_marks']; ?>/<?php echo $result['total_marks']; ?> marks</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="bi bi-file-earmark-text" style="font-size: 2.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>No exam results yet. Complete exams to see your scores here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
