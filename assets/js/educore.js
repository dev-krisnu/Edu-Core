// EduCore Global JavaScript

function openAIHelpdesk() {
    const modal = new bootstrap.Modal(document.getElementById('aiHelpdeskModal'));
    modal.show();
}

async function sendAIMessage() {
    const input = document.getElementById('aiChatInput');
    const messages = document.getElementById('aiChatMessages');
    const text = input.value.trim();
    if (!text) return;

    messages.innerHTML += `
        <div class="ai-message user">
            <div class="msg-avatar"><i class="bi bi-person"></i></div>
            <div class="msg-bubble">${escapeHtml(text)}</div>
        </div>`;
    input.value = '';

    const loadingId = 'loading-' + Date.now();
    messages.innerHTML += `
        <div class="ai-message bot" id="${loadingId}">
            <div class="msg-avatar"><i class="bi bi-robot"></i></div>
            <div class="msg-bubble"><i class="bi bi-three-dots"></i> Thinking...</div>
        </div>`;
    messages.scrollTop = messages.scrollHeight;

    try {
        const basePath = document.querySelector('script[src*="educore.js"]')?.src.replace('/assets/js/educore.js', '') || '';
        const res = await fetch(basePath + '/api/ai_helpdesk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        });
        const data = await res.json();
        document.getElementById(loadingId).querySelector('.msg-bubble').textContent =
            data.response || data.error || 'Sorry, I could not process that request.';
    } catch (e) {
        document.getElementById(loadingId).querySelector('.msg-bubble').textContent =
            'AI service is currently unavailable. Please try again later.';
    }
    messages.scrollTop = messages.scrollHeight;
}

document.getElementById('aiChatInput')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendAIMessage();
});

document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Proctoring: tab switch detection for exam terminal
let tabSwitchCount = 0;
if (document.querySelector('.exam-terminal')) {
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            tabSwitchCount++;
            const warning = document.getElementById('proctorWarning');
            if (warning) {
                warning.style.display = 'block';
                warning.textContent = `⚠ Tab switch detected! (${tabSwitchCount} violation${tabSwitchCount > 1 ? 's' : ''})`;
            }
        }
    });
}

// Exam timer
function startExamTimer(minutes, displayId) {
    let seconds = minutes * 60;
    const el = document.getElementById(displayId);
    if (!el) return;
    const interval = setInterval(() => {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        if (--seconds < 0) {
            clearInterval(interval);
            el.textContent = 'TIME UP';
            alert('Exam time is over! Your answers will be auto-submitted.');
        }
    }, 1000);
}

// QR Scanner input handler
function initQRScanner(inputId, resultId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
            const qr = input.value.trim();
            if (!qr) return;
            const basePath = document.querySelector('script[src*="educore.js"]')?.src.replace('/assets/js/educore.js', '') || '';
            try {
                const res = await fetch(basePath + '/api/scan_qr_library.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ qr_code: qr, action: 'lookup' })
                });
                const data = await res.json();
                const resultEl = document.getElementById(resultId);
                if (resultEl) {
                    resultEl.innerHTML = data.success
                        ? `<div class="alert alert-success">${data.message}<br><strong>${data.book?.title}</strong> by ${data.book?.author}</div>`
                        : `<div class="alert alert-danger">${data.error}</div>`;
                }
            } catch (err) {
                console.error(err);
            }
            input.value = '';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initQRScanner('qrInput', 'qrResult');
});
