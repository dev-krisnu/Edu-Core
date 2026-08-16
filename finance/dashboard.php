<?php
/**
 * Finance Dashboard
 * Fee management, invoices, and financial reports
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['finance']);

$pdo = getDbConnection();

try {
    $totalInvoiced = (float) ($pdo->query('SELECT COALESCE(SUM(amount), 0) as total FROM fee_invoices')->fetch()['total'] ?? 0);
    $totalPaid = (float) ($pdo->query('SELECT COALESCE(SUM(amount), 0) as total FROM fee_invoices WHERE status = "paid"')->fetch()['total'] ?? 0);
    $pendingAmount = $totalInvoiced - $totalPaid;
    $invoiceCount = $pdo->query('SELECT COUNT(*) as count FROM fee_invoices WHERE status = "pending"')->fetch()['count'] ?? 0;
    
    $recentInvoices = $pdo->query('SELECT * FROM fee_invoices ORDER BY created_at DESC LIMIT 10')->fetchAll();
} catch (Exception $e) {
    error_log('[Finance Dashboard] Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .finance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .metric-finance {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(103, 232, 249, 0.05));
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .metric-finance:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.25);
        }

        .metric-icon-finance {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #06B6D4, #22D3EE);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        .metric-value-finance {
            font-size: 1.8rem;
            font-weight: 800;
            color: #67E8F9;
            margin-bottom: 4px;
        }

        .metric-label-finance {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .finance-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }

        .finance-btn {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(6, 182, 212, 0.08));
            border: 1px solid rgba(6, 182, 212, 0.4);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #67E8F9;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .finance-btn:hover {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.3), rgba(6, 182, 212, 0.15));
            border-color: #06B6D4;
            transform: translateY(-2px);
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 12px;
            overflow: hidden;
        }

        .invoice-table th {
            background: rgba(6, 182, 212, 0.15);
            padding: 14px;
            text-align: left;
            font-weight: 700;
            color: #67E8F9;
            font-size: 0.85rem;
            border-bottom: 2px solid rgba(6, 182, 212, 0.3);
        }

        .invoice-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(6, 182, 212, 0.15);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .invoice-table tr:hover {
            background: rgba(6, 182, 212, 0.08);
        }

        .status-paid {
            background: rgba(34, 197, 94, 0.2);
            color: #86EFAC;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.2);
            color: #FCD34D;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
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
            <div class="container">
                <!-- Header -->
                <div style="margin-bottom: 40px;">
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0 0 8px 0;">Financial Overview</p>
                    <h1 class="h-display" style="margin: 0;">Finance Dashboard</h1>
                </div>

                <!-- Key Metrics -->
                <div class="finance-metrics">
                    <div class="metric-finance">
                        <div class="metric-icon-finance"><i class="bi bi-currency-rupee"></i></div>
                        <div class="metric-value-finance">₹<?php echo number_format($totalInvoiced, 0); ?></div>
                        <div class="metric-label-finance">Total Invoiced</div>
                    </div>
                    <div class="metric-finance">
                        <div class="metric-icon-finance"><i class="bi bi-check-circle"></i></div>
                        <div class="metric-value-finance">₹<?php echo number_format($totalPaid, 0); ?></div>
                        <div class="metric-label-finance">Total Paid</div>
                    </div>
                    <div class="metric-finance">
                        <div class="metric-icon-finance"><i class="bi bi-hourglass-split"></i></div>
                        <div class="metric-value-finance">₹<?php echo number_format($pendingAmount, 0); ?></div>
                        <div class="metric-label-finance">Pending Payment</div>
                    </div>
                    <div class="metric-finance">
                        <div class="metric-icon-finance"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="metric-value-finance"><?php echo $invoiceCount; ?></div>
                        <div class="metric-label-finance">Pending Invoices</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-bottom: 40px;">
                    <h2 class="section-title" style="margin-bottom: 20px;">Actions</h2>
                    <div class="finance-buttons">
                        <a href="./invoice.php" class="finance-btn">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <span>Generate Invoice</span>
                        </a>
                        <a href="./paySlip.php" class="finance-btn">
                            <i class="bi bi-receipt"></i>
                            <span>Pay Slips</span>
                        </a>
                        <a href="./report.php" class="finance-btn">
                            <i class="bi bi-graph-up"></i>
                            <span>Reports</span>
                        </a>
                        <a href="./transaction.php" class="finance-btn">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Transactions</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Invoices -->
                <div class="section-box">
                    <h2 class="section-title" style="margin-bottom: 20px;">Recent Invoices</h2>
                    <div style="overflow-x: auto;">
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Invoice ID</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentInvoices)): ?>
                                    <?php foreach ($recentInvoices as $invoice): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($invoice['id'] ?? 'N/A'); ?></td>
                                            <td>₹<?php echo number_format((float)($invoice['amount'] ?? 0), 2); ?></td>
                                            <td>
                                                <span class="status-<?php echo $invoice['status'] ?? 'pending'; ?>">
                                                    <?php echo ucfirst($invoice['status'] ?? 'pending'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['due_date'] ?? 'now')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: rgba(255, 255, 255, 0.5);">No invoices found</td>
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
