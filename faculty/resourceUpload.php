<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Resource Upload';
$basePath = '..';
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file']['name'])) {
    $dir = dirname(__DIR__) . '/uploads/resources';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['file']['name']));
    $dest = $dir . '/' . time() . '_' . $safe;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        logAction('Uploaded resource: ' . $safe, 'faculty');
        $flash = 'Resource uploaded successfully.';
    } else {
        $flash = 'Upload failed.';
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-cloud-upload" style="color:#0ea5e9"></i> Resource Upload</h1><p>Share lecture notes, slides, and materials</p></div>
<?php if ($flash): ?><div class="alert alert-success fade-in"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="content-card fade-in" style="max-width:560px">
<form method="POST" enctype="multipart/form-data">
<div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" placeholder="Lecture 5 — DBMS Normalization" required></div>
<div class="mb-3"><label class="form-label">File (PDF, PPT, ZIP)</label><input class="form-control" type="file" name="file" required></div>
<button class="btn btn-primary w-100"><i class="bi bi-upload me-2"></i>Upload</button>
</form>
</div>
<?php include __DIR__ . '/../includes/footer.php';
