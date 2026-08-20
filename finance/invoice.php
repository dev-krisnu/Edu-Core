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

$message = '';
$messageType = '';

// Handle invoice update (status / amount / penalty)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_invoice') {
    $invoice_id = intval($_POST['invoice_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $penalty = (float) ($_POST['penalty'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($invoice_id && $amount > 0 && in_array($status, ['pending', 'paid', 'overdue', 'cancelled'], true)) {
        try {
            $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("
                UPDATE fee_invoices SET amount = ?, penalty = ?, status = ?, paid_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $penalty, $status, $paidAt, $invoice_id]);
            $message = 'Invoice updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = 'Amount and status are required.';
        $messageType = 'error';
    }
}

// Fetch invoices with filtering
$status_filter = trim($_GET['status'] ?? '');

$sql = "SELECT fi.*, u.full_name AS name, u.email AS student_email, ft.name AS fee_name, ft.due_date
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
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
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
            <div class="finance-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Invoice Management</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Track and manage student fee invoices</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>" style="padding:14px; border-radius:8px; margin-bottom:20px; border-left:4px solid <?php echo $messageType === 'success' ? '#10B981' : '#EF4444'; ?>; background:rgba(<?php echo $messageType === 'success' ? '16,185,129' : '239,68,68'; ?>,0.1); color:<?php echo $messageType === 'success' ? '#6EE7B7' : '#FCA5A5'; ?>;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-value">₹<?php echo number_format((float)($summary['completed']['total'] ?? 0), 2); ?></div>
                        <div class="summary-label">Completed Payments</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value">₹<?php echo number_format((float)($summary['pending']['total'] ?? 0), 2); ?></div>
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
                                $dueDate = !empty($invoice['due_date']) ? new DateTime($invoice['due_date']) : null;
                                $today = new DateTime();
                                $isOverdue = $dueDate !== null && $dueDate < $today && $invoice['status'] !== 'paid';
                                $statusClass = $isOverdue ? 'status-overdue' : 'status-' . $invoice['status'];
                            ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars((string) $invoice['id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($invoice['name']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['fee_name'] ?? 'General Fee'); ?></td>
                                    <td>₹<?php echo number_format((float)$invoice['amount'], 2); ?></td>
                                    <td><?php echo $dueDate ? $dueDate->format('M d, Y') : 'Not set'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $isOverdue ? 'OVERDUE' : strtoupper($invoice['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" type="button"
                                            onclick='openViewInvoiceModal(<?php echo json_encode([
                                                "id" => $invoice["id"],
                                                "name" => $invoice["name"],
                                                "student_email" => $invoice["student_email"],
                                                "fee_name" => $invoice["fee_name"] ?? "General Fee",
                                                "amount" => $invoice["amount"],
                                                "penalty" => $invoice["penalty"],
                                                "status" => $isOverdue ? "OVERDUE" : strtoupper($invoice["status"]),
                                                "due_date" => $dueDate ? $dueDate->format("M d, Y") : "Not set",
                                                "paid_at" => $invoice["paid_at"] ? (new DateTime($invoice["paid_at"]))->format("M d, Y H:i") : "Not paid",
                                                "created_at" => (new DateTime($invoice["created_at"]))->format("M d, Y"),
                                            ]); ?>)'>
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="action-btn" type="button"
                                            onclick='openEditInvoiceModal(<?php echo json_encode([
                                                "id" => $invoice["id"],
                                                "amount" => $invoice["amount"],
                                                "penalty" => $invoice["penalty"],
                                                "status" => $invoice["status"],
                                            ]); ?>)'>
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

    <!-- View Invoice Modal -->
    <div id="viewInvoiceOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
        <div style="background:#1a1a3e; border:1px solid rgba(6,182,212,0.3); border-radius:16px; padding:28px; max-width:440px; width:90%;">
            <h2 style="color:#F5F4FF; margin:0 0 20px 0;"><i class="bi bi-receipt"></i> Invoice Details</h2>
            <div id="viewInvoiceBody" style="color:rgba(245,244,255,0.85); line-height:2;"></div>
            <button type="button" class="action-btn" style="margin-top:16px;" onclick="document.getElementById('viewInvoiceOverlay').style.display='none'">Close</button>
        </div>
    </div>

    <!-- Edit Invoice Modal -->
    <div id="editInvoiceOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
        <div style="background:#1a1a3e; border:1px solid rgba(6,182,212,0.3); border-radius:16px; padding:28px; max-width:400px; width:90%;">
            <h2 style="color:#F5F4FF; margin:0 0 20px 0;"><i class="bi bi-pencil-square"></i> Edit Invoice</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_invoice">
                <input type="hidden" name="invoice_id" id="edit_invoice_id">
                <div class="mb-2">
                    <label class="form-label" style="display:block; margin-bottom:6px; color:rgba(245,244,255,0.8);">Amount (₹)</label>
                    <input type="number" step="0.01" class="form-input" name="amount" id="edit_amount" style="width:100%; padding:10px; border-radius:8px; border:1px solid rgba(6,182,212,0.3); background:rgba(255,255,255,0.05); color:#F5F4FF;" required>
                </div>
                <div class="mb-2" style="margin-top:12px;">
                    <label class="form-label" style="display:block; margin-bottom:6px; color:rgba(245,244,255,0.8);">Penalty (₹)</label>
                    <input type="number" step="0.01" class="form-input" name="penalty" id="edit_penalty" style="width:100%; padding:10px; border-radius:8px; border:1px solid rgba(6,182,212,0.3); background:rgba(255,255,255,0.05); color:#F5F4FF;">
                </div>
                <div class="mb-2" style="margin-top:12px;">
                    <label class="form-label" style="display:block; margin-bottom:6px; color:rgba(245,244,255,0.8);">Status</label>
                    <select class="form-select" name="status" id="edit_status" style="width:100%; padding:10px; border-radius:8px; border:1px solid rgba(6,182,212,0.3); background:rgba(255,255,255,0.05); color:#F5F4FF;" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="submit-btn" style="flex:1;">Save Changes</button>
                    <button type="button" class="action-btn" onclick="document.getElementById('editInvoiceOverlay').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openViewInvoiceModal(inv) {
            document.getElementById('viewInvoiceBody').innerHTML = `
                <div><strong>Invoice</strong> #${inv.id}</div>
                <div><strong>Student:</strong> ${inv.name} (${inv.student_email})</div>
                <div><strong>Fee Type:</strong> ${inv.fee_name}</div>
                <div><strong>Amount:</strong> ₹${Number(inv.amount).toFixed(2)}</div>
                <div><strong>Penalty:</strong> ₹${Number(inv.penalty).toFixed(2)}</div>
                <div><strong>Status:</strong> ${inv.status}</div>
                <div><strong>Due Date:</strong> ${inv.due_date}</div>
                <div><strong>Paid At:</strong> ${inv.paid_at}</div>
                <div><strong>Created:</strong> ${inv.created_at}</div>
            `;
            document.getElementById('viewInvoiceOverlay').style.display = 'flex';
        }
        function openEditInvoiceModal(inv) {
            document.getElementById('edit_invoice_id').value = inv.id;
            document.getElementById('edit_amount').value = inv.amount;
            document.getElementById('edit_penalty').value = inv.penalty;
            document.getElementById('edit_status').value = inv.status;
            document.getElementById('editInvoiceOverlay').style.display = 'flex';
        }
    </script>
</body>
</html>
