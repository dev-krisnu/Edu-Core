<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'PTM Scheduler';
$basePath = '..';
$pdo = getDbConnection();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $topic = trim($_POST['topic'] ?? '');

    if ($date && $time && $studentId) {
        $ins = $pdo->prepare("
            INSERT INTO ptm_schedules (faculty_id, student_id, meeting_date, meeting_time, topic)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([$user['id'], $studentId, $date, $time, $topic]);
        $flash = 'PTM slot scheduled successfully.';
        logAction('Scheduled PTM', 'faculty');
    } else {
        $flash = 'Please select a student and fill in date/time.';
    }
}

// Students this faculty member teaches (via course enrollment), so the
// dropdown only lists real students rather than free-text names that
// could never be matched back to an account.
$students = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name
    FROM users u
    JOIN course_enrollments ce ON ce.student_id = u.id
    JOIN courses c ON c.id = ce.course_id
    WHERE c.faculty_id = ?
    ORDER BY u.full_name
");
$students->execute([$user['id']]);
$students = $students->fetchAll(PDO::FETCH_ASSOC);
// Fallback: if this faculty has no enrolled students yet, let them pick any student
if (empty($students)) {
    $students = $pdo->query("SELECT id, full_name FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
}

$meetings = $pdo->prepare("
    SELECT ptm.*, u.full_name AS student_name, p.full_name AS parent_name
    FROM ptm_schedules ptm
    JOIN users u ON u.id = ptm.student_id
    LEFT JOIN users p ON p.id = u.parent_id
    WHERE ptm.faculty_id = ?
    ORDER BY ptm.meeting_date, ptm.meeting_time
");
$meetings->execute([$user['id']]);
$meetings = $meetings->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-people" style="color:#6366f1"></i> PTM Scheduler</h1><p>Parent-teacher meeting appointments</p></div>
<?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="row">
<div class="col-lg-5"><div class="content-card fade-in">
<h3 class="mb-3">Schedule Meeting</h3>
<form method="POST">
<input class="form-control mb-2" type="date" name="date" required>
<input class="form-control mb-2" type="time" name="time" required>
<select class="form-control form-select mb-2" name="student_id" required>
<option value="">Select student</option>
<?php foreach ($students as $s): ?>
<option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
<?php endforeach; ?>
</select>
<input class="form-control mb-3" name="topic" placeholder="Topic (e.g. Academic progress)">
<button class="btn btn-primary w-100">Schedule</button>
</form></div></div>
<div class="col-lg-7"><div class="content-card fade-in">
<h3 class="mb-3">Upcoming Meetings</h3>
<?php if (empty($meetings)): ?>
<p class="text-muted">No meetings scheduled yet.</p>
<?php endif; ?>
<?php foreach ($meetings as $m): ?>
<div class="stat-card mb-2"><strong><?= htmlspecialchars($m['meeting_date']) ?> at <?= htmlspecialchars(date('g:i A', strtotime($m['meeting_time']))) ?></strong><br>
<?= htmlspecialchars($m['parent_name'] ?? 'No parent linked') ?> · <?= htmlspecialchars($m['student_name']) ?>
<?php if ($m['topic']): ?><br><span class="text-muted"><?= htmlspecialchars($m['topic']) ?></span><?php endif; ?>
<span class="status-badge status-<?= $m['status'] === 'confirmed' ? 'active' : 'pending' ?> float-end"><?= htmlspecialchars(ucfirst($m['status'])) ?></span></div>
<?php endforeach; ?>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php';
