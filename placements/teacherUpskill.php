<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../controllers/AIController.php';
requireRole(['tpo']);
$user = getCurrentUser();
$pageTitle = 'Teacher Upskill';
$basePath = '..';
$plan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ai = new AIController();
    $topic = trim($_POST['topic'] ?? 'Industry trends');
    $plan = $ai->helpdeskResponse("Create a 4-week upskill plan for faculty on: {$topic}. List weekly goals.", 'Role: TPO');
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-graph-up-arrow" style="color:#a855f7"></i> Teacher Upskill Program</h1><p>AI-generated faculty development roadmaps</p></div>
<div class="row">
<div class="col-lg-5"><div class="content-card fade-in">
<form method="POST">
<label class="form-label">Upskill Topic</label>
<input class="form-control mb-3" name="topic" placeholder="e.g. Cloud Computing, GenAI in Education" value="<?= htmlspecialchars($_POST['topic'] ?? '') ?>" required>
<button class="btn btn-gradient w-100"><i class="bi bi-stars me-2"></i>Generate Plan</button>
</form></div></div>
<div class="col-lg-7"><div class="content-card fade-in">
<h3 class="mb-3">Development Roadmap</h3>
<?php if ($plan): ?><div style="font-size:1.05rem;line-height:1.7;white-space:pre-wrap"><?= htmlspecialchars($plan) ?></div>
<?php else: ?><p class="text-muted">Enter a topic to generate an AI-powered upskilling plan for faculty.</p><?php endif; ?>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php';
