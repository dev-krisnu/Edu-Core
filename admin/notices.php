<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['super_admin']);
$user = getCurrentUser();
$pageTitle = 'Campus Notices';
$basePath = '..';
$pdo = getDbConnection();
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title && $content) {
        $stmt = $pdo->prepare('INSERT INTO notices (title, content, posted_by, priority, is_public) VALUES (?,?,?,?,?)');
        $stmt->execute([$title, $content, $user['id'], $_POST['priority'] ?? 'medium', 1]);
        logAction('Posted notice: ' . $title, 'admin');
        $flash = 'Notice published.';
    }
}
$notices = $pdo->query('SELECT n.*, u.full_name AS author FROM notices n LEFT JOIN users u ON n.posted_by = u.id ORDER BY n.created_at DESC LIMIT 20')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-megaphone" style="color:#3b82f6"></i> Notice Board</h1><p>Publish campus-wide announcements</p></div>
<?php if ($flash): ?><div class="alert alert-success fade-in"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="row">
<div class="col-lg-5"><div class="content-card fade-in">
<h3 class="mb-3">Post Notice</h3>
<form method="POST">
<input class="form-control mb-2" name="title" placeholder="Title" required>
<textarea class="form-control mb-2" name="content" rows="4" placeholder="Content" required></textarea>
<select class="form-select mb-3" name="priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select>
<button class="btn btn-primary w-100">Publish</button>
</form></div></div>
<div class="col-lg-7"><div class="content-card fade-in">
<?php foreach ($notices as $n): ?>
<div class="mb-3 pb-3 border-bottom">
<strong><?= htmlspecialchars($n['title']) ?></strong>
<span class="status-badge status-pending ms-2"><?= htmlspecialchars($n['priority']) ?></span>
<p class="mt-2 mb-1"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
<small class="text-muted"><?= htmlspecialchars($n['author'] ?? 'Admin') ?> · <?= date('M j, Y', strtotime($n['created_at'])) ?></small>
</div>
<?php endforeach; ?>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php';
