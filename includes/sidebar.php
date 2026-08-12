<?php
$role = $user['role'] ?? 'student';
$navItems = [];

switch ($role) {
    case 'super_admin':
        $navItems = [
            ['icon' => 'bi-speedometer2', 'label' => 'Command Center', 'href' => 'dashboard.php', 'color' => '#ef4444'],
            ['icon' => 'bi-people', 'label' => 'User Management', 'href' => 'users.php', 'color' => '#f97316'],
            ['icon' => 'bi-shield-check', 'label' => 'RBAC & Security', 'href' => 'security.php', 'color' => '#eab308'],
            ['icon' => 'bi-building', 'label' => 'Facilities', 'href' => 'facilities.php', 'color' => '#22c55e'],
            ['icon' => 'bi-megaphone', 'label' => 'Notices', 'href' => 'notices.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-journal-text', 'label' => 'System Logs', 'href' => 'logs.php', 'color' => '#8b5cf6'],
        ];
        break;
    case 'faculty':
        $navItems = [
            ['icon' => 'bi-grid', 'label' => 'Workspace', 'href' => 'dashboard.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-robot', 'label' => 'AI Question Setter', 'href' => 'ai_questions.php', 'color' => '#ec4899'],
            ['icon' => 'bi-file-earmark-text', 'label' => 'Question Bank', 'href' => 'question_bank.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-clipboard-check', 'label' => 'Exams & Grading', 'href' => 'exams.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-search', 'label' => 'Plagiarism Check', 'href' => 'plagiarism.php', 'color' => '#ef4444'],
            ['icon' => 'bi-bar-chart', 'label' => 'Analytics', 'href' => 'analytics.php', 'color' => '#10b981'],
        ];
        break;
    case 'student':
        $navItems = [
            ['icon' => 'bi-house', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-book', 'label' => 'My Courses', 'href' => 'courses.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-display', 'label' => 'Exam Terminal', 'href' => 'exam_terminal.php', 'color' => '#ef4444'],
            ['icon' => 'bi-robot', 'label' => 'AI Tutor', 'href' => 'ai_tutor.php', 'color' => '#ec4899'],
            ['icon' => 'bi-credit-card', 'label' => 'Fees', 'href' => 'fees.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-radar', 'label' => 'Remedial Analytics', 'href' => 'analytics.php', 'color' => '#10b981'],
        ];
        break;
    case 'finance':
        $navItems = [
            ['icon' => 'bi-cash-stack', 'label' => 'Finance Hub', 'href' => 'dashboard.php', 'color' => '#f59e0b'],
            ['icon' => 'bi-receipt', 'label' => 'Invoices', 'href' => 'invoices.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-journal-bookmark', 'label' => 'Ledger', 'href' => 'ledger.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-wallet2', 'label' => 'Fee Templates', 'href' => 'fee_templates.php', 'color' => '#ec4899'],
            ['icon' => 'bi-person-badge', 'label' => 'Payroll', 'href' => 'payroll.php', 'color' => '#10b981'],
        ];
        break;
    case 'librarian':
        $navItems = [
            ['icon' => 'bi-bookshelf', 'label' => 'Library Hub', 'href' => 'dashboard.php', 'color' => '#ec4899'],
            ['icon' => 'bi-qr-code-scan', 'label' => 'QR Circulation', 'href' => 'qr_desk.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-collection', 'label' => 'Catalog', 'href' => 'catalog.php', 'color' => '#8b5cf6'],
            ['icon' => 'bi-exclamation-triangle', 'label' => 'Fine Manager', 'href' => 'fines.php', 'color' => '#ef4444'],
        ];
        break;
    case 'tpo':
        $navItems = [
            ['icon' => 'bi-briefcase', 'label' => 'Placement Hub', 'href' => 'dashboard.php', 'color' => '#6366f1'],
            ['icon' => 'bi-building', 'label' => 'Company Drives', 'href' => 'drives.php', 'color' => '#06b6d4'],
            ['icon' => 'bi-file-person', 'label' => 'AI Resume Matcher', 'href' => 'resume_matcher.php', 'color' => '#ec4899'],
            ['icon' => 'bi-calendar-event', 'label' => 'Interview Timeline', 'href' => 'timeline.php', 'color' => '#f59e0b'],
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
