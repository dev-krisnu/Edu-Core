<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['parent']);
$user = getCurrentUser();
$pageTitle = 'Fee Status';
$basePath = '..';
$pdo = getDbConnection();
$invoices = $pdo->query("SELECT fi.*, ft.name AS fee_name FROM fee_invoices fi LEFT JOIN fee_templates ft ON fi.template_id = ft.id ORDER BY fi.created_at DESC LIMIT 10")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-credit-card" style="color:#f59e0b"></i> Fee Status</h1><p>View your child's fee invoices and payments</p></div>
<div class="content-card fade-in">
<table class="table"><thead><tr><th>Fee</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach ($invoices as $inv): ?>
<tr>
<td><?= htmlspecialchars($inv['fee_name'] ?? 'Fee') ?></td>
<td>₹<?= number_format((float)$inv['amount'], 2) ?></td>
<td><span class="status-badge status-<?= $inv['status']==='paid'?'active':'pending' ?>"><?= htmlspecialchars($inv['status']) ?></span></td>
<td><?= date('M j, Y', strtotime($inv['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php include __DIR__ . '/../includes/footer.php';
