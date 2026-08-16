<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['tpo']);
require_once __DIR__ . '/../../controllers/AIController.php';

$pageTitle = 'AI Resume Matcher';
$basePath = '..';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ai = new AIController();
    $result = $ai->analyzeResume($_POST['resume'], $_POST['job_description']);
    logAction('AI resume match performed', 'placements');
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-file-person" style="color:#ec4899"></i> AI Resume Matcher</h1>
    <p>Automated fitment scoring against job descriptions</p>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="content-card fade-in">
            <h3 class="mb-3">Student Resume</h3>
            <form method="POST">
                <textarea name="resume" class="form-control mb-3" rows="10" placeholder="Paste student resume text..." required><?= htmlspecialchars($_POST['resume'] ?? '') ?></textarea>
                <h3 class="mb-3">Job Description</h3>
                <textarea name="job_description" class="form-control mb-3" rows="8" placeholder="Paste job description..." required><?= htmlspecialchars($_POST['job_description'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-gradient w-100"><i class="bi bi-stars me-2"></i>Analyze Fitment</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3>Fitment Analysis</h3></div>
            <?php if ($result): ?>
            <div class="text-center mb-4">
                <?php $score = $result['fitment_score'] ?? 0; $color = $score >= 70 ? '#10b981' : ($score >= 40 ? '#f59e0b' : '#ef4444'); ?>
                <div style="font-size:4rem;font-weight:800;color:<?= $color ?>"><?= $score ?>%</div>
                <div class="badge-status" style="background:<?= $color ?>20;color:<?= $color ?>;font-size:1rem;padding:8px 20px">
                    <?= $score >= 70 ? 'Strong Match' : ($score >= 40 ? 'Moderate Match' : 'Weak Match') ?>
                </div>
            </div>
            <?php if (!empty($result['matching_skills'])): ?>
            <h5 class="mb-2">Matching Skills</h5>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <?php foreach ((array)$result['matching_skills'] as $skill): ?>
                <span class="badge-status badge-paid"><?= htmlspecialchars($skill) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($result['gaps'])): ?>
            <h5 class="mb-2">Skill Gaps</h5>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <?php foreach ((array)$result['gaps'] as $gap): ?>
                <span class="badge-status badge-overdue"><?= htmlspecialchars($gap) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="text-muted"><?= htmlspecialchars($result['recommendation'] ?? '') ?></p>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-person" style="font-size:3rem;opacity:0.3"></i>
                <p class="mt-3">Paste a resume and job description to get AI-powered fitment analysis</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
