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
            <div id="qrCameraWrap" style="display:none; margin-bottom:16px;">
                <video id="qrVideo" style="width:100%; max-width:420px; border-radius:12px; border:2px solid rgba(6,182,212,0.4);" muted playsinline></video>
                <canvas id="qrCanvas" style="display:none;"></canvas>
                <div id="qrCameraStatus" class="opacity-75 mt-2" style="font-size:0.9rem;">Point the camera at a book's QR code...</div>
            </div>

            <i class="bi bi-qr-code-scan" id="qrIcon" style="font-size:4rem;opacity:0.5"></i>
            <h3 class="mt-3 mb-2">Scan Book QR Code</h3>
            <p class="opacity-75 mb-4">Use webcam scanner or type QR code manually</p>

            <div class="d-flex gap-3 justify-content-center mb-3">
                <button class="btn btn-outline-info" id="qrCameraToggle" onclick="toggleCamera()"><i class="bi bi-camera-video me-2"></i>Start Webcam Scanner</button>
            </div>

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

<!-- jsQR: lightweight, dependency-free pure-JS QR decoder (MIT license).
     No QR/webcam scanning library existed anywhere in this project. -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
let qrStream = null;
let qrScanLoop = null;

async function toggleCamera() {
    const wrap = document.getElementById('qrCameraWrap');
    const btn = document.getElementById('qrCameraToggle');

    if (qrStream) {
        stopCamera();
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Your browser does not support camera access, or this page is not loaded over HTTPS/localhost (camera access requires a secure context).');
        return;
    }

    try {
        qrStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    } catch (err) {
        alert('Could not access the camera: ' + err.message + '\nYou can still type the QR code manually below.');
        return;
    }

    const video = document.getElementById('qrVideo');
    video.srcObject = qrStream;
    await video.play();
    wrap.style.display = 'block';
    document.getElementById('qrIcon').style.display = 'none';
    btn.innerHTML = '<i class="bi bi-camera-video-off me-2"></i>Stop Webcam Scanner';

    const canvas = document.getElementById('qrCanvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    function tick() {
        if (!qrStream) return;
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
            if (code && code.data) {
                document.getElementById('qrInput').value = code.data;
                document.getElementById('qrCameraStatus').textContent = 'Detected: ' + code.data;
                scanAction('lookup');
                stopCamera();
                return;
            }
        }
        qrScanLoop = requestAnimationFrame(tick);
    }
    qrScanLoop = requestAnimationFrame(tick);
}

function stopCamera() {
    if (qrScanLoop) cancelAnimationFrame(qrScanLoop);
    if (qrStream) {
        qrStream.getTracks().forEach(t => t.stop());
        qrStream = null;
    }
    document.getElementById('qrCameraWrap').style.display = 'none';
    document.getElementById('qrIcon').style.display = 'inline-block';
    document.getElementById('qrCameraToggle').innerHTML = '<i class="bi bi-camera-video me-2"></i>Start Webcam Scanner';
}

async function scanAction(action) {
    const qr = document.getElementById('qrInput').value.trim();
    if (!qr) { alert('Enter a QR code'); return; }
    const res = await fetch('../api/scan_qr_library.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ qr_code: qr, action, student_id: document.getElementById('studentIdInput').value })
    });
    if (!res.ok) {
        document.getElementById('qrResult').innerHTML = '<div class="alert alert-danger mt-3">Server error - please try again.</div>';
        return;
    }
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
