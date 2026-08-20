<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['faculty']);

$pageTitle = 'Plagiarism Inspector';
$basePath = '..';
$result = null;

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-search" style="color:#ef4444"></i> Plagiarism Inspector</h1>
    <p>Code AST matching, text similarity & AI-generated content detection</p>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="content-card fade-in">
            <h3 class="mb-3">Submission A</h3>
            <textarea id="text1" class="form-control" rows="8" placeholder="Paste first submission (text or code)..."></textarea>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="content-card fade-in">
            <h3 class="mb-3">Submission B (for comparison)</h3>
            <textarea id="text2" class="form-control" rows="8" placeholder="Paste second submission for comparison..."></textarea>
        </div>
    </div>
</div>

<div class="d-flex gap-3 mb-4 fade-in">
    <button class="btn btn-gradient" onclick="runCheck('text')"><i class="bi bi-file-text me-2"></i>Text Similarity</button>
    <button class="btn btn-outline-gradient" onclick="runCheck('code')"><i class="bi bi-code-slash me-2"></i>Code AST Match</button>
    <button class="btn btn-outline-gradient" onclick="runCheck('ai_detect')"><i class="bi bi-robot me-2"></i>AI Detection</button>
</div>

<div id="resultPanel" class="content-card fade-in" style="display:none">
    <div class="content-card-header"><h3>Analysis Result</h3></div>
    <div id="resultContent"></div>
</div>

<script>
async function runCheck(type) {
    const text1 = document.getElementById('text1').value;
    const text2 = document.getElementById('text2').value;
    if (!text1) { alert('Please paste content in Submission A'); return; }

    const res = await fetch('../api/check_plagiarism.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type, text1, text2 })
    });
    if (!res.ok) {
        alert('Plagiarism check failed (server error). Please try again.');
        return;
    }
    const data = await res.json();
    if (data.error) {
        alert('Error: ' + data.error);
        return;
    }
    const r = data.result;
    const panel = document.getElementById('resultPanel');
    panel.style.display = 'block';

    let html = '';
    if (type === 'ai_detect') {
        const color = r.ai_probability > 60 ? '#ef4444' : r.ai_probability > 30 ? '#f59e0b' : '#10b981';
        html = `<div class="text-center mb-4">
            <div style="font-size:3rem;font-weight:800;color:${color}">${r.ai_probability}%</div>
            <div class="badge-status" style="background:${color}20;color:${color}">${r.verdict}</div>
        </div>`;
        if (r.indicators?.length) {
            html += '<ul>' + r.indicators.map(i => `<li>${i}</li>`).join('') + '</ul>';
        }
    } else {
        const color = r.similarity >= 50 ? '#ef4444' : r.similarity >= 25 ? '#f59e0b' : '#10b981';
        html = `<div class="text-center mb-4">
            <div style="font-size:3rem;font-weight:800;color:${color}">${r.similarity}%</div>
            <div class="badge-status" style="background:${color}20;color:${color}">${r.verdict}</div>
            ${r.method ? `<p class="text-muted mt-2">Method: ${r.method}</p>` : ''}
        </div>`;
    }
    document.getElementById('resultContent').innerHTML = html;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
