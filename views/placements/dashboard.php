<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['tpo']);
require_once __DIR__ . '/../../config/database.php';

$db = getDB();
$drives = $db->query('SELECT * FROM placement_drives ORDER BY drive_date')->fetchAll();
$applications = $db->query('SELECT COUNT(*) AS c FROM placement_applications')->fetch()['c'];

$pageTitle = 'Placement Hub';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1>Placement <span class="gradient-text">Hub</span></h1>
    <p>Company drives, AI resume matching & interview timeline management</p>
</div>

<div class="stat-grid fade-in">
    <div class="stat-card" style="--card-accent:#6366f1">
        <div class="stat-card-value"><?= count($drives) ?></div>
        <div class="stat-card-label">Active Drives</div>
    </div>
    <div class="stat-card" style="--card-accent:#06b6d4">
        <div class="stat-card-value"><?= $applications ?></div>
        <div class="stat-card-label">Applications</div>
    </div>
    <div class="stat-card" style="--card-accent:#10b981">
        <div class="stat-card-value">12 LPA</div>
        <div class="stat-card-label">Highest Package</div>
    </div>
    <div class="stat-card" style="--card-accent:#ec4899">
        <div class="stat-card-value">85%</div>
        <div class="stat-card-label">Placement Rate</div>
    </div>
</div>

<div class="module-grid fade-in mb-4">
    <a href="resume_matcher.php" class="module-card" style="--module-color:#ec4899;--icon-bg:rgba(236,72,153,0.1)">
        <div class="module-icon"><i class="bi bi-file-person"></i></div>
        <h4>AI Resume Matcher</h4>
        <p>Parse resumes against JD for automated fitment scores</p>
    </a>
    <a href="drives.php" class="module-card" style="--module-color:#06b6d4;--icon-bg:rgba(6,182,212,0.1)">
        <div class="module-icon"><i class="bi bi-building"></i></div>
        <h4>Company Drives</h4>
        <p>Registration, eligibility filtering & scheduling</p>
    </a>
</div>

<div class="content-card fade-in">
    <div class="content-card-header"><h3><i class="bi bi-briefcase me-2" style="color:#6366f1"></i>Upcoming Placement Drives</h3></div>
    <table class="educore-table">
        <thead><tr><th>Company</th><th>Role</th><th>Min CGPA</th><th>Package (LPA)</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($drives as $d): ?>
        <tr>
            <td><strong><?= htmlspecialchars($d['company_name']) ?></strong></td>
            <td><?= htmlspecialchars($d['job_title']) ?></td>
            <td><?= $d['min_cgpa'] ?></td>
            <td>₹<?= number_format($d['package_lpa'], 1) ?></td>
            <td><?= date('M d, Y', strtotime($d['drive_date'])) ?></td>
            <td><span class="badge-status badge-<?= $d['status'] === 'upcoming' ? 'upcoming' : 'active' ?>"><?= ucfirst($d['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
