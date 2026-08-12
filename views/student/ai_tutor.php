<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['student']);

$pageTitle = 'AI Tutor';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-robot" style="color:#ec4899"></i> AI Tutor</h1>
    <p>Your 24/7 context-aware academic assistant</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="content-card fade-in" style="min-height:500px;display:flex;flex-direction:column">
            <div id="tutorMessages" class="ai-chat-messages flex-grow-1" style="height:400px">
                <div class="ai-message bot">
                    <div class="msg-avatar"><i class="bi bi-robot"></i></div>
                    <div class="msg-bubble">Hi! I'm your EduCore AI Tutor. Ask me anything about your courses, assignments, exam prep, or concepts you're struggling with!</div>
                </div>
            </div>
            <div class="ai-chat-input mt-3">
                <input type="text" id="tutorInput" class="form-control" placeholder="Ask about DSA, ML, exam tips...">
                <button class="btn btn-primary btn-send" onclick="askTutor()"><i class="bi bi-send-fill"></i></button>
            </div>
            <div class="d-flex gap-2 mt-3 flex-wrap">
                <?php foreach (['Explain binary trees', 'Help with ML concepts', 'Exam preparation tips', 'What are my pending fees?'] as $suggestion): ?>
                <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="document.getElementById('tutorInput').value='<?= $suggestion ?>';askTutor()"><?= $suggestion ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function askTutor() {
    const input = document.getElementById('tutorInput');
    const msgs = document.getElementById('tutorMessages');
    const text = input.value.trim();
    if (!text) return;

    msgs.innerHTML += `<div class="ai-message user"><div class="msg-avatar"><i class="bi bi-person"></i></div><div class="msg-bubble">${text}</div></div>`;
    input.value = '';
    msgs.scrollTop = msgs.scrollHeight;

    const loadId = 'load-' + Date.now();
    msgs.innerHTML += `<div class="ai-message bot" id="${loadId}"><div class="msg-avatar"><i class="bi bi-robot"></i></div><div class="msg-bubble">Thinking...</div></div>`;

    const res = await fetch('../../api/ai_helpdesk.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
    });
    const data = await res.json();
    document.getElementById(loadId).querySelector('.msg-bubble').textContent = data.response || 'Sorry, try again.';
    msgs.scrollTop = msgs.scrollHeight;
}
document.getElementById('tutorInput').addEventListener('keypress', e => { if (e.key === 'Enter') askTutor(); });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
