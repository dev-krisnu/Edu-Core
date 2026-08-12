<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND status = "active"');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                logAction('User logged in', 'auth');
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'Database connection failed. Please import database/schema.sql first.';
        }
    }
}

$notices = [];
try {
    $db = getDB();
    $notices = $db->query('SELECT * FROM notices ORDER BY created_at DESC LIMIT 5')->fetchAll();
} catch (Exception $e) {
    // DB not set up yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduCore — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/educore.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-container fade-in">
        <div class="login-brand-panel">
            <div class="brand-logo"><i class="bi bi-mortarboard-fill"></i></div>
            <h1>EduCore</h1>
            <p class="tagline">Unified AI-Powered Educational ERP & LMS</p>
            <ul class="feature-list">
                <li><i class="bi bi-robot"></i> AI Question Setter & 24/7 Helpdesk</li>
                <li><i class="bi bi-shield-check"></i> Proctored Online Exam Terminal</li>
                <li><i class="bi bi-search"></i> Plagiarism & Anti-Cheating Inspector</li>
                <li><i class="bi bi-qr-code-scan"></i> QR-Code Library Management</li>
                <li><i class="bi bi-cash-stack"></i> Dynamic Fee & Payroll Engine</li>
                <li><i class="bi bi-briefcase"></i> AI Resume Matcher & Placements</li>
            </ul>
        </div>

        <div class="login-form-panel">
            <h2 class="mb-1" style="font-weight:800;">Welcome Back</h2>
            <p class="text-muted mb-4">Sign in to your EduCore account</p>

            <?php if ($notices): ?>
            <div class="notice-panel">
                <strong><i class="bi bi-megaphone me-1"></i> Notice Board</strong>
                <?php foreach (array_slice($notices, 0, 2) as $n): ?>
                <div class="notice-item">
                    <strong><?= htmlspecialchars($n['title']) ?></strong>
                    <span><?= htmlspecialchars(substr($n['content'], 0, 80)) ?>...</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="role-selector">
                <?php
                $roles = [
                    ['id' => 'admin', 'label' => 'Admin', 'color' => '#ef4444', 'email' => 'admin@educore.edu'],
                    ['id' => 'faculty', 'label' => 'Faculty', 'color' => '#8b5cf6', 'email' => 'faculty@educore.edu'],
                    ['id' => 'student', 'label' => 'Student', 'color' => '#06b6d4', 'email' => 'student@educore.edu'],
                    ['id' => 'finance', 'label' => 'Finance', 'color' => '#f59e0b', 'email' => 'finance@educore.edu'],
                    ['id' => 'librarian', 'label' => 'Library', 'color' => '#ec4899', 'email' => 'librarian@educore.edu'],
                ];
                foreach ($roles as $i => $r): ?>
                <span class="role-chip <?= $i === 2 ? 'active' : '' ?>"
                      style="--chip-color:<?= $r['color'] ?>;--chip-bg:<?= $r['color'] ?>15"
                      data-email="<?= $r['email'] ?>"
                      onclick="selectRole(this)"><?= $r['label'] ?></span>
                <?php endforeach; ?>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-floating-custom">
                    <label>Email Address</label>
                    <input type="email" name="email" id="loginEmail" value="student@educore.edu" required placeholder="you@educore.edu">
                </div>
                <div class="form-floating-custom">
                    <label>Password</label>
                    <input type="password" name="password" value="password123" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-gradient w-100 mb-3 py-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="text-center">
                <small class="text-muted">Demo password: <code>password123</code> for all roles</small>
                <br>
                <a href="signup.php" class="text-decoration-none mt-2 d-inline-block">Create new account →</a>
            </div>
        </div>
    </div>
</div>

<script>
function selectRole(el) {
    document.querySelectorAll('.role-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('loginEmail').value = el.dataset.email;
}
</script>
</body>
</html>
