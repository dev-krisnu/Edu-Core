<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['super_admin']);
$user = getCurrentUser();
$pageTitle = 'Permissions';
$basePath = '..';
$roles = [
    'super_admin' => ['users', 'courses', 'exams', 'finance', 'logs', 'settings'],
    'faculty' => ['exams', 'grading', 'attendance', 'ai_tools'],
    'student' => ['exams', 'fees', 'library', 'projects'],
    'finance' => ['invoices', 'reports', 'transactions'],
    'librarian' => ['circulation', 'catalog', 'fines'],
    'tpo' => ['placements', 'resumes', 'drives'],
    'parent' => ['scorecard', 'alerts', 'fees_view'],
];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-shield-check" style="color:#8b5cf6"></i> Role Permissions</h1><p>RBAC matrix for EduCore modules</p></div>
<div class="content-card fade-in">
<table class="table">
<thead><tr><th>Role</th><th>Allowed Modules</th></tr></thead>
<tbody>
<?php foreach ($roles as $role => $mods): ?>
<tr><td><span class="badge-role"><?= htmlspecialchars(getRoleLabel($role)) ?></span></td><td><?= htmlspecialchars(implode(', ', $mods)) ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php include __DIR__ . '/../includes/footer.php';
