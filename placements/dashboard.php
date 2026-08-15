<?php
/**
 * Placements Dashboard
 * Recruitment drives and placement tracking
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['placements']);

$pdo = getDbConnection();

try {
    $totalDrives = $pdo->query('SELECT COUNT(*) as count FROM placement_drives')->fetch()['count'] ?? 0;
    $activeDrives = $pdo->query('SELECT COUNT(*) as count FROM placement_drives WHERE status = "active"')->fetch()['count'] ?? 0;
    $applications = $pdo->query('SELECT COUNT(*) as count FROM placement_applications')->fetch()['count'] ?? 0;
    $selectedCount = $pdo->query('SELECT COUNT(*) as count FROM placement_applications WHERE status = "selected"')->fetch()['count'] ?? 0;
    
    $recentDrives = $pdo->query('SELECT * FROM placement_drives ORDER BY created_at DESC LIMIT 8')->fetchAll();
} catch (Exception $e) {
    error_log('[Placements Dashboard] Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placements Dashboard - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .placement-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .metric-placement {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(253, 230, 138, 0.05));
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .metric-placement:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.25);
        }

        .metric-icon-placement {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, #F59E0B, #FDE68A);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1F2937;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .metric-number-placement {
            font-size: 2rem;
            font-weight: 800;
            color: #FDE68A;
            margin-bottom: 4px;
        }

        .metric-label-placement {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .placement-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }

        .placement-action {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.08));
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #FDE68A;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .placement-action:hover {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.3), rgba(245, 158, 11, 0.15));
            border-color: #F59E0B;
            transform: translateY(-2px);
        }

        .drive-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            transition: all 0.3s ease;
        }

        .drive-card:hover {
            background: rgba(245, 158, 11, 0.1);
            border-color: #F59E0B;
        }

        .drive-title {
            font-weight: 700;
            color: #F5F4FF;
            margin-bottom: 8px;
        }

        .drive-info {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            gap: 16px;
            margin-bottom: 8px;
        }

        .drive-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            background: rgba(245, 158, 11, 0.2);
            color: #FDE68A;
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
            <div class="container">
                <!-- Header -->
                <div style="margin-bottom: 40px;">
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0 0 8px 0;">Recruitment Overview</p>
                    <h1 class="h-display" style="margin: 0;">Placements Dashboard</h1>
                </div>

                <!-- Metrics -->
                <div class="placement-metrics">
                    <div class="metric-placement">
                        <div class="metric-icon-placement"><i class="bi bi-briefcase"></i></div>
                        <div class="metric-number-placement"><?php echo $totalDrives; ?></div>
                        <div class="metric-label-placement">Total Drives</div>
                    </div>
                    <div class="metric-placement">
                        <div class="metric-icon-placement"><i class="bi bi-play-circle"></i></div>
                        <div class="metric-number-placement"><?php echo $activeDrives; ?></div>
                        <div class="metric-label-placement">Active Now</div>
                    </div>
                    <div class="metric-placement">
                        <div class="metric-icon-placement"><i class="bi bi-file-earmark"></i></div>
                        <div class="metric-number-placement"><?php echo $applications; ?></div>
                        <div class="metric-label-placement">Applications</div>
                    </div>
                    <div class="metric-placement">
                        <div class="metric-icon-placement"><i class="bi bi-check-circle"></i></div>
                        <div class="metric-number-placement"><?php echo $selectedCount; ?></div>
                        <div class="metric-label-placement">Selected</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-bottom: 40px;">
                    <h2 class="section-title" style="margin-bottom: 20px;">Manage</h2>
                    <div class="placement-actions">
                        <a href="./internship.php" class="placement-action">
                            <i class="bi bi-book"></i>
                            <span>Internships</span>
                        </a>
                        <a href="./resumeUploader.php" class="placement-action">
                            <i class="bi bi-cloud-upload"></i>
                            <span>Resumes</span>
                        </a>
                        <a href="./aiResumeMatcher.php" class="placement-action">
                            <i class="bi bi-stars"></i>
                            <span>AI Matcher</span>
                        </a>
                        <a href="./teacherUpskill.php" class="placement-action">
                            <i class="bi bi-person-badge"></i>
                            <span>Training</span>
                        </a>
                    </div>
                </div>

                <!-- Active Drives -->
                <div class="section-box">
                    <h2 class="section-title" style="margin-bottom: 20px;">Upcoming Recruitment Drives</h2>
                    <div>
                        <?php if (!empty($recentDrives)): ?>
                            <?php foreach ($recentDrives as $drive): ?>
                                <div class="drive-card">
                                    <div class="drive-title"><?php echo htmlspecialchars($drive['company_name'] ?? 'Company'); ?></div>
                                    <div class="drive-info">
                                        <span><i class="bi bi-calendar"></i> <?php echo date('M d', strtotime($drive['drive_date'] ?? 'now')); ?></span>
                                        <span><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($drive['position'] ?? 'Position'); ?></span>
                                    </div>
                                    <span class="drive-badge"><?php echo ucfirst(htmlspecialchars($drive['status'] ?? 'pending')); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: rgba(255, 255, 255, 0.5);">No recruitment drives scheduled</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/educore.js"></script>
</body>
</html>
