<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['super_admin']);
$user = getCurrentUser();
$pageTitle = 'Infrastructure';
$basePath = '..';
$blocks = [
    ['name' => 'Main Academic Block', 'labs' => 12, 'classrooms' => 48, 'status' => 'Operational'],
    ['name' => 'Computer Science Block', 'labs' => 6, 'classrooms' => 18, 'status' => 'Operational'],
    ['name' => 'Library & Digital Lab', 'labs' => 2, 'classrooms' => 4, 'status' => 'Operational'],
    ['name' => 'Workshop & ME Block', 'labs' => 8, 'classrooms' => 22, 'status' => 'Maintenance'],
];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-building" style="color:#6366f1"></i> Campus Infrastructure</h1><p>Buildings, labs, and facility status</p></div>
<div class="grid grid-2">
<?php foreach ($blocks as $b): ?>
<div class="stat-card animate-slide-in">
<h4><?= htmlspecialchars($b['name']) ?></h4>
<p class="mb-1">Labs: <strong><?= $b['labs'] ?></strong> · Classrooms: <strong><?= $b['classrooms'] ?></strong></p>
<span class="status-badge status-<?= $b['status'] === 'Operational' ? 'active' : 'pending' ?>"><?= htmlspecialchars($b['status']) ?></span>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
