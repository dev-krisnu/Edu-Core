        </main>
    </div>
</div>

<!-- AI Helpdesk Modal -->
<div class="modal fade" id="aiHelpdeskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ai-modal">
            <div class="modal-header border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="ai-avatar-lg"><i class="bi bi-stars"></i></div>
                    <div>
                        <h5 class="modal-title mb-0">EduCore AI Helpdesk</h5>
                        <small class="text-muted">Powered by Gemini / Ollama</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="aiChatMessages" class="ai-chat-messages">
                    <div class="ai-message bot">
                        <div class="msg-avatar"><i class="bi bi-robot"></i></div>
                        <div class="msg-bubble">Hello! I'm your EduCore AI assistant. Ask me about courses, exams, fees, library, or placements!</div>
                    </div>
                </div>
                <div class="ai-chat-input mt-3">
                    <input type="text" id="aiChatInput" class="form-control" placeholder="Type your question...">
                    <button class="btn btn-primary btn-send" onclick="sendAIMessage()">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?? '../..' ?>/assets/js/educore.js"></script>
</body>
</html>
