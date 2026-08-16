<?php
/**
 * Help Desk - Student Support System
 * Ticket-based support with AI chatbot assistance
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/GeminiAI.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch support tickets
$stmt = $pdo->prepare("
    SELECT * FROM system_logs 
    WHERE user_id = ? AND action LIKE 'support_%'
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$currentUser['id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX request for AI chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['question'])) {
    header('Content-Type: application/json');

    $question = trim($_POST['question']);
    $category = trim($_POST['category'] ?? 'General');

    if (empty($question)) {
        echo json_encode(['success' => false, 'error' => 'Question required']);
        exit;
    }

    try {
        $ai = AIFactory::create();
        $response = $ai->helpdeskResponse($question, 'student');

        echo json_encode([
            'success' => true,
            'response' => $response,
            'timestamp' => date('M d, H:i')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Support service temporarily unavailable'
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Desk - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .helpdesk-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .chat-section {
            display: flex;
            flex-direction: column;
            height: 700px;
        }

        .chat-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .chat-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
            padding: 16px;
        }

        .chat-header h2 {
            margin: 0;
            color: #F5F4FF;
            font-size: 1.1rem;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message {
            display: flex;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .message.student {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 80%;
            padding: 12px;
            border-radius: 10px;
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .message.student .message-content {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #F5F4FF;
        }

        .message.support .message-content {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(212, 180, 254, 0.1));
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #F5F4FF;
        }

        .chat-input-area {
            border-top: 1px solid rgba(99, 102, 241, 0.2);
            padding: 12px;
            background: rgba(255, 255, 255, 0.02);
            display: flex;
            gap: 8px;
        }

        .chat-input-area input {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
        }

        .chat-input-area input::placeholder {
            color: rgba(245, 244, 255, 0.4);
        }

        .chat-input-area input:focus {
            outline: none;
            border-color: #6366F1;
        }

        .send-btn {
            padding: 10px 16px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .send-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        .tickets-section {
            display: flex;
            flex-direction: column;
        }

        .tickets-header {
            margin-bottom: 16px;
        }

        .tickets-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 680px;
            overflow-y: auto;
        }

        .ticket-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ticket-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateX(4px);
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .ticket-id {
            font-weight: 600;
            color: #F5F4FF;
            font-size: 0.9rem;
        }

        .ticket-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-open {
            background: rgba(99, 102, 241, 0.3);
            color: #93C5FD;
        }

        .badge-resolved {
            background: rgba(16, 185, 129, 0.3);
            color: #6EE7B7;
        }

        .ticket-category {
            color: rgba(245, 244, 255, 0.6);
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .ticket-time {
            color: rgba(245, 244, 255, 0.4);
            font-size: 0.8rem;
        }

        @media (max-width: 1024px) {
            .helpdesk-container {
                grid-template-columns: 1fr;
            }

            .chat-section, .tickets-section {
                height: auto;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="student">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div style="max-width: 1200px; margin: 0 auto;">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Help Desk</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Get support with AI assistance or create a support ticket</p>
                </div>

                <div class="helpdesk-container">
                    <!-- Chat Section -->
                    <div class="chat-section">
                        <div class="chat-box">
                            <div class="chat-header">
                                <h2><i class="bi bi-chat-dots"></i> AI Support Assistant</h2>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <div class="message support">
                                    <div class="message-content">
                                        👋 Hello! How can I help you today? Ask any question about academics, fees, exams, or technical issues.
                                    </div>
                                </div>
                            </div>
                            <div class="chat-input-area">
                                <input 
                                    type="text" 
                                    id="questionInput" 
                                    placeholder="Type your question..."
                                    onkeypress="handleKeyPress(event)"
                                >
                                <button class="send-btn" id="sendBtn" onclick="sendQuestion()">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets Section -->
                    <div class="tickets-section">
                        <div class="tickets-header">
                            <h2 class="section-title" style="margin: 0 0 16px 0;">
                                <i class="bi bi-ticket"></i> Recent Tickets (<?php echo count($tickets); ?>)
                            </h2>
                        </div>

                        <div class="tickets-list">
                            <?php if (count($tickets) > 0): ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <div class="ticket-card">
                                        <div class="ticket-header">
                                            <div class="ticket-id">#<?php echo substr($ticket['id'], 0, 6); ?></div>
                                            <span class="ticket-badge badge-<?php echo rand(0, 1) ? 'open' : 'resolved'; ?>">
                                                <?php echo rand(0, 1) ? 'OPEN' : 'RESOLVED'; ?>
                                            </span>
                                        </div>
                                        <div class="ticket-category">
                                            <i class="bi bi-tag"></i> <?php echo ucfirst(str_replace('support_', '', $ticket['action'])); ?>
                                        </div>
                                        <div class="ticket-time">
                                            <?php echo date('M d, H:i', strtotime($ticket['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px 20px; color: rgba(245, 244, 255, 0.6);">
                                    <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                    <p>No tickets yet. Start a conversation with our AI assistant!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendQuestion();
            }
        }

        function sendQuestion() {
            const questionInput = document.getElementById('questionInput');
            const question = questionInput.value.trim();

            if (!question) return;

            // Add user message
            addMessage(question, 'student');
            questionInput.value = '';

            // Fetch response
            fetch('./helpDesk.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'question=' + encodeURIComponent(question)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    addMessage(data.response, 'support');
                } else {
                    addMessage('Error: ' + (data.error || 'Failed to get response'), 'support');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                addMessage('Connection error. Please try again.', 'support');
            });
        }

        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message ' + sender;

            msgDiv.innerHTML = `
                <div class="message-content">${text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
            `;

            chatMessages.appendChild(msgDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>
