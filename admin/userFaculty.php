<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['super_admin']);
$user = getCurrentUser();
$pageTitle = 'Faculty Accounts';
$basePath = '..';
$pdo = getDbConnection();
$faculty = $pdo->query("SELECT id, full_name, email, phone, status, created_at FROM users WHERE role = 'faculty' ORDER BY full_name")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-person-workspace" style="color:#8b5cf6"></i> Faculty Management</h1><p>View and manage faculty accounts</p></div>
<div class="content-card fade-in">
<table class="table table-hover">
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th></tr></thead>
<tbody>
<?php foreach ($faculty as $f): ?>
<tr>
<td><?= htmlspecialchars($f['full_name']) ?></td>
<td><?= htmlspecialchars($f['email']) ?></td>
<td><?= htmlspecialchars($f['phone'] ?? '—') ?></td>
<td><span class="status-badge status-<?= $f['status'] === 'active' ? 'active' : 'inactive' ?>"><?= htmlspecialchars($f['status']) ?></span></td>
<td><?= date('M j, Y', strtotime($f['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php include __DIR__ . '/../includes/footer.php';
