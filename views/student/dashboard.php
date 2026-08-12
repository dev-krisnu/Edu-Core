<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['student', 'parent']);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/FeeController.php';

$db = getDB();
$userId = $_SESSION['user_id'];
$courses = $db->query('SELECT * FROM courses LIMIT 6')->fetchAll();
$feeCtrl = new FeeController();
$invoices = $feeCtrl->getInvoices($userId);
$pendingFees = array_filter($invoices, fn($i) => $i['status'] === 'pending');

$pageTitle = 'Student Dashboard';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1>Welcome, <span class="gradient-text"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></span>!</h1>
    <p>Your academic hub — courses, exams, fees & AI tutor</p>
</div>

<div class="stat-grid fade-in">
    <div class="stat-card" style="--card-accent:#06b6d4">
        <div class="stat-card-icon" style="--icon-bg:rgba(6,182,212,0.1);--card-accent:#06b6d4"><i class="bi bi-book"></i></div>
        <div class="stat-card-value"><?= count($courses) ?></div>
        <div class="stat-card-label">Enrolled Courses</div>
    </div>
    <div class="stat-card" style="--card-accent:#ef4444">
        <div class="stat-card-icon" style="--icon-bg:rgba(239,68,68,0.1);--card-accent:#ef4444"><i class="bi bi-display"></i></div>
        <div class="stat-card-value">2</div>
        <div class="stat-card-label">Upcoming Exams</div>
    </div>
    <div class="stat-card" style="--card-accent:#f59e0b">
        <div class="stat-card-icon" style="--icon-bg:rgba(245,158,11,0.1);--card-accent:#f59e0b"><i class="bi bi-credit-card"></i></div>
        <div class="stat-card-value"><?= count($pendingFees) ?></div>
        <div class="stat-card-label">Pending Fees</div>
    </div>
    <div class="stat-card" style="--card-accent:#10b981">
        <div class="stat-card-icon" style="--icon-bg:rgba(16,185,129,0.1);--card-accent:#10b981"><i class="bi bi-graph-up"></i></div>
        <div class="stat-card-value">8.2</div>
        <div class="stat-card-label">Current CGPA</div>
    </div>
</div>

<div class="module-grid fade-in mb-4">
    <a href="exam_terminal.php" class="module-card" style="--module-color:#ef4444;--icon-bg:rgba(239,68,68,0.1)">
        <div class="module-icon"><i class="bi bi-display"></i></div>
        <h4>Exam Terminal</h4>
        <p>Proctored online exam room with tab-switch detection</p>
    </a>
    <a href="ai_tutor.php" class="module-card" style="--module-color:#ec4899;--icon-bg:rgba(236,72,153,0.1)">
        <div class="module-icon"><i class="bi bi-robot"></i></div>
        <h4>AI Tutor</h4>
        <p>24/7 context-aware academic assistant</p>
    </a>
    <a href="analytics.php" class="module-card" style="--module-color:#10b981;--icon-bg:rgba(16,185,129,0.1)">
        <div class="module-icon"><i class="bi bi-radar"></i></div>
        <h4>Remedial Analytics</h4>
        <p>Performance radar & targeted study materials</p>
    </a>
    <a href="fees.php" class="module-card" style="--module-color:#f59e0b;--icon-bg:rgba(245,158,11,0.1)">
        <div class="module-icon"><i class="bi bi-credit-card"></i></div>
        <h4>Fee Portal</h4>
        <p>View & pay semester fees online</p>
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3><i class="bi bi-book me-2" style="color:#8b5cf6"></i>My Courses</h3></div>
            <?php foreach ($courses as $c): ?>
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <div style="width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:14px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.75rem">
                    <?= htmlspecialchars($c['code']) ?>
                </div>
                <div class="flex-grow-1">
                    <strong><?= htmlspecialchars($c['title']) ?></strong>
                    <p class="text-muted mb-0" style="font-size:0.8rem"><?= htmlspecialchars($c['department']) ?> · <?= $c['credits'] ?> credits</p>
                </div>
                <div class="progress" style="width:80px;height:8px">
                    <div class="progress-bar" style="width:<?= rand(60,95) ?>%;background:linear-gradient(90deg,#06b6d4,#8b5cf6)"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3><i class="bi bi-radar me-2" style="color:#10b981"></i>Performance Radar</h3></div>
            <div class="chart-container"><canvas id="radarChart"></canvas></div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('radarChart'), {
    type: 'radar',
    data: {
        labels: ['DSA','ML','DSP','Math','English','Lab'],
        datasets: [{
            label: 'Your Score',
            data: [85, 72, 90, 78, 88, 95],
            borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,0.2)', borderWidth: 2
        }, {
            label: 'Class Avg',
            data: [70, 65, 75, 72, 80, 82],
            borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', borderWidth: 2
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { r: { beginAtZero: true, max: 100 } } }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
