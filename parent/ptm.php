<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['parent']);
$user = getCurrentUser();
$pageTitle = 'Parent-Teacher Meet';
$basePath = '..';
$pdo = getDbConnection();

// Find this parent's linked child/children, then their meetings
$meetings = $pdo->prepare("
    SELECT ptm.*, f.full_name AS faculty_name, s.full_name AS student_name
    FROM ptm_schedules ptm
    JOIN users s ON s.id = ptm.student_id
    JOIN users f ON f.id = ptm.faculty_id
    WHERE s.parent_id = ?
    ORDER BY ptm.meeting_date, ptm.meeting_time
");
$meetings->execute([$user['id']]);
$meetings = $meetings->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-people" style="color:#3b82f6"></i> Parent-Teacher Meetings</h1><p>Scheduled and upcoming PTM sessions</p></div>
<div class="grid">
<?php if (empty($meetings)): ?>
<p class="text-muted">No meetings scheduled yet.</p>
<?php endif; ?>
<?php foreach ($meetings as $m): ?>
<div class="stat-card animate-slide-in">
<h4><?= htmlspecialchars($m['meeting_date']) ?> · <?= htmlspecialchars(date('g:i A', strtotime($m['meeting_time']))) ?></h4>
<p>Faculty: <strong><?= htmlspecialchars($m['faculty_name']) ?></strong></p>
<p>Student: <?= htmlspecialchars($m['student_name']) ?></p>
<?php if ($m['topic']): ?><p>Topic: <?= htmlspecialchars($m['topic']) ?></p><?php endif; ?>
<span class="status-badge status-<?= $m['status']==='confirmed'?'active':'pending' ?>"><?= htmlspecialchars(ucfirst($m['status'])) ?></span>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
