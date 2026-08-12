<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['student']);
require_once __DIR__ . '/../../controllers/AIController.php';

$pageTitle = 'Remedial Analytics';
$basePath = '../..';
$analysis = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ai = new AIController();
    $scores = [
        'DSA' => 85, 'Machine Learning' => 72, 'Digital Signal Processing' => 90,
        'Mathematics' => 78, 'English' => 88, 'Lab Work' => 95
    ];
    $analysis = $ai->remedialAnalysis($scores);
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-radar" style="color:#10b981"></i> Remedial Analytics</h1>
    <p>Multi-dimensional performance mapping with AI-driven study recommendations</p>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3>Performance Radar</h3></div>
            <div class="chart-container"><canvas id="perfRadar"></canvas></div>
        </div>
        <form method="POST" class="mt-3">
            <button type="submit" class="btn btn-gradient w-100">
                <i class="bi bi-stars me-2"></i>Generate AI Remedial Plan
            </button>
        </form>
    </div>
    <div class="col-lg-7">
        <div class="content-card fade-in">
            <div class="content-card-header"><h3>AI Study Recommendations</h3></div>
            <?php if ($analysis): ?>
            <div class="p-4 rounded-3" style="background:linear-gradient(135deg,rgba(16,185,129,0.05),rgba(6,182,212,0.05));border-left:4px solid #10b981">
                <?= nl2br(htmlspecialchars($analysis)) ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-radar" style="font-size:3rem;opacity:0.3"></i>
                <p class="mt-3">Click "Generate AI Remedial Plan" to get personalized study recommendations based on your performance data</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-card fade-in mt-3">
            <div class="content-card-header"><h3>Weak Concepts Identified</h3></div>
            <div class="d-flex gap-3 flex-wrap">
                <?php foreach ([['ML','72%','#f59e0b'],['Math','78%','#ef4444'],['DSA','85%','#10b981']] as [$subject, $score, $color]): ?>
                <div class="p-3 rounded-3 text-center" style="background:<?= $color ?>10;border:1px solid <?= $color ?>30;min-width:120px">
                    <div style="font-size:1.5rem;font-weight:800;color:<?= $color ?>"><?= $score ?></div>
                    <div style="font-size:0.85rem;font-weight:600"><?= $subject ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('perfRadar'), {
    type: 'radar',
    data: {
        labels: ['DSA','ML','DSP','Math','English','Lab'],
        datasets: [{ label: 'Score', data: [85,72,90,78,88,95], borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.2)', borderWidth: 2 }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { r: { beginAtZero: true, max: 100 } } }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
