<?php
/**
 * Fee Payment - Student Payment Portal
 * Razorpay integration for online fee collection
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/PaymentHandler.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch pending invoices
$stmt = $pdo->prepare("
    SELECT fi.*, ft.name AS fee_name, ft.amount AS template_amount, ft.due_date
    FROM fee_invoices fi
    LEFT JOIN fee_templates ft ON fi.template_id = ft.id
    WHERE fi.student_id = ? AND fi.status IN ('pending', 'overdue')
    ORDER BY fi.created_at ASC
");
$stmt->execute([$currentUser['id']]);
$pendingInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payment history
$stmt = $pdo->prepare("
    SELECT fi.*, ft.name AS fee_name, fi.paid_at AS payment_date
    FROM fee_invoices fi
    LEFT JOIN fee_templates ft ON fi.template_id = ft.id
    WHERE fi.student_id = ? AND fi.status = 'paid'
    ORDER BY fi.paid_at DESC
    LIMIT 10
");
$stmt->execute([$currentUser['id']]);
$paymentHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalPending = array_reduce($pendingInvoices, fn($sum, $inv) => $sum + $inv['amount'], 0);

// Handle payment processing
$paymentMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'process_payment') {
        $invoiceId = intval($_POST['invoice_id']);
        $amount = floatval($_POST['amount']);
        
        try {
            $handler = new RazorpayPaymentHandler();
            $order = $handler->createOrder($amount, 'INV-' . $invoiceId, 'Fee Payment');
            
            // Store order info in session
            $_SESSION['payment_order'] = [
                'order_id' => $order['id'],
                'invoice_id' => $invoiceId,
                'amount' => $amount
            ];
            
            $paymentMessage = 'Order created. Complete payment to proceed.';
        } catch (Exception $e) {
            $paymentMessage = 'Error: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'verify_payment') {
        $orderId = $_POST['razorpay_order_id'];
        $paymentId = $_POST['razorpay_payment_id'];
        $signature = $_POST['razorpay_signature'];
        
        try {
            $handler = new RazorpayPaymentHandler();
            if ($handler->verifyPayment($orderId, $paymentId, $signature)) {
                // Update invoice status
                $invoiceId = $_SESSION['payment_order']['invoice_id'] ?? null;
                if ($invoiceId) {
                    $stmt = $pdo->prepare("
                        UPDATE fee_invoices 
                        SET status = 'paid', paid_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$invoiceId]);
                    $paymentMessage = 'Payment successful! Invoice updated.';
                    unset($_SESSION['payment_order']);
                }
            } else {
                $paymentMessage = 'Payment verification failed. Please contact support.';
            }
        } catch (Exception $e) {
            $paymentMessage = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payment - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        .fee-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .fee-summary {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-card {
            text-align: center;
        }

        .summary-label {
            color: rgba(245, 244, 255, 0.6);
            font-size: 0.9rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: #6366F1;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .invoice-table th {
            background: rgba(99, 102, 241, 0.1);
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
            color: #F5F4FF;
            font-weight: 600;
        }

        .invoice-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            color: rgba(245, 244, 255, 0.9);
        }

        .invoice-table tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(249, 115, 22, 0.2);
            color: #FDBA74;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .status-overdue {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .pay-btn {
            padding: 10px 20px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .pay-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .pay-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 28px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-info {
            background: rgba(99, 102, 241, 0.1);
            border-color: #6366F1;
            color: rgba(245, 244, 255, 0.9);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: #10B981;
            color: #6EE7B7;
        }

        @media (max-width: 768px) {
            .fee-summary {
                grid-template-columns: 1fr;
            }

            .invoice-table {
                font-size: 0.85rem;
            }

            .invoice-table th,
            .invoice-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="student">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="fee-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Fee Payment</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Manage your fees and make online payments</p>
                </div>

                <?php if ($paymentMessage): ?>
                    <div class="alert alert-<?php echo strpos($paymentMessage, 'Error') === false ? 'success' : 'info'; ?>">
                        <?php echo htmlspecialchars($paymentMessage); ?>
                    </div>
                <?php endif; ?>

                <!-- Fee Summary -->
                <div class="fee-summary">
                    <div class="summary-card">
                        <div class="summary-label"><i class="bi bi-cash"></i> Total Pending</div>
                        <div class="summary-value">₹<?php echo number_format($totalPending, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label"><i class="bi bi-receipt"></i> Invoices</div>
                        <div class="summary-value"><?php echo count($pendingInvoices); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label"><i class="bi bi-check-circle"></i> Completed</div>
                        <div class="summary-value"><?php echo count($paymentHistory); ?></div>
                    </div>
                </div>

                <!-- Pending Invoices -->
                <h2 class="section-title">
                    <i class="bi bi-list-check"></i> Pending Invoices
                </h2>
                
                <?php if (count($pendingInvoices) > 0): ?>
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingInvoices as $invoice): 
                                $dueDate = $invoice['due_date'] ? new DateTime($invoice['due_date']) : null;
                                $today = new DateTime();
                                $isOverdue = $dueDate ? ($dueDate < $today) : false;
                                $statusClass = $isOverdue ? 'status-overdue' : 'status-pending';
                            ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($invoice['id']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['fee_name'] ?? 'General Fee'); ?></td>
                                    <td><strong>₹<?php echo number_format((float)$invoice['amount'], 2); ?></strong></td>
                                    <td><?php echo $invoice['due_date'] ? date('M d, Y', strtotime($invoice['due_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $isOverdue ? 'OVERDUE' : 'PENDING'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="process_payment">
                                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                            <input type="hidden" name="amount" value="<?php echo $invoice['amount']; ?>">
                                            <button type="submit" class="pay-btn" onclick="return initiatePayment(event)">
                                                Pay Now
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No pending invoices. All fees are up to date!
                    </div>
                <?php endif; ?>

                <!-- Payment History -->
                <h2 class="section-title">
                    <i class="bi bi-clock-history"></i> Payment History
                </h2>

                <?php if (count($paymentHistory) > 0): ?>
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentHistory as $history): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($history['id']); ?></td>
                                    <td><?php echo htmlspecialchars($history['fee_name'] ?? 'General Fee'); ?></td>
                                    <td><strong>₹<?php echo number_format($history['amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($history['payment_date'] ?? $history['created_at'] ?? 'now')); ?></td>
                                    <td>
                                        <span class="status-badge status-completed">
                                            COMPLETED
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No payment history yet.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function initiatePayment(event) {
            event.preventDefault();
            const form = event.target.closest('form');
            const invoiceId = form.querySelector('input[name="invoice_id"]').value;
            const amount = parseFloat(form.querySelector('input[name="amount"]').value) * 100; // Convert to paise

            const options = {
                key: '<?php echo env("RAZORPAY_KEY_ID"); ?>',
                amount: amount,
                currency: 'INR',
                name: 'EduCore - Fee Payment',
                description: 'Invoice #' + invoiceId,
                handler: function (response) {
                    // Verify payment on server
                    const verifyForm = document.createElement('form');
                    verifyForm.method = 'POST';
                    verifyForm.innerHTML = `
                        <input type="hidden" name="action" value="verify_payment">
                        <input type="hidden" name="razorpay_order_id" value="${response.razorpay_order_id}">
                        <input type="hidden" name="razorpay_payment_id" value="${response.razorpay_payment_id}">
                        <input type="hidden" name="razorpay_signature" value="${response.razorpay_signature}">
                    `;
                    document.body.appendChild(verifyForm);
                    verifyForm.submit();
                },
                prefill: {
                    name: '<?php echo htmlspecialchars($currentUser['name']); ?>',
                    email: '<?php echo htmlspecialchars($currentUser['email']); ?>'
                },
                theme: {
                    color: '#6366F1'
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
            return false;
        }
    </script>
</body>
</html>
