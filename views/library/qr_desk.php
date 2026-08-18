<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['librarian']);

$pageTitle = 'QR Circulation Desk';
$basePath = '..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-qr-code-scan" style="color:#06b6d4"></i> QR Circulation Desk</h1>
    <p>Scan or enter QR codes for instant book issue & return</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="qr-scanner-box fade-in">
            <i class="bi bi-qr-code-scan" style="font-size:4rem;opacity:0.5"></i>
            <h3 class="mt-3 mb-2">Scan Book QR Code</h3>
            <p class="opacity-75 mb-4">Use webcam scanner or type QR code manually</p>

            <input type="text" id="qrInput" class="qr-input" placeholder="Scan or type QR code (e.g. QR-EJ-001)" autofocus>
            <input type="number" id="studentIdInput" class="qr-input mt-3" min="1" placeholder="Student ID (required to issue)">

            <div class="d-flex gap-3 justify-content-center mt-3">
                <button class="btn btn-light" onclick="scanAction('lookup')"><i class="bi bi-search me-2"></i>Lookup</button>
                <button class="btn btn-success" onclick="scanAction('issue')"><i class="bi bi-box-arrow-right me-2"></i>Issue</button>
                <button class="btn btn-warning" onclick="scanAction('return')"><i class="bi bi-box-arrow-in-left me-2"></i>Return</button>
            </div>

            <div id="qrResult" class="mt-4"></div>
        </div>

        <div class="content-card fade-in mt-4">
            <div class="content-card-header"><h3>Quick Reference — Demo QR Codes</h3></div>
            <div class="d-flex gap-3 flex-wrap">
                <?php foreach (['QR-CS101-01','QR-CS202-01','QR-EC101-01','QR-MA101-01'] as $qr): ?>
                <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('qrInput').value='<?= $qr ?>';scanAction('lookup')"><?= $qr ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function scanAction(action) {
    const qr = document.getElementById('qrInput').value.trim();
    if (!qr) { alert('Enter a QR code'); return; }
    const res = await fetch('../../api/scan_qr_library.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ qr_code: qr, action, student_id: document.getElementById('studentIdInput').value })
    });
    const data = await res.json();
    const el = document.getElementById('qrResult');
    if (data.success) {
        el.innerHTML = `<div class="alert alert-success mt-3">${data.message}${data.book ? '<br><strong>'+data.book.title+'</strong> by '+data.book.author : ''}</div>`;
    } else {
        el.innerHTML = `<div class="alert alert-danger mt-3">${data.error}</div>`;
    }
    if (action !== 'lookup') document.getElementById('qrInput').value = '';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
