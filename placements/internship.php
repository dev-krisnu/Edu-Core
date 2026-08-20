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
requireRole(['tpo']);

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
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
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
<body class="portal-page" data-role="placements">
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
                                    <td><?php echo htmlspecialchars($drive['job_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo $driveDate->format('M d, Y'); ?></td>
                                    <td>₹<?php echo number_format((float) ($drive['package_lpa'] ?? 0), 2); ?> LPA</td>
                                    <td><?php echo $drive['total_applications']; ?></td>
                                    <td><strong><?php echo $drive['selected_count']; ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($drive['status'] ?? 'upcoming'); ?>">
                                            <?php echo ucfirst($drive['status'] ?? 'upcoming'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" type="button"
                                            onclick='openDriveDetailModal(<?php echo json_encode([
                                                "company_name" => $drive["company_name"],
                                                "job_title" => $drive["job_title"],
                                                "description" => $drive["description"] ?? "No description provided.",
                                                "min_cgpa" => $drive["min_cgpa"],
                                                "package_lpa" => number_format((float) ($drive["package_lpa"] ?? 0), 2),
                                                "drive_date" => $driveDate->format("M d, Y"),
                                                "status" => ucfirst($drive["status"] ?? "upcoming"),
                                                "total_applications" => $drive["total_applications"],
                                                "selected_count" => $drive["selected_count"],
                                            ]); ?>)'>
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

    <div id="driveDetailOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;" onclick="if(event.target===this) document.getElementById('driveDetailOverlay').style.display='none'">
        <div style="background:#1a1a3e; border:1px solid rgba(245,158,11,0.3); border-radius:16px; padding:28px; max-width:480px; width:90%; max-height:80vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:16px;">
                <h2 id="dd_company" style="color:#F5F4FF; margin:0;"></h2>
                <button type="button" onclick="document.getElementById('driveDetailOverlay').style.display='none'" style="background:none; border:none; color:rgba(245,244,255,0.6); font-size:1.4rem; cursor:pointer;">&times;</button>
            </div>
            <p style="color:rgba(245,244,255,0.7);" id="dd_job"></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-cash"></i> Package: ₹<span id="dd_package"></span> LPA</p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-mortarboard"></i> Min CGPA: <span id="dd_cgpa"></span></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-calendar"></i> Drive Date: <span id="dd_date"></span></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-info-circle"></i> Status: <span id="dd_status"></span></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-people"></i> <span id="dd_stats"></span></p>
            <p style="color:rgba(245,244,255,0.85); margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.1); line-height:1.6;" id="dd_description"></p>
        </div>
    </div>
    <script>
        function openDriveDetailModal(d) {
            document.getElementById('dd_company').textContent = d.company_name;
            document.getElementById('dd_job').textContent = d.job_title;
            document.getElementById('dd_package').textContent = d.package_lpa;
            document.getElementById('dd_cgpa').textContent = d.min_cgpa;
            document.getElementById('dd_date').textContent = d.drive_date;
            document.getElementById('dd_status').textContent = d.status;
            document.getElementById('dd_stats').textContent = d.total_applications + ' applications, ' + d.selected_count + ' selected';
            document.getElementById('dd_description').textContent = d.description;
            document.getElementById('driveDetailOverlay').style.display = 'flex';
        }
    </script>
</body>
</html>
