<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';

$db = getDB();
$totalUsers = $db->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$totalCourses = $db->query('SELECT COUNT(*) AS c FROM courses')->fetch()['c'];
$totalExams = $db->query('SELECT COUNT(*) AS c FROM exams')->fetch()['c'];
$recentLogs = $db->query('SELECT sl.*, u.full_name FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.created_at DESC LIMIT 8')->fetchAll();
$notices = $db->query('SELECT * FROM notices ORDER BY created_at DESC LIMIT 5')->fetchAll();

$pageTitle = 'Executive Command Center';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1>Executive <span class="gradient-text">Command Center</span></h1>
    <p>Real-time campus metrics, security tracking & system overview</p>
</div>

<div class="stat-grid fade-in">
    <div class="stat-card" style="--card-accent:#6366f1">
        <div class="stat-card-icon" style="--icon-bg:rgba(99,102,241,0.1);--card-accent:#6366f1"><i class="bi bi-people-fill"></i></div>
        <div class="stat-card-value"><?= $totalUsers ?></div>
        <div class="stat-card-label">Total Users</div>
        <div class="stat-card-trend up"><i class="bi bi-arrow-up"></i> Active accounts</div>
    </div>
    <div class="stat-card" style="--card-accent:#8b5cf6">
        <div class="stat-card-icon" style="--icon-bg:rgba(139,92,246,0.1);--card-accent:#8b5cf6"><i class="bi bi-book-fill"></i></div>
        <div class="stat-card-value"><?= $totalCourses ?></div>
        <div class="stat-card-label">Active Courses</div>
    </div>
    <div class="stat-card" style="--card-accent:#ef4444">
        <div class="stat-card-icon" style="--icon-bg:rgba(239,68,68,0.1);--card-accent:#ef4444"><i class="bi bi-clipboard-check"></i></div>
        <div class="stat-card-value"><?= $totalExams ?></div>
        <div class="stat-card-label">Exams Scheduled</div>
    </div>
    <div class="stat-card" style="--card-accent:#10b981">
        <div class="stat-card-icon" style="--icon-bg:rgba(16,185,129,0.1);--card-accent:#10b981"><i class="bi bi-shield-check"></i></div>
        <div class="stat-card-value">99.8%</div>
        <div class="stat-card-label">System Uptime</div>
        <div class="stat-card-trend up"><i class="bi bi-check-circle"></i> All systems operational</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="content-card fade-in">
            <div class="content-card-header">
                <h3><i class="bi bi-activity me-2" style="color:#6366f1"></i>System Activity</h3>
            </div>
            <div class="chart-container"><canvas id="activityChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3><i class="bi bi-megaphone me-2" style="color:#f59e0b"></i>Notices</h3></div>
            <?php foreach ($notices as $n): ?>
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <div class="flex-shrink-0">
                    <span class="badge-status badge-<?= $n['priority'] === 'high' ? 'overdue' : 'active' ?>"><?= ucfirst($n['priority']) ?></span>
                </div>
                <div>
                    <strong style="font-size:0.9rem"><?= htmlspecialchars($n['title']) ?></strong>
                    <p class="text-muted mb-0" style="font-size:0.8rem"><?= htmlspecialchars(substr($n['content'], 0, 60)) ?>...</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="content-card fade-in">
    <div class="content-card-header"><h3><i class="bi bi-journal-text me-2" style="color:#8b5cf6"></i>Recent System Logs</h3></div>
    <table class="educore-table">
        <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Time</th></tr></thead>
        <tbody>
        <?php foreach ($recentLogs as $log): ?>
        <tr>
            <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
            <td><?= htmlspecialchars($log['action']) ?></td>
            <td><span class="badge-status badge-active"><?= htmlspecialchars($log['module']) ?></span></td>
            <td class="text-muted"><?= date('M d, H:i', strtotime($log['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentLogs)): ?>
        <tr><td colspan="4" class="text-center text-muted">No logs yet</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
            label: 'Active Users',
            data: [120, 190, 170, 210, 180, 90, 60],
            borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)',
            fill: true, tension: 0.4, borderWidth: 3
        }, {
            label: 'Exam Sessions',
            data: [30, 45, 50, 60, 55, 20, 10],
            borderColor: '#ec4899', backgroundColor: 'rgba(236,72,153,0.1)',
            fill: true, tension: 0.4, borderWidth: 3
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
