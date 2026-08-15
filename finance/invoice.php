<?php
/**
 * Finance - Invoice Management
 * Generate and manage fee invoices
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['finance']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch invoices with filtering
$status_filter = trim($_GET['status'] ?? '');

$sql = "SELECT fi.*, u.name, ft.fee_name 
        FROM fee_invoices fi
        JOIN users u ON fi.student_id = u.id
        LEFT JOIN fee_templates ft ON fi.template_id = ft.id
        WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql .= " AND fi.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY fi.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary
$stmt = $pdo->query("SELECT status, COUNT(*) as count, SUM(amount) as total FROM fee_invoices GROUP BY status");
$summary = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $summary[$row['status']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .finance-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 12px;
            padding: 18px;
            text-align: center;
        }

        .summary-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #67E8F9;
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
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: linear-gradient(120deg, #06B6D4, #67E8F9);
            border-color: transparent;
            color: white;
        }

        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(6, 182, 212, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .invoices-table th {
            background: rgba(6, 182, 212, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(6, 182, 212, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .invoices-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(6, 182, 212, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .invoices-table tbody tr:hover {
            background: rgba(6, 182, 212, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .status-pending {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
        }

        .status-overdue {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #06B6D4, #67E8F9);
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
            box-shadow: 0 6px 12px rgba(6, 182, 212, 0.3);
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
            .invoices-table {
                font-size: 0.85rem;
            }

            .invoices-table th,
            .invoices-table td {
                padding: 8px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
                margin-right: 2px;
            }
        }
    </style>
</head>
<body data-role="finance">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="finance-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Invoice Management</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Track and manage student fee invoices</p>
                </div>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-value">₹<?php echo number_format($summary['completed']['total'] ?? 0, 2); ?></div>
                        <div class="summary-label">Completed Payments</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value">₹<?php echo number_format($summary['pending']['total'] ?? 0, 2); ?></div>
                        <div class="summary-label">Pending Payments</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value"><?php echo $summary['completed']['count'] ?? 0; ?></div>
                        <div class="summary-label">Total Invoices</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <a href="?status=" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">
                        All Status
                    </a>
                    <a href="?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        Pending
                    </a>
                    <a href="?status=completed" class="filter-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                        Completed
                    </a>
                    <a href="?status=overdue" class="filter-btn <?php echo $status_filter === 'overdue' ? 'active' : ''; ?>">
                        Overdue
                    </a>
                </div>

                <!-- Invoices Table -->
                <h2 class="section-title">
                    <i class="bi bi-receipt"></i> Invoices (<?php echo count($invoices); ?>)
                </h2>

                <?php if (count($invoices) > 0): ?>
                    <table class="invoices-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Student Name</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): 
                                $dueDate = new DateTime($invoice['due_date']);
                                $today = new DateTime();
                                $isOverdue = $dueDate < $today && $invoice['status'] !== 'completed';
                                $statusClass = $isOverdue ? 'status-overdue' : 'status-' . $invoice['status'];
                            ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($invoice['id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($invoice['name']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['fee_name'] ?? 'General Fee'); ?></td>
                                    <td>₹<?php echo number_format($invoice['amount'], 2); ?></td>
                                    <td><?php echo $dueDate->format('M d, Y'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $isOverdue ? 'OVERDUE' : strtoupper($invoice['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="alert('View invoice')">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="action-btn" onclick="alert('Edit invoice')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(6, 182, 212, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No invoices found matching the selected filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
