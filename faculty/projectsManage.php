<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Student Projects';
$basePath = '..';
$pdo = getDbConnection();
$projects = $pdo->query("SELECT u.full_name, u.email FROM users u WHERE u.role='student' LIMIT 10")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-kanban" style="color:#a855f7"></i> Student Projects</h1><p>Review and grade submitted projects</p></div>
<div class="grid">
<?php foreach ($projects as $i => $p): $statuses = ['Submitted', 'Under Review', 'Approved']; $st = $statuses[$i % 3]; ?>
<div class="stat-card animate-slide-in">
<h5><?= htmlspecialchars($p['full_name']) ?></h5>
<p class="text-muted mb-2"><?= htmlspecialchars($p['email']) ?></p>
<p>Project: <strong>Capstone Module <?= ($i % 4) + 1 ?></strong></p>
<span class="status-badge status-<?= $st === 'Approved' ? 'active' : 'pending' ?>"><?= $st ?></span>
<button class="btn btn-sm btn-outline-primary mt-2">Review</button>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
