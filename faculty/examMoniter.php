<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Exam Monitor';
$basePath = '..';
$pdo = getDbConnection();
$exams = $pdo->query("SELECT e.*, c.title AS course_title FROM exams e LEFT JOIN courses c ON e.course_id = c.id WHERE e.status IN ('scheduled','active') ORDER BY e.start_time ASC")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-camera-video" style="color:#ef4444"></i> Live Exam Monitor</h1><p>Track proctored sessions in real time</p></div>
<div class="grid">
<?php foreach ($exams as $e): ?>
<div class="stat-card animate-slide-in">
<h4><?= htmlspecialchars($e['title']) ?></h4>
<p class="text-muted"><?= htmlspecialchars($e['course_title'] ?? 'General') ?></p>
<p>Duration: <?= (int)$e['duration_minutes'] ?> min · Marks: <?= (int)$e['total_marks'] ?></p>
<span class="status-badge status-<?= $e['status'] === 'active' ? 'active' : 'pending' ?>"><?= htmlspecialchars($e['status']) ?></span>
<?php if ($e['proctored']): ?><span class="status-badge status-error ms-2"><i class="bi bi-shield-lock"></i> Proctored</span><?php endif; ?>
</div>
<?php endforeach; ?>
<?php if (empty($exams)): ?><p class="text-muted">No active exams scheduled.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
