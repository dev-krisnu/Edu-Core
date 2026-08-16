<?php
/**
 * AI Tutor - Student Learning Assistant
 * AI-powered tutoring system with contextual learning
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/GeminiAI.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$response = '';
$error = '';
$isProcessing = false;

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['question'])) {
    header('Content-Type: application/json');

    $question = trim($_POST['question']);
    $topic = trim($_POST['topic'] ?? 'General');

    if (empty($question) || empty($topic)) {
        echo json_encode(['success' => false, 'error' => 'Topic and question required']);
        exit;
    }

    try {
        $ai = AIFactory::create();
        $response = $ai->tutorResponse($topic, $question, 'student');

        echo json_encode([
            'success' => true,
            'response' => $response,
            'topic' => $topic,
            'timestamp' => date('M d, H:i')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'AI service unavailable. Please try again later.'
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
    <title>AI Tutor - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .tutor-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .chat-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        .chat-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
            padding: 20px;
        }

        .chat-header h2 {
            margin: 0 0 4px 0;
            color: #F5F4FF;
            font-size: 1.2rem;
        }

        .chat-header p {
            margin: 0;
            color: rgba(245, 244, 255, 0.6);
            font-size: 0.9rem;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            display: flex;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .message.student .message-avatar {
            background: linear-gradient(135deg, #6366F1, #22D3EE);
            color: white;
        }

        .message.tutor .message-avatar {
            background: linear-gradient(135deg, #8B5CF6, #D8B4FE);
            color: white;
        }

        .message-content {
            max-width: 70%;
            padding: 14px 16px;
            border-radius: 12px;
            line-height: 1.5;
        }

        .message.student .message-content {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #F5F4FF;
        }

        .message.tutor .message-content {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(212, 180, 254, 0.1));
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #F5F4FF;
        }

        .message-time {
            font-size: 0.75rem;
            color: rgba(245, 244, 255, 0.4);
            margin-top: 4px;
        }

        .chat-input-area {
            border-top: 1px solid rgba(99, 102, 241, 0.2);
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.02);
            display: flex;
            gap: 12px;
        }

        .input-group {
            display: flex;
            gap: 12px;
            flex: 1;
        }

        .input-group input,
        .input-group select {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .input-group input::placeholder {
            color: rgba(245, 244, 255, 0.4);
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .input-group input {
            flex: 1;
        }

        .input-group select {
            min-width: 150px;
        }

        .send-btn {
            padding: 12px 20px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .send-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .typing-indicator {
            display: flex;
            gap: 4px;
            align-items: center;
            color: rgba(245, 244, 255, 0.6);
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.6);
            animation: typing 1.4s infinite;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
            30% { opacity: 1; transform: translateY(-10px); }
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .info-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
        }

        .info-card-icon {
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        .info-card-text {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.8);
        }

        @media (max-width: 768px) {
            .chat-box {
                height: 500px;
            }

            .message-content {
                max-width: 90%;
            }

            .input-group select {
                display: none;
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
            <div class="tutor-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">AI Tutor</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Get instant help on any subject with our intelligent tutoring system</p>
                </div>

                <!-- Info Cards -->
                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-card-icon"><i class="bi bi-stars"></i></div>
                        <div class="info-card-text">AI-Powered Learning</div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon"><i class="bi bi-lightning"></i></div>
                        <div class="info-card-text">Instant Responses</div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon"><i class="bi bi-clipboard-check"></i></div>
                        <div class="info-card-text">In-Depth Explanations</div>
                    </div>
                </div>

                <!-- Chat Box -->
                <div class="chat-box">
                    <div class="chat-header">
                        <h2><i class="bi bi-cpu"></i> EduCore AI Tutor</h2>
                        <p>Powered by Google Gemini • Available 24/7</p>
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <div class="message tutor">
                            <div class="message-avatar"><i class="bi bi-robot"></i></div>
                            <div>
                                <div class="message-content">
                                    👋 Hello! I'm your AI Tutor. I'm here to help you understand any topic. 
                                    Select a subject, ask your question, and I'll provide a detailed explanation!
                                </div>
                                <div class="message-time">Just now</div>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input-area">
                        <div class="input-group">
                            <select id="topicSelect" class="input-focus">
                                <option value="Mathematics">Mathematics</option>
                                <option value="Science">Science</option>
                                <option value="English">English</option>
                                <option value="History">History</option>
                                <option value="Economics">Economics</option>
                                <option value="Programming">Programming</option>
                                <option value="General">General Knowledge</option>
                            </select>
                            <input 
                                type="text" 
                                id="questionInput" 
                                placeholder="Ask your question..." 
                                class="input-focus"
                                onkeypress="handleKeyPress(event)"
                            >
                        </div>
                        <button class="send-btn" id="sendBtn" onclick="sendQuestion()">
                            <i class="bi bi-send-fill"></i>
                            Send
                        </button>
                    </div>
                </div>

                <!-- Tips -->
                <div style="margin-top: 24px; padding: 16px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; border-left: 4px solid #6366F1;">
                    <h3 style="margin: 0 0 10px 0; color: #67E8F9; font-size: 0.95rem;">
                        <i class="bi bi-lightbulb"></i> Tips for Best Results
                    </h3>
                    <ul style="margin: 0; padding-left: 20px; color: rgba(245, 244, 255, 0.7); font-size: 0.85rem;">
                        <li>Be specific in your questions</li>
                        <li>Mention the topic or subject area</li>
                        <li>Ask follow-up questions for clarification</li>
                        <li>Save helpful responses for later reference</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const questionInput = document.getElementById('questionInput');
        const topicSelect = document.getElementById('topicSelect');
        const sendBtn = document.getElementById('sendBtn');

        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendQuestion();
            }
        }

        function sendQuestion() {
            const topic = topicSelect.value;
            const question = questionInput.value.trim();

            if (!question) {
                alert('Please enter a question');
                return;
            }

            // Add user message
            addMessage(question, 'student', topic);
            questionInput.value = '';
            sendBtn.disabled = true;

            // Show typing indicator
            showTypingIndicator();

            // Send to server
            fetch('./ aiTutor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'question=' + encodeURIComponent(question) + '&topic=' + encodeURIComponent(topic)
            })
            .then(res => res.json())
            .then(data => {
                removeTypingIndicator();
                if (data.success) {
                    addMessage(data.response, 'tutor', data.topic);
                } else {
                    addMessage('Error: ' + (data.error || 'Failed to get response'), 'tutor');
                }
                sendBtn.disabled = false;
            })
            .catch(err => {
                console.error('Error:', err);
                removeTypingIndicator();
                addMessage('Connection error. Please try again.', 'tutor');
                sendBtn.disabled = false;
            });
        }

        function addMessage(text, sender, topic = '') {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message ' + sender;

            const avatar = sender === 'student' ? '👨‍🎓' : '🤖';
            const timestamp = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            msgDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div>
                    <div class="message-content">${text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;

            chatMessages.appendChild(msgDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showTypingIndicator() {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message tutor';
            msgDiv.id = 'typingIndicator';
            msgDiv.innerHTML = `
                <div class="message-avatar">🤖</div>
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            chatMessages.appendChild(msgDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }
    </script>
</body>
</html>
