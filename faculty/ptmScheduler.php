<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'PTM Scheduler';
$basePath = '..';
$flash = '';
$meetings = [
    ['date' => '2026-03-20', 'time' => '10:00 AM', 'parent' => 'Mr. Sharma', 'student' => 'Krrish Jeswar', 'status' => 'Confirmed'],
    ['date' => '2026-03-22', 'time' => '2:30 PM', 'parent' => 'Mrs. Shaw', 'student' => 'Komal Shaw', 'status' => 'Pending'],
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash = 'PTM slot scheduled successfully.';
    logAction('Scheduled PTM', 'faculty');
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-people" style="color:#6366f1"></i> PTM Scheduler</h1><p>Parent-teacher meeting appointments</p></div>
<?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="row">
<div class="col-lg-5"><div class="content-card fade-in">
<h3 class="mb-3">Schedule Meeting</h3>
<form method="POST">
<input class="form-control mb-2" type="date" name="date" required>
<input class="form-control mb-2" type="time" name="time" required>
<input class="form-control mb-2" name="parent" placeholder="Parent name" required>
<input class="form-control mb-3" name="student" placeholder="Student name" required>
<button class="btn btn-primary w-100">Schedule</button>
</form></div></div>
<div class="col-lg-7"><div class="content-card fade-in">
<h3 class="mb-3">Upcoming Meetings</h3>
<?php foreach ($meetings as $m): ?>
<div class="stat-card mb-2"><strong><?= htmlspecialchars($m['date']) ?> at <?= htmlspecialchars($m['time']) ?></strong><br>
<?= htmlspecialchars($m['parent']) ?> · <?= htmlspecialchars($m['student']) ?>
<span class="status-badge status-<?= $m['status'] === 'Confirmed' ? 'active' : 'pending' ?> float-end"><?= $m['status'] ?></span></div>
<?php endforeach; ?>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php';
