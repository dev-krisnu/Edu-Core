<?php
/**
 * Parent Dashboard
 * Student monitoring and parent notifications
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['parent']);

$pdo = getDbConnection();
$currentUser = getCurrentUser();

try {
    $childrenStmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE parent_id = ? AND role = "student"');
    $childrenStmt->execute([$currentUser['id']]);
    $children = $childrenStmt->fetchAll() ?: [];
    
    if (!empty($children)) {
        $childId = $children[0]['id'];
        $attendanceStmt = $pdo->prepare("SELECT COUNT(*) as attended FROM attendance WHERE student_id = ? AND status IN ('present', 'late')");
        $attendanceStmt->execute([$childId]);
        $attendance = $attendanceStmt->fetch()['attended'] ?? 0;
    } else {
        $attendance = 0;
    }
} catch (Exception $e) {
    error_log('[Parent Dashboard] Error: ' . $e->getMessage());
    $children = [];
    $attendance = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .parent-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-parent {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(253, 186, 116, 0.05));
            border: 1px solid rgba(249, 115, 22, 0.3);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .stat-parent:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.25);
        }

        .stat-icon-parent {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, #F97316, #FDBA74);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .stat-number-parent {
            font-size: 2rem;
            font-weight: 800;
            color: #FDBA74;
            margin-bottom: 4px;
        }

        .stat-label-parent {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .parent-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }

        .parent-action {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(249, 115, 22, 0.08));
            border: 1px solid rgba(249, 115, 22, 0.4);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #FDBA74;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .parent-action:hover {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.3), rgba(249, 115, 22, 0.15));
            border-color: #F97316;
            transform: translateY(-2px);
        }

        .child-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(249, 115, 22, 0.3);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            transition: all 0.3s ease;
        }

        .child-card:hover {
            background: rgba(249, 115, 22, 0.1);
            border-color: #F97316;
        }

        .child-name {
            font-weight: 700;
            color: #F5F4FF;
            margin-bottom: 8px;
        }

        .child-info {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .section-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(12px);
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
            <div class="container">
                <!-- Header -->
                <div style="margin-bottom: 40px;">
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0 0 8px 0;">Welcome back!</p>
                    <h1 class="h-display" style="margin: 0;">Parent Portal</h1>
                </div>

                <!-- Stats -->
                <div class="parent-stats">
                    <div class="stat-parent">
                        <div class="stat-icon-parent"><i class="bi bi-people"></i></div>
                        <div class="stat-number-parent"><?php echo count($children); ?></div>
                        <div class="stat-label-parent">Linked Students</div>
                    </div>
                    <div class="stat-parent">
                        <div class="stat-icon-parent"><i class="bi bi-calendar-check"></i></div>
                        <div class="stat-number-parent"><?php echo $attendance; ?></div>
                        <div class="stat-label-parent">Exams Completed</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-bottom: 40px;">
                    <h2 class="section-title" style="margin-bottom: 20px;">Quick Access</h2>
                    <div class="parent-actions">
                        <a href="./attendence.php" class="parent-action">
                            <i class="bi bi-calendar-event"></i>
                            <span>Attendance</span>
                        </a>
                        <a href="./scorecard.php" class="parent-action">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Scorecard</span>
                        </a>
                        <a href="./fees.php" class="parent-action">
                            <i class="bi bi-credit-card"></i>
                            <span>Fees</span>
                        </a>
                        <a href="./ptm.php" class="parent-action">
                            <i class="bi bi-chat-left-text"></i>
                            <span>PTM</span>
                        </a>
                        <a href="./alerts.php" class="parent-action">
                            <i class="bi bi-bell"></i>
                            <span>Alerts</span>
                        </a>
                    </div>
                </div>

                <!-- Children -->
                <div class="section-box">
                    <h2 class="section-title" style="margin-bottom: 20px;">Your Children</h2>
                    <div>
                        <?php if (!empty($children)): ?>
                            <?php foreach ($children as $child): ?>
                                <div class="child-card">
                                    <div class="child-name">
                                        <i class="bi bi-person-circle" style="margin-right: 8px;"></i>
                                        <?php echo htmlspecialchars($child['full_name']); ?>
                                    </div>
                                    <div class="child-info">
                                        <?php echo htmlspecialchars($child['email']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: rgba(255, 255, 255, 0.5);">No students linked to this account</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/educore.js"></script>
</body>
</html>
