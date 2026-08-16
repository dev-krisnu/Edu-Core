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
    </div>
</aside>