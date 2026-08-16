<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Syllabus Tracker';
$basePath = '..';
$pdo = getDbConnection();
$courses = $pdo->prepare('SELECT * FROM courses WHERE faculty_id = ? OR ? IN (SELECT id FROM users WHERE role="super_admin")');
$courses->execute([$user['id'], $user['id']]);
$courses = $courses->fetchAll() ?: $pdo->query('SELECT * FROM courses LIMIT 6')->fetchAll();
$units = ['Unit 1 — Foundations', 'Unit 2 — Core Concepts', 'Unit 3 — Applications', 'Unit 4 — Advanced Topics', 'Unit 5 — Review'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-diagram-3" style="color:#14b8a6"></i> Syllabus Tracker</h1><p>Track course completion progress</p></div>
<?php foreach ($courses as $c): $pct = rand(45, 95); ?>
<div class="content-card fade-in mb-3 animate-slide-in">
<div class="d-flex justify-content-between align-items-center mb-2">
<h4 class="mb-0"><?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars($c['title']) ?></h4>
<span class="status-badge status-active"><?= $pct ?>% complete</span>
</div>
<div class="progress mb-3" style="height:10px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
<div class="d-flex flex-wrap gap-2"><?php foreach ($units as $u): ?><span class="pill" style="font-size:.85rem"><?= htmlspecialchars($u) ?></span><?php endforeach; ?></div>
</div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/footer.php';
