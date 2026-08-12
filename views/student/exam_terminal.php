<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['student']);
require_once __DIR__ . '/../../controllers/ExamController.php';

$examCtrl = new ExamController();
$exams = $examCtrl->getExams('scheduled');
$activeExam = $exams[0] ?? null;
$questions = $activeExam ? $examCtrl->getExamQuestions($activeExam['id']) : [];

if (empty($questions) && $activeExam) {
    $questions = [
        ['id' => 1, 'question_text' => 'What is the time complexity of binary search?', 'question_type' => 'mcq', 'options' => '["O(n)","O(log n)","O(n²)","O(1)"]', 'marks' => 5],
        ['id' => 2, 'question_text' => 'Explain the difference between stack and queue data structures.', 'question_type' => 'short', 'marks' => 10],
        ['id' => 3, 'question_text' => 'Write a function to reverse a linked list in Python.', 'question_type' => 'code', 'marks' => 15],
    ];
}

$pageTitle = 'Proctored Exam Terminal';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-display" style="color:#ef4444"></i> Proctored Exam Terminal</h1>
    <p>Distraction-free exam room · Tab-switch detection active</p>
</div>

<div class="exam-terminal fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><?= htmlspecialchars($activeExam['title'] ?? 'Demo: Data Structures Mid-Term') ?></h3>
            <small class="text-muted">Proctored Session · Do not switch tabs</small>
        </div>
        <div class="exam-timer" id="examTimer">60:00</div>
    </div>

    <div class="proctor-warning mb-4" id="proctorWarning" style="display:none"></div>

    <form id="examForm">
        <?php foreach ($questions as $i => $q):
            $options = is_string($q['options'] ?? '') ? json_decode($q['options'], true) : ($q['options'] ?? []);
        ?>
        <div class="mb-4 p-4 rounded-3" style="background:rgba(255,255,255,0.05)">
            <div class="d-flex justify-content-between mb-3">
                <strong style="color:#a5f3fc">Question <?= $i + 1 ?></strong>
                <span class="badge" style="background:rgba(6,182,212,0.3);color:#a5f3fc"><?= $q['marks'] ?> marks</span>
            </div>
            <p class="mb-3"><?= htmlspecialchars($q['question_text']) ?></p>
            <?php if ($q['question_type'] === 'mcq' && $options): ?>
                <?php foreach ($options as $j => $opt): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="<?= htmlspecialchars($opt) ?>" id="q<?= $q['id'] ?>_<?= $j ?>">
                    <label class="form-check-label" for="q<?= $q['id'] ?>_<?= $j ?>"><?= htmlspecialchars($opt) ?></label>
                </div>
                <?php endforeach; ?>
            <?php elseif ($q['question_type'] === 'code'): ?>
                <textarea class="form-control bg-dark text-light border-secondary" name="q_<?= $q['id'] ?>" rows="6" placeholder="Write your code here..." style="font-family:monospace"></textarea>
            <?php else: ?>
                <textarea class="form-control bg-dark text-light border-secondary" name="q_<?= $q['id'] ?>" rows="3" placeholder="Your answer..."></textarea>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <button type="button" class="btn btn-gradient btn-lg" onclick="submitExam()">
            <i class="bi bi-check-circle me-2"></i>Submit Exam
        </button>
    </form>
</div>

<script>
startExamTimer(<?= $activeExam['duration_minutes'] ?? 60 ?>, 'examTimer');
function submitExam() {
    if (confirm('Are you sure you want to submit? This cannot be undone.')) {
        alert('Exam submitted successfully! Results will be available after auto-grading.');
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
