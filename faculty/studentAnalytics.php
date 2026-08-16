<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Student Analytics';
$basePath = '..';
$pdo = getDbConnection();
$students = $pdo->query("SELECT full_name, email FROM users WHERE role='student' LIMIT 8")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-bar-chart" style="color:#10b981"></i> Student Analytics</h1><p>Performance overview across your courses</p></div>
<div class="content-card fade-in mb-4"><canvas id="perfChart" height="120"></canvas></div>
<div class="content-card fade-in">
<table class="table"><thead><tr><th>Student</th><th>Email</th><th>Avg Score</th></tr></thead><tbody>
<?php foreach ($students as $i => $s): $score = 65 + ($i * 3) % 30; ?>
<tr><td><?= htmlspecialchars($s['full_name']) ?></td><td><?= htmlspecialchars($s['email']) ?></td><td><div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= $score ?>%"></div></div> <?= $score ?>%</td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<script>
new Chart(document.getElementById('perfChart'),{type:'radar',data:{labels:['Theory','Lab','Projects','Attendance','Exams'],datasets:[{label:'Class Avg',data:[78,82,70,88,75],borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,.2)'}]},options:{plugins:{legend:{labels:{color:'#64748b'}}},scales:{r:{ticks:{color:'#94a3b8'},grid:{color:'rgba(0,0,0,.08)'}}}}});
</script>
<?php include __DIR__ . '/../includes/footer.php';
