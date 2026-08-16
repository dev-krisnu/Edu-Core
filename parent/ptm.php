<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['parent']);
$user = getCurrentUser();
$pageTitle = 'Parent-Teacher Meet';
$basePath = '..';
$meetings = [
    ['date' => '2026-03-20', 'time' => '10:00 AM', 'faculty' => 'Mr. Lakhan Mahato', 'topic' => 'Academic progress', 'status' => 'Confirmed'],
    ['date' => '2026-04-05', 'time' => '11:30 AM', 'faculty' => 'Course Advisor', 'topic' => 'Semester review', 'status' => 'Open'],
];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-people" style="color:#3b82f6"></i> Parent-Teacher Meetings</h1><p>Scheduled and upcoming PTM sessions</p></div>
<div class="grid">
<?php foreach ($meetings as $m): ?>
<div class="stat-card animate-slide-in">
<h4><?= htmlspecialchars($m['date']) ?> · <?= htmlspecialchars($m['time']) ?></h4>
<p>Faculty: <strong><?= htmlspecialchars($m['faculty']) ?></strong></p>
<p>Topic: <?= htmlspecialchars($m['topic']) ?></p>
<span class="status-badge status-<?= $m['status']==='Confirmed'?'active':'pending' ?>"><?= $m['status'] ?></span>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
