<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['faculty']);
require_once __DIR__ . '/../../controllers/AIController.php';

$pageTitle = 'AI Question Setter';
$basePath = '../..';
$generatedQuestions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ai = new AIController();
    $generatedQuestions = $ai->generateQuestions(
        $_POST['topic'], $_POST['syllabus'],
        (int)$_POST['count'], $_POST['difficulty'], $_POST['bloom_level']
    );
    logAction('AI questions generated for: ' . $_POST['topic'], 'ai');
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-robot" style="color:#ec4899"></i> AI Question Setter</h1>
    <p>Automated exam generation using Bloom's Taxonomy & difficulty scaling</p>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="content-card fade-in">
            <h3 class="mb-4">Generate Questions</h3>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Topic</label>
                    <input type="text" name="topic" class="form-control" placeholder="e.g. Binary Search Trees" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Syllabus / Context</label>
                    <textarea name="syllabus" class="form-control" rows="3" placeholder="Cover insertion, deletion, traversal..."></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Count</label>
                        <select name="count" class="form-select">
                            <?php for ($i = 3; $i <= 15; $i += 2): ?>
                            <option value="<?= $i ?>" <?= $i === 5 ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Difficulty</label>
                        <select name="difficulty" class="form-select">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Bloom's Taxonomy Level</label>
                    <select name="bloom_level" class="form-select">
                        <?php foreach (['Remember','Understand','Apply','Analyze','Evaluate','Create'] as $level): ?>
                        <option value="<?= $level ?>" <?= $level === 'Apply' ? 'selected' : '' ?>><?= $level ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-gradient w-100">
                    <i class="bi bi-stars me-2"></i>Generate with AI
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="content-card fade-in">
            <div class="content-card-header">
                <h3>Generated Questions</h3>
                <?php if ($generatedQuestions): ?>
                <span class="badge-status badge-active"><?= count($generatedQuestions) ?> questions</span>
                <?php endif; ?>
            </div>
            <?php if ($generatedQuestions): ?>
                <?php foreach ($generatedQuestions as $i => $q): ?>
                <div class="p-3 mb-3 rounded-3" style="background:#f8faff;border-left:4px solid #ec4899">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Q<?= $i + 1 ?></strong>
                        <span>
                            <span class="badge-status badge-pending"><?= htmlspecialchars($q['question_type'] ?? 'short') ?></span>
                            <span class="badge-status badge-active"><?= $q['marks'] ?? 5 ?> marks</span>
                        </span>
                    </div>
                    <p class="mb-1"><?= htmlspecialchars($q['question_text'] ?? '') ?></p>
                    <?php if (!empty($q['options']) && is_array($q['options'])): ?>
                    <ul class="mb-0" style="font-size:0.85rem">
                        <?php foreach ($q['options'] as $opt): ?>
                        <li><?= htmlspecialchars($opt) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-stars" style="font-size:3rem;opacity:0.3"></i>
                <p class="mt-3">Fill in the form and click Generate to create AI-powered exam questions</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
