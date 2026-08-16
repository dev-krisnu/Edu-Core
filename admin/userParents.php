<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['super_admin']);
$user = getCurrentUser();
$pageTitle = 'Parent Accounts';
$basePath = '..';
$pdo = getDbConnection();
$parents = $pdo->query("SELECT id, full_name, email, phone, status FROM users WHERE role = 'parent' ORDER BY full_name")->fetchAll();
if (empty($parents)) {
    $parents = [['full_name' => 'Demo Parent', 'email' => 'parent@educore.edu', 'phone' => '—', 'status' => 'active', 'id' => 0]];
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-people" style="color:#10b981"></i> Parent Accounts</h1><p>Monitor parent portal access</p></div>
<div class="content-card fade-in">
<?php foreach ($parents as $p): ?>
<div class="stat-card mb-3 animate-slide-in">
<strong><?= htmlspecialchars($p['full_name']) ?></strong><br>
<span class="text-muted"><?= htmlspecialchars($p['email']) ?> · <?= htmlspecialchars($p['phone'] ?? '—') ?></span>
</div>
<?php endforeach; ?>
<p class="text-muted mt-3">Parents can view child scorecards, attendance, fees, and PTM schedules.</p>
</div>
<?php include __DIR__ . '/../includes/footer.php';
