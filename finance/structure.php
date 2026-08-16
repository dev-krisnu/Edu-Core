<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['finance']);
$user = getCurrentUser();
$pageTitle = 'Fee Structure';
$basePath = '..';
$pdo = getDbConnection();
$templates = $pdo->query('SELECT * FROM fee_templates ORDER BY category')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-wallet2" style="color:#ec4899"></i> Fee Structure</h1><p>Campus fee templates and penalty rules</p></div>
<div class="grid">
<?php foreach ($templates as $t): ?>
<div class="stat-card animate-slide-in">
<h4><?= htmlspecialchars($t['name']) ?></h4>
<p class="text-muted text-uppercase" style="font-size:.8rem"><?= htmlspecialchars($t['category']) ?></p>
<h3 style="color:var(--theme-primary)">₹<?= number_format((float)$t['amount'], 2) ?></h3>
<p>Penalty: <?= $t['penalty_percent'] ?>% · Due: <?= $t['due_date'] ? date('M j, Y', strtotime($t['due_date'])) : '—' ?></p>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
