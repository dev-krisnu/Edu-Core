<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['finance']);
$user = getCurrentUser();
$pageTitle = 'Transactions';
$basePath = '..';
$pdo = getDbConnection();
$txns = $pdo->query("SELECT fi.*, u.full_name, ft.name AS fee_name FROM fee_invoices fi JOIN users u ON fi.student_id = u.id LEFT JOIN fee_templates ft ON fi.template_id = ft.id ORDER BY fi.created_at DESC LIMIT 25")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-arrow-left-right" style="color:#3b82f6"></i> Transactions</h1><p>Fee payment ledger</p></div>
<div class="content-card fade-in">
<table class="table table-hover"><thead><tr><th>Student</th><th>Fee</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach ($txns as $t): ?>
<tr>
<td><?= htmlspecialchars($t['full_name']) ?></td>
<td><?= htmlspecialchars($t['fee_name'] ?? '—') ?></td>
<td>₹<?= number_format((float)$t['amount'], 2) ?></td>
<td><span class="status-badge status-<?= $t['status']==='paid'?'active':($t['status']==='pending'?'pending':'error') ?>"><?= htmlspecialchars($t['status']) ?></span></td>
<td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php include __DIR__ . '/../includes/footer.php';
