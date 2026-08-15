<?php
/**
 * Admin - System Logs
 * View system activity and audit logs
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['admin']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$action_filter = trim($_GET['action'] ?? '');
$days = intval($_GET['days'] ?? 7);

// Fetch system logs
$sql = "SELECT sl.*, u.name as user_name
        FROM system_logs sl
        LEFT JOIN users u ON sl.user_id = u.id
        WHERE sl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$params = [$days];

if ($action_filter) {
    $sql .= " AND sl.action LIKE ?";
    $params[] = '%' . $action_filter . '%';
}

$sql .= " ORDER BY sl.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action types
$stmt = $pdo->query("SELECT DISTINCT action FROM system_logs ORDER BY action");
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Count by action type
$stmt = $pdo->prepare("
    SELECT action, COUNT(*) as count 
    FROM system_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY action
    ORDER BY count DESC
");
$stmt->execute([$days]);
$actionStats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $actionStats[$row['action']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .logs-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .filter-section {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-label {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.7);
            font-weight: 600;
        }

        .filter-input,
        .filter-select {
            padding: 8px;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 6px;
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .filter-btn {
            padding: 8px 16px;
            background: linear-gradient(120deg, #EF4444, #FCA5A5);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .logs-table th {
            background: rgba(239, 68, 68, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(239, 68, 68, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .logs-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(239, 68, 68, 0.1);
            color: rgba(245, 244, 255, 0.8);
            font-size: 0.9rem;
        }

        .logs-table tbody tr:hover {
            background: rgba(239, 68, 68, 0.05);
        }

        .action-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .timestamp {
            color: rgba(245, 244, 255, 0.6);
            font-size: 0.85rem;
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

        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex: 1;
            }

            .logs-table {
                font-size: 0.85rem;
            }

            .logs-table th,
            .logs-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body data-role="admin">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="logs-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">System Logs</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">View system activity and audit trail</p>
                </div>

                <!-- Filter Section -->
                <form method="GET" class="filter-section">
                    <div class="filter-group">
                        <label class="filter-label">Time Range (Days)</label>
                        <select name="days" class="filter-select" onchange="this.form.submit()">
                            <option value="1" <?php echo $days == 1 ? 'selected' : ''; ?>>Last 24 Hours</option>
                            <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>Last 90 Days</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Filter by Action</label>
                        <select name="action" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo urlencode($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <!-- Logs Table -->
                <h2 class="section-title">
                    <i class="bi bi-clock-history"></i> Activity Log (<?php echo count($logs); ?> entries)
                </h2>

                <?php if (count($logs) > 0): ?>
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $logTime = new DateTime($log['created_at']);
                            ?>
                                <tr>
                                    <td>
                                        <div class="timestamp">
                                            <?php echo $logTime->format('M d, Y h:i:s A'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                    <td>
                                        <span class="action-badge">
                                            <?php echo htmlspecialchars(ucfirst($log['action'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 50)); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(239, 68, 68, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No logs found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
