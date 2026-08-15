<?php
/**
 * Parent - Alerts & Notifications
 * View student alerts and notifications
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
    SELECT DISTINCT s.id, s.name, s.email
    FROM users s
    WHERE s.parent_id = ? AND s.role = 'student'
");
$stmt->execute([$currentUser['id']]);
$linkedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedStudent = intval($_GET['student'] ?? ($linkedStudents[0]['id'] ?? 0));

// Fetch alerts for selected student
$alerts = [];
if ($selectedStudent) {
    // Low attendance alerts
    $stmt = $pdo->prepare("
        SELECT 
            'attendance' as type,
            CONCAT('Low attendance in ', c.course_name) as message,
            CONCAT(ROUND(COUNT(CASE WHEN a.status = 'present' THEN 1 END) / COUNT(*) * 100), '% attendance') as details,
            NOW() as created_at,
            'warning' as severity
        FROM attendance a
        JOIN courses c ON a.course_id = c.id
        WHERE a.student_id = ?
        GROUP BY c.id
        HAVING (COUNT(CASE WHEN a.status = 'present' THEN 1 END) / COUNT(*)) < 0.75
    ");
    $stmt->execute([$selectedStudent]);
    $alerts = array_merge($alerts, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Pending payment alerts
    $stmt = $pdo->prepare("
        SELECT 
            'payment' as type,
            'Pending fee payment' as message,
            CONCAT('₹', amount) as details,
            due_date as created_at,
            CASE WHEN due_date < CURDATE() THEN 'error' ELSE 'warning' END as severity
        FROM fee_invoices
        WHERE student_id = ? AND status = 'pending'
        ORDER BY due_date ASC
    ");
    $stmt->execute([$selectedStudent]);
    $alerts = array_merge($alerts, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Poor performance alerts
    $stmt = $pdo->prepare("
        SELECT 
            'performance' as type,
            CONCAT('Low performance in exam') as message,
            CONCAT(ROUND((e.obtained_marks / e.total_marks * 100)), '%') as details,
            e.exam_date as created_at,
            CASE WHEN (e.obtained_marks / e.total_marks) < 0.4 THEN 'error' ELSE 'warning' END as severity
        FROM exams e
        WHERE e.student_id = ? AND e.obtained_marks / e.total_marks < 0.6
        ORDER BY e.exam_date DESC
    ");
    $stmt->execute([$selectedStudent]);
    $alerts = array_merge($alerts, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Sort by date
usort($alerts, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts & Notifications - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .alerts-container {
            max-width: 1000px;
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
        }

        .alert-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(249, 115, 22, 0.2);
            border-left: 4px solid;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .alert-card:hover {
            background: rgba(249, 115, 22, 0.05);
        }

        .alert-card.error {
            border-left-color: #EF4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .alert-card.warning {
            border-left-color: #F59E0B;
            background: rgba(245, 158, 11, 0.05);
        }

        .alert-card.success {
            border-left-color: #10B981;
            background: rgba(16, 185, 129, 0.05);
        }

        .alert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .alert-icon {
            font-size: 1.2rem;
            margin-right: 10px;
        }

        .alert-title {
            font-weight: 600;
            color: #F5F4FF;
            flex: 1;
        }

        .alert-time {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.5);
        }

        .alert-message {
            color: rgba(245, 244, 255, 0.7);
            margin-bottom: 8px;
        }

        .alert-details {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 6px;
            display: inline-block;
        }

        .severity-error {
            color: #FCA5A5;
        }

        .severity-warning {
            color: #FDBA74;
        }

        .severity-success {
            color: #6EE7B7;
        }

        .dismiss-btn {
            padding: 6px 12px;
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.3);
            color: #FDBA74;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dismiss-btn:hover {
            background: rgba(249, 115, 22, 0.2);
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

        .no-alerts {
            text-align: center;
            padding: 40px;
            background: rgba(249, 115, 22, 0.05);
            border-radius: 12px;
            color: rgba(245, 244, 255, 0.6);
        }

        @media (max-width: 768px) {
            .alert-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .alert-time {
                margin-top: 8px;
            }
        }
    </style>
</head>
<body data-role="parent">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="alerts-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Alerts & Notifications</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Important updates about your child's academics</p>
                </div>

                <!-- Student Selector -->
                <?php if (count($linkedStudents) > 0): ?>
                    <div class="student-selector">
                        <?php foreach ($linkedStudents as $student): ?>
                            <a href="?student=<?php echo $student['id']; ?>" 
                               class="student-btn <?php echo $student['id'] == $selectedStudent ? 'active' : ''; ?>">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($student['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Alerts List -->
                <h2 class="section-title">
                    <i class="bi bi-bell"></i> Recent Alerts (<?php echo count($alerts); ?>)
                </h2>

                <?php if (count($alerts) > 0): ?>
                    <div>
                        <?php foreach ($alerts as $alert): 
                            $alertTime = new DateTime($alert['created_at']);
                            $severity = $alert['severity'] ?? 'warning';
                            $icon = $severity === 'error' ? 'exclamation-triangle' : ($severity === 'success' ? 'check-circle' : 'info-circle');
                        ?>
                            <div class="alert-card <?php echo $severity; ?>">
                                <div class="alert-header">
                                    <div style="display: flex; align-items: center; flex: 1;">
                                        <i class="bi bi-<?php echo $icon; ?> alert-icon severity-<?php echo $severity; ?>"></i>
                                        <div class="alert-title"><?php echo htmlspecialchars($alert['message']); ?></div>
                                    </div>
                                    <div class="alert-time"><?php echo $alertTime->format('M d, h:i A'); ?></div>
                                </div>
                                <div class="alert-message">
                                    <?php 
                                        $typeIcons = [
                                            'attendance' => '📚',
                                            'payment' => '💳',
                                            'performance' => '📊'
                                        ];
                                        echo $typeIcons[$alert['type']] ?? '📌';
                                    ?>
                                    <?php echo ucfirst($alert['type']); ?> Alert
                                </div>
                                <div>
                                    <span class="alert-details">
                                        <?php echo htmlspecialchars($alert['details']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-alerts">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No alerts at this moment. Everything looks great!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
