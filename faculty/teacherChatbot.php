<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../controllers/AIController.php';
requireRole(['faculty']);
$user = getCurrentUser();
$pageTitle = 'Faculty AI Chatbot';
$basePath = '..';
$reply = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ai = new AIController();
    $reply = $ai->helpdeskResponse(trim($_POST['message'] ?? ''), 'Role: faculty, Name: ' . $user['full_name']);
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header fade-in"><h1><i class="bi bi-chat-dots" style="color:#d946ef"></i> Faculty AI Assistant</h1><p>Lesson plans, grading tips, and academic support</p></div>
<div class="row">
<div class="col-lg-6"><div class="content-card fade-in">
<form method="POST">
<textarea class="form-control mb-3" name="message" rows="5" placeholder="Ask about lesson planning, rubrics, Bloom's taxonomy..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
<button class="btn btn-gradient w-100"><i class="bi bi-stars me-2"></i>Ask AI</button>
</form></div></div>
<div class="col-lg-6"><div class="content-card fade-in">
<h3 class="mb-3">Response</h3>
<?php if ($reply): ?><div class="animate-fade-in" style="font-size:1.05rem;line-height:1.65"><?= nl2br(htmlspecialchars($reply)) ?></div>
<?php else: ?><p class="text-muted">Your AI response will appear here. Uses Gemini with response caching to save tokens.</p><?php endif; ?>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php';
