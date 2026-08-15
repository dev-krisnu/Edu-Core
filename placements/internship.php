<?php
/**
 * Placements - Internship Management
 * Track internship opportunities and applications
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['placements']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$status_filter = trim($_GET['status'] ?? '');

// Fetch placement drives
$sql = "SELECT pd.*, 
               COUNT(DISTINCT pa.id) as total_applications,
               SUM(CASE WHEN pa.status = 'selected' THEN 1 ELSE 0 END) as selected_count
        FROM placement_drives pd
        LEFT JOIN placement_applications pa ON pd.id = pa.drive_id
        WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql .= " AND pd.status = ?";
    $params[] = $status_filter;
}

$sql .= " GROUP BY pd.id ORDER BY pd.drive_date DESC LIMIT 30";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$drives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM placement_drives GROUP BY status");
$summary = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $summary[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Management - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .placement-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(253, 224, 71, 0.1));
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: #FCD34D;
            margin-bottom: 4px;
        }

        .summary-label {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            text-transform: uppercase;
        }

        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 14px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: linear-gradient(120deg, #F59E0B, #FCD34D);
            border-color: transparent;
            color: white;
        }

        .filter-btn:hover {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.5);
        }

        .drives-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .drives-table th {
            background: rgba(245, 158, 11, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(245, 158, 11, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .drives-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .drives-table tbody tr:hover {
            background: rgba(245, 158, 11, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-upcoming {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
        }

        .status-ongoing {
            background: rgba(34, 211, 238, 0.2);
            color: #67E8F9;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #F59E0B, #FCD34D);
            color: #18181B;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 4px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(245, 158, 11, 0.3);
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

        .company-name {
            font-weight: 600;
            color: #FCD34D;
        }

        @media (max-width: 768px) {
            .drives-table {
                font-size: 0.85rem;
            }

            .drives-table th,
            .drives-table td {
                padding: 8px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body data-role="placements">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="placement-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Internship Management</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Track recruitment drives and student placements</p>
                </div>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-value"><?php echo count($drives); ?></div>
                        <div class="summary-label">Total Drives</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value"><?php echo $summary['upcoming'] ?? 0; ?></div>
                        <div class="summary-label">Upcoming</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value"><?php echo $summary['ongoing'] ?? 0; ?></div>
                        <div class="summary-label">Ongoing</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value"><?php echo $summary['completed'] ?? 0; ?></div>
                        <div class="summary-label">Completed</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <a href="?status=" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">
                        All Drives
                    </a>
                    <a href="?status=upcoming" class="filter-btn <?php echo $status_filter === 'upcoming' ? 'active' : ''; ?>">
                        Upcoming
                    </a>
                    <a href="?status=ongoing" class="filter-btn <?php echo $status_filter === 'ongoing' ? 'active' : ''; ?>">
                        Ongoing
                    </a>
                    <a href="?status=completed" class="filter-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                        Completed
                    </a>
                </div>

                <!-- Drives Table -->
                <h2 class="section-title">
                    <i class="bi bi-briefcase"></i> Recruitment Drives (<?php echo count($drives); ?>)
                </h2>

                <?php if (count($drives) > 0): ?>
                    <table class="drives-table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Position</th>
                                <th>Drive Date</th>
                                <th>Salary</th>
                                <th>Applications</th>
                                <th>Selected</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drives as $drive): 
                                $driveDate = new DateTime($drive['drive_date']);
                            ?>
                                <tr>
                                    <td><span class="company-name"><?php echo htmlspecialchars(substr($drive['company_name'] ?? 'N/A', 0, 20)); ?></span></td>
                                    <td><?php echo htmlspecialchars($drive['position'] ?? 'N/A'); ?></td>
                                    <td><?php echo $driveDate->format('M d, Y'); ?></td>
                                    <td>₹<?php echo number_format($drive['salary'] ?? 0, 0); ?></td>
                                    <td><?php echo $drive['total_applications']; ?></td>
                                    <td><strong><?php echo $drive['selected_count']; ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($drive['status'] ?? 'upcoming'); ?>">
                                            <?php echo ucfirst($drive['status'] ?? 'upcoming'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="alert('View: ' + '<?php echo addslashes($drive['company_name']); ?>')">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(245, 158, 11, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No recruitment drives found matching the selected filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
