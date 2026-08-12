<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['student']);
require_once __DIR__ . '/../../controllers/FeeController.php';

$feeCtrl = new FeeController();
$invoices = $feeCtrl->getInvoices($_SESSION['user_id']);

$pageTitle = 'Fee Portal';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-credit-card" style="color:#f59e0b"></i> Fee Portal</h1>
    <p>View invoices, payment status & fee breakdown</p>
</div>

<div class="stat-grid fade-in">
    <?php
    $total = array_sum(array_column($invoices, 'amount'));
    $paid = array_sum(array_map(fn($i) => $i['status'] === 'paid' ? $i['amount'] : 0, $invoices));
    $pending = $total - $paid;
    ?>
    <div class="stat-card" style="--card-accent:#6366f1">
        <div class="stat-card-value">₹<?= number_format($total) ?></div>
        <div class="stat-card-label">Total Billed</div>
    </div>
    <div class="stat-card" style="--card-accent:#10b981">
        <div class="stat-card-value">₹<?= number_format($paid) ?></div>
        <div class="stat-card-label">Paid</div>
    </div>
    <div class="stat-card" style="--card-accent:#ef4444">
        <div class="stat-card-value">₹<?= number_format($pending) ?></div>
        <div class="stat-card-label">Pending</div>
    </div>
</div>

<div class="content-card fade-in">
    <div class="content-card-header"><h3>My Invoices</h3></div>
    <table class="educore-table">
        <thead><tr><th>Fee Type</th><th>Category</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
            <td><strong><?= htmlspecialchars($inv['template_name'] ?? 'Fee') ?></strong></td>
            <td><span class="badge-status badge-active"><?= ucfirst($inv['category'] ?? 'misc') ?></span></td>
            <td>₹<?= number_format($inv['amount'], 2) ?></td>
            <td><span class="badge-status badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
            <td>
                <?php if ($inv['status'] === 'pending'): ?>
                <button class="btn btn-gradient btn-sm">Pay Now</button>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
