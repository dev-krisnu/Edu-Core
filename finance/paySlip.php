<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['finance']);
$user = getCurrentUser();
$pageTitle = 'Pay Slips';
$basePath = '..';
$pdo = getDbConnection();

$staff = $pdo->query("
    SELECT p.id, p.pay_month, p.basic, p.allowances, p.deductions, p.net_pay,
           u.full_name, u.role
    FROM payroll p
    JOIN users u ON u.id = p.staff_id
    ORDER BY p.pay_month DESC, u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-person-badge" style="color:#10b981"></i> Pay Slips</h1><p>Staff payroll summaries</p></div>
<div class="grid">
<?php if (empty($staff)): ?>
<p class="text-muted">No payroll records yet.</p>
<?php endif; ?>
<?php foreach ($staff as $s): ?>
<div class="stat-card animate-slide-in">
<h4><?= htmlspecialchars($s['full_name']) ?></h4>
<p><?= htmlspecialchars((new DateTime($s['pay_month']))->format('F Y')) ?> · <span class="text-muted" style="text-transform:capitalize;"><?= htmlspecialchars(str_replace('_',' ',$s['role'])) ?></span></p>
<h3>₹<?= number_format((float)$s['net_pay']) ?></h3>
<a href="downloadPayslip.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-download me-1"></i>Download PDF</a>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
