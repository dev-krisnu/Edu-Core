<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['finance']);
$user = getCurrentUser();
$pageTitle = 'Pay Slips';
$basePath = '..';
$staff = [
    ['name' => 'Finance Officer', 'month' => 'February 2026', 'net' => 48500],
    ['name' => 'Library Manager', 'month' => 'February 2026', 'net' => 89000],
    ['name' => 'Mr. Lakhan Mahato', 'month' => 'February 2026', 'net' => 62000],
];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-person-badge" style="color:#10b981"></i> Pay Slips</h1><p>Staff payroll summaries</p></div>
<div class="grid">
<?php foreach ($staff as $s): ?>
<div class="stat-card animate-slide-in">
<h4><?= htmlspecialchars($s['name']) ?></h4>
<p><?= htmlspecialchars($s['month']) ?></p>
<h3>₹<?= number_format((float)$s['net']) ?></h3>
<button class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-download me-1"></i>Download PDF</button>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
