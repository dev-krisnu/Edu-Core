<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['parent']);
$user = getCurrentUser();
$pageTitle = 'Attendance';
$basePath = '..';
$pdo = getDbConnection();
$childStmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE parent_id = ? AND role = 'student' LIMIT 1");
$childStmt->execute([$user['id']]);
$child = $childStmt->fetch();
$records = [];
if ($child) {
    $recordsStmt = $pdo->prepare('SELECT attendance_date AS date, status FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC LIMIT 30');
    $recordsStmt->execute([$child['id']]);
    $records = $recordsStmt->fetchAll();
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-calendar2-check" style="color:#8b5cf6"></i> Attendance</h1>
<p><?= $child ? 'Tracking: ' . htmlspecialchars($child['full_name']) : 'Child attendance overview' ?></p></div>
<div class="content-card fade-in">
<table class="table"><thead><tr><th>Date</th><th>Status</th></tr></thead><tbody>
<?php foreach ($records as $r): ?>
<tr><td><?= htmlspecialchars((string) $r['date']) ?></td><td><span class="status-badge status-<?= in_array(strtolower($r['status']), ['present', 'late', 'excused'], true) ? 'active' : 'error' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td></tr>
<?php endforeach; ?>
</tbody></table>
<p class="text-muted mt-2">Overall attendance: <strong><?= $records ? round((count(array_filter($records, fn($record) => in_array($record['status'], ['present', 'late'], true))) / count($records)) * 100) : 0 ?>%</strong></p>
</div>
<?php include __DIR__ . '/../includes/footer.php';
