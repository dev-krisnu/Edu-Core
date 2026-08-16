<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['super_admin']);
$user = getCurrentUser();
$pageTitle = 'Institute Settings';
$basePath = '..';
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash = 'Settings saved (demo — persist via database in production).';
    logAction('Updated institute settings', 'admin');
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-gear" style="color:#a855f7"></i> Institute Settings</h1><p>Campus profile and system preferences</p></div>
<?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="content-card fade-in" style="max-width:640px">
<form method="POST">
<div class="mb-3"><label class="form-label">Institute Name</label><input class="form-control" name="name" value="Dr. B.C. Roy Engineering College"></div>
<div class="mb-3"><label class="form-label">Campus Location</label><input class="form-control" name="location" value="Durgapur, West Bengal"></div>
<div class="mb-3"><label class="form-label">Academic Year</label><input class="form-control" name="year" value="2025-2026"></div>
<div class="mb-3"><label class="form-label">AI Provider</label><select class="form-select" name="ai"><option>Gemini (Free Tier)</option><option>Ollama (Local)</option></select></div>
<button class="btn btn-primary">Save Settings</button>
</form>
</div>
<?php include __DIR__ . '/../includes/footer.php';
