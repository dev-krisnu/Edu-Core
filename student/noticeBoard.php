<?php
/**
 * Notice Board - Announcements & Notifications
 * View institutional announcements and notifications
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch notices
$stmt = $pdo->prepare("
    SELECT * FROM notices 
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->execute();
$notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .notices-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .notices-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .notice-card {
            background: rgba(99, 102, 241, 0.1);
            border-left: 4px solid #6366F1;
            border-top: 1px solid rgba(99, 102, 241, 0.2);
            border-right: 1px solid rgba(99, 102, 241, 0.2);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notice-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateX(4px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.2);
        }

        .notice-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .notice-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #F5F4FF;
            flex: 1;
            line-height: 1.3;
        }

        .notice-date {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.5);
            white-space: nowrap;
            margin-left: 16px;
        }

        .notice-meta {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(245, 244, 255, 0.8);
        }

        .meta-badge i {
            font-size: 0.9rem;
        }

        .priority-high {
            border-left-color: #EF4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .priority-medium {
            border-left-color: #F59E0B;
            background: rgba(245, 158, 11, 0.1);
        }

        .priority-low {
            border-left-color: #10B981;
            background: rgba(16, 185, 129, 0.1);
        }

        .notice-body {
            color: rgba(245, 244, 255, 0.85);
            line-height: 1.5;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .notice-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid rgba(99, 102, 241, 0.1);
        }

        .department-tag {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(34, 211, 238, 0.2);
            color: #67E8F9;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .read-btn {
            padding: 6px 12px;
            background: transparent;
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #67E8F9;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .read-btn:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 14px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            border-color: transparent;
            color: white;
        }

        .filter-btn:hover:not(.active) {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .no-notices {
            text-align: center;
            padding: 60px 20px;
            background: rgba(99, 102, 241, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            color: rgba(245, 244, 255, 0.6);
        }

        .no-notices i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .notice-header {
                flex-direction: column;
                gap: 8px;
            }

            .notice-date {
                margin-left: 0;
            }

            .filter-bar {
                justify-content: center;
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
            <div class="notices-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Notice Board</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Stay updated with institutional announcements</p>
                </div>

                <!-- Notices List -->
                <?php if (count($notices) > 0): ?>
                    <div class="notices-list">
                        <?php foreach ($notices as $notice): 
                            $createdDate = new DateTime($notice['created_at']);
                            $priority = $notice['priority'] ?? 'low';
                            $priorityClass = 'priority-' . strtolower($priority);
                        ?>
                            <div class="notice-card <?php echo $priorityClass; ?>">
                                <div class="notice-header">
                                    <div class="notice-title">
                                        <i class="bi bi-megaphone"></i> 
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                    </div>
                                    <div class="notice-date">
                                        <?php echo $createdDate->format('M d, Y'); ?>
                                    </div>
                                </div>

                                <div class="notice-meta">
                                    <span class="meta-badge">
                                        <i class="bi bi-tag"></i>
                                        <?php echo htmlspecialchars($notice['category'] ?? 'General'); ?>
                                    </span>
                                    <span class="meta-badge" style="text-transform: uppercase;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($priority); ?>
                                    </span>
                                </div>

                                <div class="notice-body">
                                    <?php echo htmlspecialchars(substr($notice['content'] ?? $notice['description'], 0, 200)) . '...'; ?>
                                </div>

                                <div class="notice-footer">
                                    <span class="department-tag">
                                        <i class="bi bi-building"></i>
                                        <?php echo htmlspecialchars($notice['department'] ?? 'All'); ?>
                                    </span>
                                    <button class="read-btn" onclick="alert('Full notice: ' + '<?php echo addslashes($notice['title']); ?>')">
                                        Read Full <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-notices">
                        <i class="bi bi-inbox"></i>
                        <p>No notices at the moment. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
