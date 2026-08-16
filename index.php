<?php
/**
 * index.php — EduCore login gateway
 * Handles authentication for all seven roles (student, faculty, admin,
 * parent, finance, library, placements). The Student/Faculty toggle in the
 * UI is a convenience default for the two most common logins; the actual
 * redirect after login always follows the role stored in the database,
 * never the toggle, so nobody can spoof their way into the wrong portal.
 */

declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

// ---- Already logged in? Skip the gateway. ----
if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
    header('Location: ' . roleDashboardPath($_SESSION['user_role']));
    exit;
}

function roleDashboardPath(string $role): string
{
    // Keys must match the `users.role` ENUM in database/schema.sql exactly.
    $map = [
        'super_admin' => 'admin/dashboard.php',
        'faculty'     => 'faculty/dashboard.php',
        'student'     => 'student/dashboard.php',
        'parent'      => 'parent/dashboard.php',
        'finance'     => 'finance/dashboard.php',
        'librarian'   => 'library/dashboard.php',
        'tpo'         => 'placements/dashboard.php',
    ];
    return url($map[$role] ?? 'index.php');
}

// ---- CSRF token ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors    = [];
$oldEmail  = '';

// ---- Handle login submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $oldEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Your session expired. Please try logging in again.';
    } elseif ($email === '' || $password === '') {
        $errors[] = 'Enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare(
                'SELECT id, full_name, email, password_hash, role, status, two_factor_enabled
                 FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'That email and password don\'t match our records.';
            } elseif ($user['status'] === 'suspended') {
                $errors[] = 'This account has been suspended. Contact your administrator.';
            } else {
                session_regenerate_id(true);

                if (!empty($user['two_factor_enabled'])) {
                    // Password verified, but the account requires a TOTP
                    // code before the session becomes fully authenticated.
                    $_SESSION['pending_2fa_user_id'] = $user['id'];
                    $_SESSION['pending_2fa_role']    = $user['role'];
                    $_SESSION['pending_2fa_name']    = $user['full_name'];
                    header('Location: ' . url('2fa.php'));
                    exit;
                }

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ' . roleDashboardPath($user['role']));
                exit;
            }
        } catch (Throwable $e) {
            error_log('[EduCore] Login error: ' . $e->getMessage());
            $errors[] = 'Something went wrong on our end. Please try again.';
        }
    }
}

// ---- Notices for the board (fails gracefully if the table isn't set up yet) ----
$notices = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        "SELECT title FROM notices WHERE is_public = 1
         ORDER BY created_at DESC LIMIT 4"
    );
    $notices = $stmt ? $stmt->fetchAll() : [];
} catch (Throwable $e) {
    $notices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduCore Login · Dr. B.C. Roy Engineering College</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="aurora-bg">
  <div class="aurora-blob b1"></div>
  <div class="aurora-blob b2"></div>
  <div class="aurora-blob b3"></div>
</div>
<div class="grain"></div>

<div class="container-shell" style="padding: 24px; max-width: 1200px; margin: 0 auto;">

  <!-- Top strip -->
  <div class="glass-strip" style="display:flex; align-items:center; justify-content:space-between; padding: 16px 24px; margin-bottom: 20px;">
    <div style="display:flex; align-items:center; gap:14px;">
      <div class="logo-mark">EC</div>
      <div>
        <div class="h-display" style="font-size:1.15rem;">Edu<span class="brand-gradient">Core</span></div>
        <div class="text-muted" style="font-size:0.78rem;">Campus Management System</div>
      </div>
    </div>
    <div style="text-align:right;">
      <div class="h-display" style="font-size:1.05rem;">Dr. B.C. Roy Engineering College</div>
      <div class="eyebrow" style="color:var(--amber-300);">Durgapur</div>
    </div>
  </div>

  <!-- Main grid -->
  <div style="display:grid; grid-template-columns: 300px 1fr; gap: 20px; flex: 1;" class="main-grid">

    <!-- Left: notices + ID badge -->
    <div style="display:flex; flex-direction:column; gap:20px;" class="lg-hide">
      <div class="glass-panel" style="padding: 22px;">
        <div class="eyebrow" style="margin-bottom:12px;">Notice Board</div>
        <?php if (empty($notices)): ?>
          <p class="notice-empty">No new notices at this time.</p>
        <?php else: ?>
          <ul class="notice-list">
            <?php foreach ($notices as $n): ?>
              <li><span class="notice-dot"></span><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div>
        <div class="eyebrow" style="margin-bottom:12px;">Your Digital ID</div>
        <div class="id-badge" id="idBadge">
          <div class="id-badge-content">
            <div class="id-badge-top">
              <span class="pill">EDU · ID</span>
              <div class="id-badge-qr"></div>
            </div>
            <div>
              <div class="id-badge-name">Campus Access Card</div>
              <div class="id-badge-id">DBCREC · SCAN TO VERIFY</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: login -->
    <div>
      <div class="glass-panel glass-hi" style="padding: 8px; margin-bottom: 20px;">
        <div style="display:flex; justify-content:center; padding: 10px;">
          <div class="role-toggle" id="roleToggle">
            <input type="radio" name="role_display" id="role-student" checked>
            <input type="radio" name="role_display" id="role-faculty">
            <div class="role-thumb"></div>
            <label for="role-student">Student</label>
            <label for="role-faculty">Faculty</label>
          </div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1.6fr 1fr; gap: 20px;" class="login-grid">

        <div class="glass-panel" style="padding: 36px;">
          <h1 class="h-display" style="font-size:1.9rem; margin-bottom: 26px;">Welcome back</h1>

          <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endforeach; ?>

          <form method="POST" action="index.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
              <label for="email">Email address</label>
              <input type="email" id="email" name="email" placeholder="Example: komalshaw@gmail.com" value="<?= $oldEmail ?>" required autofocus>
            </div>

            <div class="field">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" placeholder="••••••••••" required>
              <button type="button" class="field-icon-btn" id="togglePw" aria-label="Show password">👁</button>
            </div>

            <button type="submit" class="btn btn-gradient btn-block">Log in</button>
          </form>

          <div class="divider-label">or</div>

          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="text-muted" style="font-size:0.85rem;">New here?</span>
            <a href="signup.php" class="text-link">Create an account →</a>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
            <span class="text-muted" style="font-size:0.85rem;">Forgot something?</span>
            <a href="forgotPassword.php" class="text-link">Reset your password →</a>
          </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:20px;">
          <div class="glass-panel" style="padding: 24px; text-align:center;">
            <div class="eyebrow" style="margin-bottom:10px;">Need help?</div>
            <p class="text-muted" style="font-size:0.85rem; margin-bottom:16px;">Our AI assistant can help you recover access or find the right portal.</p>
            <a href="homePageChatbot.php" class="btn btn-ghost btn-block">Open AI Assistant</a>
          </div>

          <div class="glass-panel" style="padding: 20px; text-align:center;">
            <div class="h-display" style="font-size:1rem;">Edu<span class="brand-gradient">Core</span></div>
            <div class="pill" style="margin-top:10px;">v1.0.0</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Password visibility toggle
  const pwInput = document.getElementById('password');
  const pwToggle = document.getElementById('togglePw');
  pwToggle.addEventListener('click', () => {
    const isHidden = pwInput.type === 'password';
    pwInput.type = isHidden ? 'text' : 'password';
    pwToggle.textContent = isHidden ? '🙈' : '👁';
    pwToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
  });

  // Subtle tilt on the digital ID badge signature element
  const badge = document.getElementById('idBadge');
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches === false) {
    badge.addEventListener('mousemove', (e) => {
      const rect = badge.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;
      badge.style.transform = `rotateX(${y * -8}deg) rotateY(${x * 10}deg)`;
    });
    badge.addEventListener('mouseleave', () => {
      badge.style.transform = 'rotateX(0deg) rotateY(0deg)';
    });
  }
</script>

<style>
  @media (max-width: 960px) {
    .main-grid { grid-template-columns: 1fr !important; }
    .login-grid { grid-template-columns: 1fr !important; }
  }
</style>

</body>
</html>