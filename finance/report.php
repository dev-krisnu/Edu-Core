<?php
/**
 * Finance - Financial Reports
 * Generate and view financial reports
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['finance']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Get date range
$fromDate = trim($_GET['from'] ?? date('Y-m-01'));
$toDate = trim($_GET['to'] ?? date('Y-m-d'));

// Fetch revenue data
$stmt = $pdo->prepare("
    SELECT 
        DATE(payment_date) as date,
        COUNT(*) as transactions,
        SUM(amount) as total_amount
    FROM fee_invoices
    WHERE status = 'completed' AND payment_date BETWEEN ? AND ?
    GROUP BY DATE(payment_date)
    ORDER BY date DESC
");
$stmt->execute([$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
$dailyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_invoices,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as collected
    FROM fee_invoices
    WHERE payment_date BETWEEN ? AND ?
");
$stmt->execute([$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Status breakdown
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count, SUM(amount) as total
    FROM fee_invoices
    WHERE payment_date BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
$statusBreakdown = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusBreakdown[$row['status']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .reports-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .filter-section {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.2);
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

        .filter-input {
            padding: 8px;
            background: rgba(6, 182, 212, 0.05);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 6px;
            color: #F5F4FF;
            font-family: inherit;
        }

        .filter-input:focus {
            outline: none;
            border-color: #06B6D4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
        }

        .filter-btn {
            padding: 8px 16px;
            background: linear-gradient(120deg, #06B6D4, #67E8F9);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(6, 182, 212, 0.3);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 12px;
            padding: 18px;
        }

        .card-label {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #67E8F9;
            margin-bottom: 4px;
        }

        .card-subtext {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.5);
        }

        .revenue-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(6, 182, 212, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .revenue-table th {
            background: rgba(6, 182, 212, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(6, 182, 212, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .revenue-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(6, 182, 212, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .revenue-table tbody tr:hover {
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

            .revenue-table {
                font-size: 0.85rem;
            }

            .revenue-table th,
            .revenue-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="finance">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="reports-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Financial Reports</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Generate and analyze financial data</p>
                </div>

                <!-- Filter Section -->
                <form method="GET" class="filter-section">
                    <div class="filter-group">
                        <label class="filter-label">From Date</label>
                        <input type="date" name="from" class="filter-input" value="<?php echo $fromDate; ?>" required>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">To Date</label>
                        <input type="date" name="to" class="filter-input" value="<?php echo $toDate; ?>" required>
                    </div>
                    <button type="submit" class="filter-btn">
                        <i class="bi bi-search"></i> Generate Report
                    </button>
                </form>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="card-label">Total Invoices</div>
                        <div class="card-value"><?php echo $summary['total_invoices']; ?></div>
                        <div class="card-subtext">₹<?php echo number_format($summary['total_amount'] ?? 0, 2); ?> total</div>
                    </div>
                    <div class="summary-card">
                        <div class="card-label">Completed Payments</div>
                        <div class="card-value">₹<?php echo number_format($summary['collected'] ?? 0, 2); ?></div>
                        <div class="card-subtext"><?php echo $summary['completed'] ?? 0; ?> invoices paid</div>
                    </div>
                    <div class="summary-card">
                        <div class="card-label">Pending Payments</div>
                        <div class="card-value">₹<?php echo number_format(($summary['total_amount'] ?? 0) - ($summary['collected'] ?? 0), 2); ?></div>
                        <div class="card-subtext"><?php echo $summary['pending'] ?? 0; ?> pending</div>
                    </div>
                    <div class="summary-card">
                        <div class="card-label">Collection Rate</div>
                        <div class="card-value">
                            <?php 
                                $rate = $summary['total_amount'] > 0 ? (($summary['collected'] ?? 0) / ($summary['total_amount'] ?? 1)) * 100 : 0;
                                echo number_format($rate, 1); 
                            ?>%
                        </div>
                        <div class="card-subtext">of total expected</div>
                    </div>
                </div>

                <!-- Daily Revenue Table -->
                <h2 class="section-title">
                    <i class="bi bi-bar-chart"></i> Daily Revenue
                </h2>

                <?php if (count($dailyRevenue) > 0): ?>
                    <table class="revenue-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transactions</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailyRevenue as $day): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['transactions']; ?></td>
                                    <td><strong>₹<?php echo number_format($day['total_amount'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(6, 182, 212, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No transactions in the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
