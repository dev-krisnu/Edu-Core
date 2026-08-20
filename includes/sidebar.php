<?php
// Pages are expected to set $user before including this file (header.php
// does). A number of pages instead set $currentUser and never $user, which
// silently made every nav item here fall back to the single-link default —
// fetch it ourselves as a safety net so the correct role nav always renders.
if (!isset($user) || !is_array($user)) {
    $user = getCurrentUser() ?? ($currentUser ?? null);
}

$role = $user['role'] ?? 'student';
$navItems = [];

switch ($role) {
    case 'super_admin':
        $navItems = [
            ['icon' => 'bi-speedometer2', 'label' => 'Command Center', 'href' => 'dashboard.php', 'color' => '#ef4444'],
            ['icon' => 'bi-mortarboard', 'label' => 'Students', 'href' => 'userStudent.php', 'color' => '#f97316'],
            ['icon' => 'bi-person-workspace', 'label' => 'Faculty', 'href' => 'userFaculty.php', 'color' => '#eab308'],
            ['icon' => 'bi-people', 'label' => 'Parents', 'href' => 'userParents.php', 'color' => '#84cc16'],
            ['icon' => 'bi-journal-bookmark', 'label' => 'Courses', 'href' => 'academicCourses.php', 'color' => '#22c55e'],
            ['icon' => 'bi-calendar-event', 'label' => 'Campus Events', 'href' => 'events.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-megaphone', 'label' => 'Notices', 'href' => 'notices.php', 'color' => '#3b82f6'],
            ['icon' => 'bi-building', 'label' => 'Infrastructure', 'href' => 'infrastructure.php', 'color' => '#6366f1'],
            ['icon' => 'bi-shield-check', 'label' => 'Permissions', 'href' => 'permissions.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-gear', 'label' => 'Institute Settings', 'href' => 'instituteSettings.php', 'color' => '#a855f7'],
            ['icon' => 'bi-journal-text', 'label' => 'System Logs', 'href' => 'logs.php', 'color' => '#ec4899'],
        ];
        break;
    case 'faculty':
        $navItems = [
            ['icon' => 'bi-grid', 'label' => 'Workspace', 'href' => 'dashboard.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-robot', 'label' => 'AI Question Setter', 'href' => 'aiQuestionGeneration.php', 'color' => '#ec4899'],
            ['icon' => 'bi-file-earmark-text', 'label' => 'Question Bank', 'href' => 'questionBank.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-pencil-square', 'label' => 'Create Exam', 'href' => 'examCreate.php', 'color' => '#3b82f6'],
            ['icon' => 'bi-clipboard-check', 'label' => 'Grade Exams', 'href' => 'examGrading.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-camera-video', 'label' => 'Exam Monitor', 'href' => 'examMoniter.php', 'color' => '#ef4444'],
            ['icon' => 'bi-calendar2-check', 'label' => 'Attendance', 'href' => 'attendance.php', 'color' => '#22c55e'],
            ['icon' => 'bi-search', 'label' => 'Plagiarism Inspector', 'href' => 'aiPlagarismInspector.php', 'color' => '#f43f5e'],
            ['icon' => 'bi-bar-chart', 'label' => 'Student Analytics', 'href' => 'studentAnalytics.php', 'color' => '#10b981'],
            ['icon' => 'bi-diagram-3', 'label' => 'Syllabus Tracker', 'href' => 'syllabusTracker.php', 'color' => '#14b8a6'],
            ['icon' => 'bi-cloud-upload', 'label' => 'Resource Upload', 'href' => 'resourceUpload.php', 'color' => '#0ea5e9'],
            ['icon' => 'bi-people', 'label' => 'PTM Scheduler', 'href' => 'ptmScheduler.php', 'color' => '#6366f1'],
            ['icon' => 'bi-kanban', 'label' => 'Student Projects', 'href' => 'projectsManage.php', 'color' => '#a855f7'],
            ['icon' => 'bi-chat-dots', 'label' => 'AI Chatbot', 'href' => 'teacherChatbot.php', 'color' => '#d946ef'],
        ];
        break;
    case 'student':
        $navItems = [
            ['icon' => 'bi-house', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-journal-richtext', 'label' => 'Study Corner', 'href' => 'studyCorner.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-display', 'label' => 'Exam Terminal', 'href' => 'exam.php', 'color' => '#ef4444'],
            ['icon' => 'bi-award', 'label' => 'Exam Results', 'href' => 'examResults.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-robot', 'label' => 'AI Tutor', 'href' => 'aiTutor.php', 'color' => '#ec4899'],
            ['icon' => 'bi-credit-card', 'label' => 'Fee Payment', 'href' => 'feePayment.php', 'color' => '#22c55e'],
            ['icon' => 'bi-calendar3', 'label' => 'Timetable', 'href' => 'timetable.php', 'color' => '#3b82f6'],
            ['icon' => 'bi-megaphone', 'label' => 'Notice Board', 'href' => 'noticeBoard.php', 'color' => '#0ea5e9'],
            ['icon' => 'bi-calendar-event', 'label' => 'Events Hub', 'href' => 'eventsHub.php', 'color' => '#14b8a6'],
            ['icon' => 'bi-upload', 'label' => 'Submit Project', 'href' => 'projectSubmit.php', 'color' => '#a855f7'],
            ['icon' => 'bi-book', 'label' => 'Library Search', 'href' => 'librarySearch.php', 'color' => '#d946ef'],
            ['icon' => 'bi-life-preserver', 'label' => 'Help Desk', 'href' => 'helpDesk.php', 'color' => '#f43f5e'],
        ];
        break;
    case 'parent':
        $navItems = [
            ['icon' => 'bi-house', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'color' => '#10b981'],
            ['icon' => 'bi-mortarboard', 'label' => "Child's Scorecard", 'href' => 'scorecard.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-calendar2-check', 'label' => 'Attendance', 'href' => 'attendence.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-credit-card', 'label' => 'Fees', 'href' => 'fees.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-people', 'label' => 'Parent-Teacher Meet', 'href' => 'ptm.php', 'color' => '#3b82f6'],
            ['icon' => 'bi-bell', 'label' => 'Alerts', 'href' => 'alerts.php', 'color' => '#ef4444'],
        ];
        break;
    case 'finance':
        $navItems = [
            ['icon' => 'bi-cash-stack', 'label' => 'Finance Hub', 'href' => 'dashboard.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-receipt', 'label' => 'Invoices', 'href' => 'invoice.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-bar-chart-line', 'label' => 'Reports', 'href' => 'report.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-wallet2', 'label' => 'Fee Structure', 'href' => 'structure.php', 'color' => '#ec4899'],
            ['icon' => 'bi-arrow-left-right', 'label' => 'Transactions', 'href' => 'transaction.php', 'color' => '#3b82f6'],
            ['icon' => 'bi-person-badge', 'label' => 'Pay Slips', 'href' => 'paySlip.php', 'color' => '#10b981'],
        ];
        break;
    case 'librarian':
        $navItems = [
            ['icon' => 'bi-bookshelf', 'label' => 'Library Hub', 'href' => 'dashboard.php', 'color' => '#ec4899'],
            ['icon' => 'bi-qr-code-scan', 'label' => 'QR Desk', 'href' => 'qrDesk.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-collection', 'label' => 'Repository', 'href' => 'repository.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-exclamation-triangle', 'label' => 'Fine Manager', 'href' => 'fines.php', 'color' => '#ef4444'],
        ];
        break;
    case 'tpo':
        $navItems = [
            ['icon' => 'bi-briefcase', 'label' => 'Placement Hub', 'href' => 'dashboard.php', 'color' => '#6366f1'],
            ['icon' => 'bi-building', 'label' => 'Internship Drives', 'href' => 'internship.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-file-earmark-person', 'label' => 'Resumes', 'href' => 'resumeUploader.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-file-person', 'label' => 'AI Resume Matcher', 'href' => 'aiResumeMatcher.php', 'color' => '#ec4899'],
            ['icon' => 'bi-graph-up-arrow', 'label' => 'Teacher Upskill', 'href' => 'teacherUpskill.php', 'color' => '#a855f7'],
        ];
        break;
    default:
        $navItems = [
            ['icon' => 'bi-house', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'color' => '#6366f1'],
        ];
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name">EduCore</span>
            <span class="brand-tagline">AI-Powered ERP</span>
        </div>
    </div>

    <div class="sidebar-role-badge" style="--role-color: <?= getRoleColor($role) ?>">
        <i class="bi bi-person-badge"></i>
        <?= getRoleLabel($role) ?>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
        <a href="<?= $item['href'] ?>" class="nav-item <?= $currentPage === $item['href'] ? 'active' : '' ?>"
           style="--item-color: <?= $item['color'] ?>">
            <i class="bi <?= $item['icon'] ?>"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="ai-assistant-card">
            <div class="ai-icon"><i class="bi bi-stars"></i></div>
            <div>
                <strong>AI Helpdesk</strong>
                <small>24/7 Support</small>
            </div>
            <button class="btn btn-sm btn-ai" onclick="openAIHelpdesk()">
                <i class="bi bi-chat-dots"></i>
            </button>
        </div>
        <a href="<?= htmlspecialchars(($portalBase ?? $basePath ?? '..') . '/profile.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
            <?php
            $sidebarPhoto = $user['photo'] ?? null;
            $sidebarPhotoPath = $sidebarPhoto ? __DIR__ . '/../uploads/' . $sidebarPhoto : null;
            ?>
            <?php if ($sidebarPhotoPath && is_file($sidebarPhotoPath)): ?>
                <img src="<?= htmlspecialchars(($portalBase ?? $basePath ?? '..') . '/uploads/' . $sidebarPhoto, ENT_QUOTES, 'UTF-8') ?>" style="width:20px; height:20px; border-radius:50%; object-fit:cover;" alt="">
            <?php else: ?>
                <i class="bi bi-person-circle"></i>
            <?php endif; ?>
            <span>My Profile</span>
        </a>
        <a href="<?= htmlspecialchars(($portalBase ?? $basePath ?? '..') . '/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-item sidebar-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<?php
// The AI Helpdesk button above only works if this modal exists in the DOM.
// It used to live only in includes/footer.php, which none of the pages
// that include this sidebar ever include - so the button did nothing
// anywhere. Rendering it here, next to the button, guarantees they're
// always paired. Guard against double-render in case a page includes
// both this file and footer.php.
//
// Built as a plain, self-contained overlay (no Bootstrap modal classes)
// because this sidebar is shared by two different page templates and
// only one of them (includes/header.php) loads Bootstrap's CSS - the
// other (includes/portal_head.php) only loads Bootstrap Icons' font.
// A Bootstrap ".modal.fade" with no Bootstrap CSS present has no
// "display:none" default, so it rendered inline and always visible on
// every portal_head.php-based page. Inline CSS below has no external
// dependency either way, so it looks/works identically everywhere.
if (!defined('EDUCORE_AI_MODAL_RENDERED')):
    define('EDUCORE_AI_MODAL_RENDERED', true);
    $aiModalBase = $portalBase ?? $basePath ?? '..';
?>
<style>
    #aiHelpdeskOverlay {
        display: none; position: fixed; inset: 0; z-index: 2000;
        align-items: center; justify-content: center;
        background: rgba(10, 10, 30, 0.65); backdrop-filter: blur(3px);
    }
    #aiHelpdeskOverlay .ai-modal-box {
        background: #14142e; border: 1px solid rgba(99, 102, 241, 0.35);
        border-radius: 16px; width: 92%; max-width: 640px;
        max-height: 82vh; display: flex; flex-direction: column;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    #aiHelpdeskOverlay .ai-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    #aiHelpdeskOverlay .ai-modal-body { padding: 18px 22px; overflow-y: auto; flex: 1; }
    #aiHelpdeskOverlay .ai-close-btn {
        background: none; border: none; color: rgba(245,244,255,0.6);
        font-size: 1.4rem; cursor: pointer; line-height: 1;
    }
    #aiHelpdeskOverlay .ai-close-btn:hover { color: #F5F4FF; }
    #aiHelpdeskOverlay .ai-chat-input { display: flex; gap: 10px; margin-top: 14px; }
    #aiHelpdeskOverlay .ai-chat-input input {
        flex: 1; padding: 12px 14px; border-radius: 10px;
        border: 1px solid rgba(99,102,241,0.3); background: rgba(255,255,255,0.05);
        color: #F5F4FF; font-family: inherit;
    }
    #aiHelpdeskOverlay .btn-send {
        padding: 0 18px; border: none; border-radius: 10px;
        background: linear-gradient(120deg,#6366F1,#22D3EE); color: #fff; cursor: pointer;
    }
</style>
<div id="aiHelpdeskOverlay" onclick="if(event.target===this) closeAIHelpdesk()">
    <div class="ai-modal-box">
        <div class="ai-modal-header">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="ai-avatar-lg"><i class="bi bi-stars"></i></div>
                <div>
                    <h5 style="margin:0; color:#F5F4FF;">EduCore AI Helpdesk</h5>
                    <small style="color:rgba(245,244,255,0.5);">Powered by Gemini / Ollama</small>
                </div>
            </div>
            <button type="button" class="ai-close-btn" onclick="closeAIHelpdesk()">&times;</button>
        </div>
        <div class="ai-modal-body">
            <div id="aiChatMessages" class="ai-chat-messages">
                <div class="ai-message bot">
                    <div class="msg-avatar"><i class="bi bi-robot"></i></div>
                    <div class="msg-bubble">Hello! I'm your EduCore AI assistant. Ask me about courses, exams, fees, library, or placements!</div>
                </div>
            </div>
            <div class="ai-chat-input">
                <input type="text" id="aiChatInput" placeholder="Type your question..." onkeydown="if(event.key==='Enter') sendAIMessage()">
                <button class="btn-send" onclick="sendAIMessage()">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<script src="<?= htmlspecialchars($aiModalBase, ENT_QUOTES, 'UTF-8') ?>/assets/js/educore.js"></script>
<?php endif; ?>