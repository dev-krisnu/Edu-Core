<?php
/**
 * Library - Fine Management
 * Track and manage library fines
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['librarian']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$status_filter = trim($_GET['status'] ?? '');

// Fetch fines
$sql = "SELECT lc.*, u.full_name as student_name, u.email,
               lb.book_title, lc.due_date,
               DATEDIFF(CURDATE(), lc.due_date) as days_overdue
        FROM library_circulation lc
        JOIN users u ON lc.student_id = u.id
        JOIN library_books lb ON lc.book_id = lb.id
        WHERE lc.return_date IS NULL AND lc.due_date < CURDATE()";
$params = [];

if ($status_filter) {
    $sql .= " AND lc.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY lc.due_date ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$overdueItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total fines
$totalFines = 0;
foreach ($overdueItems as $item) {
    $days = $item['days_overdue'];
    $finePerDay = 5; // ₹5 per day
    $totalFines += ($days * $finePerDay);
}

// Get statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_overdue,
        SUM(DATEDIFF(CURDATE(), due_date)) as total_days_overdue
    FROM library_circulation
    WHERE return_date IS NULL AND due_date < CURDATE()
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Fines - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .fines-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(110, 231, 183, 0.1));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #6EE7B7;
            margin-bottom: 4px;
        }

        .stat-label {
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
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .filter-btn.active {
            background: linear-gradient(120deg, #10B981, #6EE7B7);
            border-color: transparent;
            color: white;
        }

        .fines-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .fines-table th {
            background: rgba(16, 185, 129, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(16, 185, 129, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .fines-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .fines-table tbody tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }

        .fine-amount {
            font-weight: 700;
            color: #FCA5A5;
        }

        .days-overdue {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #10B981, #6EE7B7);
            color: white;
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
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
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
            .fines-table {
                font-size: 0.85rem;
            }

            .fines-table th,
            .fines-table td {
                padding: 8px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="library">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="fines-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Library Fines</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Track and manage overdue book fines</p>
                </div>

                <!-- Statistics -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_overdue']; ?></div>
                        <div class="stat-label">Overdue Books</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_days_overdue'] ?? 0; ?></div>
                        <div class="stat-label">Total Days Overdue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">₹<?php echo number_format($totalFines, 2); ?></div>
                        <div class="stat-label">Total Fine Pending</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <a href="?" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">
                        All Overdue
                    </a>
                    <a href="?status=active" class="filter-btn <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
                        Active
                    </a>
                    <a href="?status=resolved" class="filter-btn <?php echo $status_filter === 'resolved' ? 'active' : ''; ?>">
                        Resolved
                    </a>
                </div>

                <!-- Overdue Books Table -->
                <h2 class="section-title">
                    <i class="bi bi-exclamation-circle"></i> Overdue Books (<?php echo count($overdueItems); ?>)
                </h2>

                <?php if (count($overdueItems) > 0): ?>
                    <table class="fines-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Book Title</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Fine Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdueItems as $item): 
                                $dueDate = new DateTime($item['due_date']);
                                $days = intval($item['days_overdue']);
                                $finePerDay = 5;
                                $fine = $days * $finePerDay;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['student_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['book_title']); ?></td>
                                    <td><?php echo $dueDate->format('M d, Y'); ?></td>
                                    <td>
                                        <span class="days-overdue">
                                            <?php echo $days; ?> days
                                        </span>
                                    </td>
                                    <td><span class="fine-amount">₹<?php echo number_format($fine, 2); ?></span></td>
                                    <td>
                                        <button class="action-btn" onclick="alert('Send reminder to: ' + '<?php echo addslashes($item['student_name']); ?>')">
                                            <i class="bi bi-bell"></i> Remind
                                        </button>
                                        <button class="action-btn" onclick="alert('Resolve fine')">
                                            <i class="bi bi-check"></i> Resolve
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(16, 185, 129, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No overdue books. Great job!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
