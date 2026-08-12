<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['faculty']);
require_once __DIR__ . '/../../controllers/ExamController.php';

$examCtrl = new ExamController();
$exams = $examCtrl->getExams();
$db = getDB();
$courses = $db->query('SELECT * FROM courses')->fetchAll();

$pageTitle = 'Faculty Workspace';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1>Faculty <span class="gradient-text">Workspace</span></h1>
    <p>Manage courses, AI question generation, exams & grading</p>
</div>

<div class="module-grid fade-in mb-4">
    <a href="ai_questions.php" class="module-card" style="--module-color:#ec4899;--icon-bg:rgba(236,72,153,0.1)">
        <div class="module-icon"><i class="bi bi-robot"></i></div>
        <h4>AI Question Setter</h4>
        <p>Generate exam questions using Bloom's Taxonomy & AI</p>
    </a>
    <a href="exams.php" class="module-card" style="--module-color:#ef4444;--icon-bg:rgba(239,68,68,0.1)">
        <div class="module-icon"><i class="bi bi-display"></i></div>
        <h4>Proctored Exams</h4>
        <p>Create & manage proctored online examinations</p>
    </a>
    <a href="plagiarism.php" class="module-card" style="--module-color:#f59e0b;--icon-bg:rgba(245,158,11,0.1)">
        <div class="module-icon"><i class="bi bi-search"></i></div>
        <h4>Plagiarism Inspector</h4>
        <p>Code AST matching & text similarity analysis</p>
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="content-card fade-in">
            <div class="content-card-header">
                <h3><i class="bi bi-clipboard-check me-2" style="color:#ef4444"></i>Recent Exams</h3>
                <a href="exams.php" class="btn btn-outline-gradient btn-sm">View All</a>
            </div>
            <table class="educore-table">
                <thead><tr><th>Title</th><th>Course</th><th>Duration</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($exams, 0, 5) as $e): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
                    <td><?= htmlspecialchars($e['course_title'] ?? 'N/A') ?></td>
                    <td><?= $e['duration_minutes'] ?> min</td>
                    <td><span class="badge-status badge-<?= $e['status'] === 'active' ? 'active' : 'pending' ?>"><?= ucfirst($e['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($exams)): ?>
                <tr><td colspan="4" class="text-center text-muted">No exams yet. Create one with AI Question Setter!</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3><i class="bi bi-book me-2" style="color:#8b5cf6"></i>My Courses</h3></div>
            <?php foreach ($courses as $c): ?>
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <div style="width:44px;height:44px;background:rgba(139,92,246,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#8b5cf6;font-weight:700">
                    <?= htmlspecialchars($c['code']) ?>
                </div>
                <div>
                    <strong style="font-size:0.9rem"><?= htmlspecialchars($c['title']) ?></strong>
                    <p class="text-muted mb-0" style="font-size:0.8rem"><?= $c['credits'] ?> credits · <?= htmlspecialchars($c['department']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
