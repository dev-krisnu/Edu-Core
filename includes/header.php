<?php
$user = getCurrentUser();
$pageTitle = $pageTitle ?? 'EduCore';
$basePath = $basePath ?? '../..';
require_once __DIR__ . '/role_theme.php';
$roleTheme = getRoleThemeKey($user['role'] ?? 'student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= $basePath ?>/assets/css/educore.css" rel="stylesheet">
    <link href="<?= $basePath ?>/assets/css/themes.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- Needed for the user-menu dropdown below (data-bs-toggle="dropdown").
         Previously only loaded via a script tag at the bottom of
         includes/footer.php - moved here, next to what actually uses it,
         after that duplicate copy (and a second stray copy pulled in by
         includes/sidebar.php for the AI modal, since removed in favor of
         a Bootstrap-independent modal) was cleaned up. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="educore-body theme-<?= htmlspecialchars($roleTheme) ?>" data-role="<?= htmlspecialchars($roleTheme) ?>">
<div class="app-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">
        <header class="top-bar">
            <div class="top-bar-left">
                <button class="sidebar-toggle btn btn-link d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="search-box d-none d-md-flex">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search modules, courses, students..." class="form-control">
                </div>
            </div>
            <div class="top-bar-right">
                <div class="notification-bell">
                    <i class="bi bi-bell"></i>
                    <span class="badge-dot"></span>
                </div>
                <div class="user-menu dropdown">
                    <button class="btn user-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <?php
                        $headerPhoto = $user['photo'] ?? null;
                        $headerPhotoPath = $headerPhoto ? __DIR__ . '/../uploads/' . $headerPhoto : null;
                        ?>
                        <?php if ($headerPhotoPath && is_file($headerPhotoPath)): ?>
                            <img src="<?= htmlspecialchars($basePath) ?>/uploads/<?= htmlspecialchars($headerPhoto) ?>" class="user-avatar" style="object-fit:cover;" alt="">
                        <?php else: ?>
                            <div class="user-avatar" style="background: linear-gradient(135deg, <?= getRoleColor($user['role'] ?? 'student') ?>, #6366f1);">
                                <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="user-info d-none d-md-block">
                            <span class="user-name"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></span>
                            <span class="user-role"><?= getRoleLabel($user['role'] ?? 'student') ?></span>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                        <li><a class="dropdown-item" href="<?= $basePath ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="<?= $basePath ?>/profile.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $basePath ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>
        <main class="page-content">
