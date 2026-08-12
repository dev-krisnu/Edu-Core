<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['finance']);
require_once __DIR__ . '/../../controllers/FeeController.php';

$feeCtrl = new FeeController();
$summary = $feeCtrl->getFinancialSummary();
$invoices = $feeCtrl->getInvoices();

$pageTitle = 'Finance Hub';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1>Finance <span class="gradient-text">Hub</span></h1>
    <p>Dynamic fee engine, invoicing, ledger & payroll management</p>
</div>

<div class="stat-grid fade-in">
    <div class="stat-card" style="--card-accent:#6366f1">
        <div class="stat-card-icon" style="--icon-bg:rgba(99,102,241,0.1);--card-accent:#6366f1"><i class="bi bi-cash-stack"></i></div>
        <div class="stat-card-value">₹<?= number_format($summary['total_billed']) ?></div>
        <div class="stat-card-label">Total Billed</div>
    </div>
    <div class="stat-card" style="--card-accent:#10b981">
        <div class="stat-card-icon" style="--icon-bg:rgba(16,185,129,0.1);--card-accent:#10b981"><i class="bi bi-check-circle"></i></div>
        <div class="stat-card-value">₹<?= number_format($summary['collected']) ?></div>
        <div class="stat-card-label">Collected</div>
        <div class="stat-card-trend up"><?= $summary['collection_rate'] ?>% collection rate</div>
    </div>
    <div class="stat-card" style="--card-accent:#f59e0b">
        <div class="stat-card-icon" style="--icon-bg:rgba(245,158,11,0.1);--card-accent:#f59e0b"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-card-value">₹<?= number_format($summary['pending']) ?></div>
        <div class="stat-card-label">Pending</div>
    </div>
    <div class="stat-card" style="--card-accent:#ef4444">
        <div class="stat-card-icon" style="--icon-bg:rgba(239,68,68,0.1);--card-accent:#ef4444"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="stat-card-value">₹<?= number_format($summary['overdue']) ?></div>
        <div class="stat-card-label">Overdue</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3><i class="bi bi-receipt me-2" style="color:#06b6d4"></i>Recent Invoices</h3></div>
            <table class="educore-table">
                <thead><tr><th>Student</th><th>Fee Type</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?= htmlspecialchars($inv['student_name']) ?></td>
                    <td><?= htmlspecialchars($inv['template_name'] ?? 'N/A') ?></td>
                    <td>₹<?= number_format($inv['amount'], 2) ?></td>
                    <td><span class="badge-status badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3>Collection Overview</h3></div>
            <div class="chart-container"><canvas id="financeChart"></canvas></div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('financeChart'), {
    type: 'doughnut',
    data: {
        labels: ['Collected','Pending','Overdue'],
        datasets: [{ data: [<?= $summary['collected'] ?>,<?= $summary['pending'] ?>,<?= $summary['overdue'] ?>],
            backgroundColor: ['#10b981','#f59e0b','#ef4444'], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
