<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['parent']);
$user = getCurrentUser();
$pageTitle = 'Attendance';
$basePath = '..';
$pdo = getDbConnection();
$child = $pdo->query("SELECT full_name, email FROM users WHERE role='student' LIMIT 1")->fetch();
$records = [['date'=>'2026-03-01','status'=>'Present'],['date'=>'2026-03-02','status'=>'Present'],['date'=>'2026-03-03','status'=>'Absent'],['date'=>'2026-03-04','status'=>'Present'],['date'=>'2026-03-05','status'=>'Present']];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-calendar2-check" style="color:#8b5cf6"></i> Attendance</h1>
<p><?= $child ? 'Tracking: ' . htmlspecialchars($child['full_name']) : 'Child attendance overview' ?></p></div>
<div class="content-card fade-in">
<table class="table"><thead><tr><th>Date</th><th>Status</th></tr></thead><tbody>
<?php foreach ($records as $r): ?>
<tr><td><?= htmlspecialchars($r['date']) ?></td><td><span class="status-badge status-<?= $r['status']==='Present'?'active':'error' ?>"><?= $r['status'] ?></span></td></tr>
<?php endforeach; ?>
</tbody></table>
<p class="text-muted mt-2">Overall attendance: <strong>92%</strong></p>
</div>
<?php include __DIR__ . '/../includes/footer.php';
