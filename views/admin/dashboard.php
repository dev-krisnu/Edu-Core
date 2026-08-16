<?php
/**
 * Admin Dashboard
 * System administration, user management, and analytics
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['super_admin']);

$pdo = getDbConnection();

// Get system statistics
try {
    $statsQueries = [
        'total_users' => 'SELECT COUNT(*) as count FROM users',
        'active_users' => 'SELECT COUNT(*) as count FROM users WHERE status = "active"',
        'total_courses' => 'SELECT COUNT(*) as count FROM courses',
        'total_exams' => 'SELECT COUNT(*) as count FROM exams',
        'recent_logs' => 'SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10',
        'user_breakdown' => 'SELECT role, COUNT(*) as count FROM users GROUP BY role'
    ];

    $stats = [];
    foreach ($statsQueries as $key => $query) {
        if ($key === 'recent_logs') {
            $stmt = $pdo->query($query);
            $stats[$key] = $stmt->fetchAll();
        } elseif ($key === 'user_breakdown') {
            $stmt = $pdo->query($query);
            $stats[$key] = $stmt->fetchAll();
        } else {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch();
            $stats[$key] = $result['count'] ?? 0;
        }
    }
} catch (Exception $e) {
    error_log('[Admin Dashboard] Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduCore</title>
    <?php $portalBase = '../..'; include __DIR__ . '/../../includes/portal_head.php'; ?>
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .metric-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(244, 63, 94, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.25);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #FCA5A5;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .metric-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            position: relative;
            z-index: 1;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }

        .action-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #FCA5A5;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .action-card:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.35), rgba(239, 68, 68, 0.15));
            border-color: #EF4444;
            transform: translateY(-2px);
            color: #FFE3E3;
        }

        .action-icon {
            font-size: 1.8rem;
            color: #EF4444;
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
        }

        .log-table th {
            background: rgba(239, 68, 68, 0.1);
            padding: 12px;
            text-align: left;
            font-weight: 700;
            color: #FCA5A5;
            font-size: 0.85rem;
            border-bottom: 2px solid rgba(239, 68, 68, 0.3);
        }

        .log-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .log-table tr:hover {
            background: rgba(239, 68, 68, 0.08);
        }

        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: capitalize;
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .section-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            backdrop-filter: blur(12px);
        }

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .user-stat {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: #FCA5A5;
            margin-bottom: 6px;
        }

        .user-stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: capitalize;
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
            <div class="container">
                <!-- Header -->
                <div style="margin-bottom: 40px;">
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0 0 8px 0;">System Overview</p>
                    <h1 class="h-display" style="margin: 0;">Administration Dashboard</h1>
                </div>

                <!-- Key Metrics -->
                <div class="admin-grid">
                    <div class="metric-card">
                        <div class="metric-value"><?php echo $stats['total_users'] ?? 0; ?></div>
                        <div class="metric-label">Total Users</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value"><?php echo $stats['active_users'] ?? 0; ?></div>
                        <div class="metric-label">Active Users</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value"><?php echo $stats['total_courses'] ?? 0; ?></div>
                        <div class="metric-label">Courses</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value"><?php echo $stats['total_exams'] ?? 0; ?></div>
                        <div class="metric-label">Exams</div>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div style="margin-bottom: 40px;">
                    <h2 class="section-title" style="margin-bottom: 20px;">Quick Actions</h2>
                    <div class="action-grid">
                        <a href="./userStudent.php" class="action-card">
                            <i class="bi bi-people action-icon"></i>
                            <span>Manage Students</span>
                        </a>
                        <a href="./userFaculty.php" class="action-card">
                            <i class="bi bi-person-badge action-icon"></i>
                            <span>Manage Faculty</span>
                        </a>
                        <a href="./academicCourses.php" class="action-card">
                            <i class="bi bi-book action-icon"></i>
                            <span>Courses</span>
                        </a>
                        <a href="./events.php" class="action-card">
                            <i class="bi bi-calendar-event action-icon"></i>
                            <span>Events</span>
                        </a>
                        <a href="./permissions.php" class="action-card">
                            <i class="bi bi-shield-check action-icon"></i>
                            <span>Permissions</span>
                        </a>
                        <a href="./logs.php" class="action-card">
                            <i class="bi bi-file-text action-icon"></i>
                            <span>System Logs</span>
                        </a>
                    </div>
                </div>

                <!-- User Breakdown -->
                <div class="section-box">
                    <h2 class="section-title" style="margin-bottom: 20px;">User Distribution</h2>
                    <div class="user-grid">
                        <?php if (!empty($stats['user_breakdown'])): ?>
                            <?php foreach ($stats['user_breakdown'] as $breakdown): ?>
                                <div class="user-stat">
                                    <div class="user-stat-number"><?php echo $breakdown['count']; ?></div>
                                    <div class="user-stat-label"><?php echo ucfirst(htmlspecialchars($breakdown['role'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="section-box">
                    <h2 class="section-title" style="margin-bottom: 20px;">Recent System Activity</h2>
                    <div style="overflow-x: auto;">
                        <table class="log-table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['recent_logs'])): ?>
                                    <?php foreach ($stats['recent_logs'] as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['action'] ?? 'System Action'); ?></td>
                                            <td><?php echo htmlspecialchars($log['user_id'] ?? 'System'); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <span class="role-badge"><?php echo htmlspecialchars($log['module'] ?? 'general'); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: rgba(255, 255, 255, 0.5);">No recent activity</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/educore.js"></script>
</body>
</html>