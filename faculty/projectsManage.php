<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Student Projects';
$basePath = '..';
$pdo = getDbConnection();

// Handle status update / feedback (Review action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'update_status') {
    $submissionId = (int) ($_POST['submission_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $feedback = trim($_POST['feedback'] ?? '');
    if ($submissionId && in_array($newStatus, ['submitted', 'under_review', 'approved', 'rejected'], true)) {
        $upd = $pdo->prepare("UPDATE project_submissions SET status = ?, feedback = ?, graded_by = ? WHERE id = ?");
        $upd->execute([$newStatus, $feedback, $user['id'], $submissionId]);
    }
    header('Location: projectsManage.php');
    exit;
}

// Real submissions from students - courses this faculty member teaches,
// or ungrouped ones with no course match, so nothing submitted is hidden.
$projects = $pdo->prepare("
    SELECT ps.*, u.full_name AS student_name, u.email AS student_email, c.title AS course_title
    FROM project_submissions ps
    JOIN users u ON u.id = ps.student_id
    LEFT JOIN courses c ON c.id = ps.course_id
    WHERE ps.course_id IS NULL OR c.faculty_id = ?
    ORDER BY ps.submitted_at DESC
    LIMIT 30
");
$projects->execute([$user['id']]);
$projects = $projects->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-kanban" style="color:#a855f7"></i> Student Projects</h1><p>Review and grade submitted projects</p></div>
<div class="grid">
<?php if (empty($projects)): ?>
<p class="text-muted">No project submissions yet for your courses.</p>
<?php endif; ?>
<?php foreach ($projects as $p): ?>
<div class="stat-card animate-slide-in">
<h5><?= htmlspecialchars($p['student_name']) ?></h5>
<p class="text-muted mb-2"><?= htmlspecialchars($p['student_email']) ?></p>
<p>Project: <strong><?= htmlspecialchars($p['title']) ?></strong><?php if ($p['course_title']): ?> — <?= htmlspecialchars($p['course_title']) ?><?php endif; ?></p>
<?php if ($p['description']): ?><p class="text-muted" style="font-size:0.85rem;"><?= htmlspecialchars($p['description']) ?></p><?php endif; ?>
<a href="<?= htmlspecialchars($basePath . '/uploads/' . $p['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-download"></i> View File</a>
<br>
<span class="status-badge status-<?= $p['status'] === 'approved' ? 'active' : 'pending' ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $p['status']))) ?></span>
<form method="POST" class="mt-2 d-flex gap-2" style="flex-wrap:wrap;">
<input type="hidden" name="action" value="update_status">
<input type="hidden" name="submission_id" value="<?= (int)$p['id'] ?>">
<select name="status" class="form-select form-input" style="max-width:160px;">
<option value="submitted" <?= $p['status']==='submitted'?'selected':'' ?>>Submitted</option>
<option value="under_review" <?= $p['status']==='under_review'?'selected':'' ?>>Under Review</option>
<option value="approved" <?= $p['status']==='approved'?'selected':'' ?>>Approved</option>
<option value="rejected" <?= $p['status']==='rejected'?'selected':'' ?>>Rejected</option>
</select>
<button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
</form>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
