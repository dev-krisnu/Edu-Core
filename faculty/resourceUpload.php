<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Resource Upload';
$basePath = '..';
$pdo = getDbConnection();
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file']['name'])) {
    $dir = dirname(__DIR__) . '/uploads/resources';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['file']['name']));
    $filename = time() . '_' . $safe;
    $dest = $dir . '/' . $filename;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $title = trim($_POST['title'] ?? $safe);
        $courseId = !empty($_POST['course_id']) ? (int) $_POST['course_id'] : null;
        $ins = $pdo->prepare("INSERT INTO resources (title, course_id, uploaded_by, file_path) VALUES (?, ?, ?, ?)");
        $ins->execute([$title, $courseId, $user['id'], 'resources/' . $filename]);
        logAction('Uploaded resource: ' . $safe, 'faculty');
        $flash = 'Resource uploaded successfully.';
    } else {
        $flash = 'Upload failed.';
    }
}

$courses = $pdo->prepare("SELECT id, code, title FROM courses WHERE faculty_id = ? ORDER BY code");
$courses->execute([$user['id']]);
$courses = $courses->fetchAll(PDO::FETCH_ASSOC);

$myResources = $pdo->prepare("
    SELECT r.*, c.title AS course_title FROM resources r
    LEFT JOIN courses c ON c.id = r.course_id
    WHERE r.uploaded_by = ? ORDER BY r.created_at DESC LIMIT 20
");
$myResources->execute([$user['id']]);
$myResources = $myResources->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-cloud-upload" style="color:#0ea5e9"></i> Resource Upload</h1><p>Share lecture notes, slides, and materials</p></div>
<?php if ($flash): ?><div class="alert alert-success fade-in"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="content-card fade-in" style="max-width:560px">
<form method="POST" enctype="multipart/form-data">
<div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" placeholder="Lecture 5 — DBMS Normalization" required></div>
<div class="mb-3"><label class="form-label">Course</label>
<select class="form-control form-select" name="course_id">
<option value="">General (no specific course)</option>
<?php foreach ($courses as $c): ?>
<option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['code'] . ' — ' . $c['title']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3"><label class="form-label">File (PDF, PPT, ZIP)</label><input class="form-control" type="file" name="file" required></div>
<button class="btn btn-primary w-100"><i class="bi bi-upload me-2"></i>Upload</button>
</form>
</div>

<h3 class="mt-4 mb-3">Your Uploaded Resources (<?= count($myResources) ?>)</h3>
<div class="content-card fade-in" style="max-width:560px">
<?php if (empty($myResources)): ?>
<p class="text-muted mb-0">No resources uploaded yet.</p>
<?php endif; ?>
<?php foreach ($myResources as $r): ?>
<div class="stat-card mb-2">
<strong><?= htmlspecialchars($r['title']) ?></strong>
<?php if ($r['course_title']): ?><span class="text-muted"> · <?= htmlspecialchars($r['course_title']) ?></span><?php endif; ?>
<br>
<a href="<?= htmlspecialchars($basePath . '/uploads/' . $r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-1"><i class="bi bi-download"></i> Download</a>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
