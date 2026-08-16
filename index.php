<?php
/**
 * index.php — EduCore login gateway
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
    header('Location: ' . roleDashboardPath($_SESSION['user_role']));
    exit;
}

function roleDashboardPath(string $role): string
{
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors  = [];
$oldEmail = '';
$notices = [];

try {
    $pdoNotices = getDbConnection();
    $nStmt = $pdoNotices->query('SELECT title, content FROM notices WHERE is_public = 1 ORDER BY created_at DESC LIMIT 4');
    $notices = $nStmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $notices = [];
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduCore — Dr. B.C. Roy Engineering College</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars(url('style.css')) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/login-page.css')) ?>">
</head>
<body class="login-page" data-portal="student">

<div class="login-bg" aria-hidden="true"></div>

<div class="login-shell">

  <header class="login-header">
    <div class="login-brand">
      <div class="logo-mark">EC</div>
      <div>
        <h1>Edu<span class="brand-gradient">Core</span></h1>
        <p>AI-Powered Campus Management System</p>
      </div>
    </div>
    <div class="college-block">
      <h2>Dr. B.C. Roy Engineering College</h2>
      <div class="eyebrow">Durgapur · West Bengal</div>
    </div>
  </header>

  <div class="login-main">

    <!-- Hero + features -->
    <section class="hero-panel">
      <div class="hero-kicker">
        <i class="bi bi-stars"></i> Unified ERP &amp; LMS · Powered by Gemini AI
      </div>

      <h2 class="hero-title">
        Your entire campus,<br>
        <span class="brand-gradient">one intelligent platform</span>
      </h2>

      <p class="hero-desc">
        Manage academics, proctored exams, fees, library circulation, placements, and
        24/7 AI assistance — built for students, faculty, and every department on campus.
      </p>

      <div class="stats-row">
        <div class="stat-pill"><strong>7</strong><span>Role Portals</span></div>
        <div class="stat-pill"><strong>AI</strong><span>Smart Tutor</span></div>
        <div class="stat-pill"><strong>QR</strong><span>Library Desk</span></div>
        <div class="stat-pill"><strong>24/7</strong><span>Helpdesk</span></div>
      </div>

      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon i1"><i class="bi bi-robot"></i></div>
          <div>
            <h3>AI Question Setter</h3>
            <p>Auto-generate exams using Bloom's Taxonomy with Gemini.</p>
          </div>
        </div>
        <div class="feature-card">
          <div class="feature-icon i2"><i class="bi bi-display"></i></div>
          <div>
            <h3>Proctored Exams</h3>
            <p>Secure online terminal with tab-switch detection.</p>
          </div>
        </div>
        <div class="feature-card">
          <div class="feature-icon i3"><i class="bi bi-search"></i></div>
          <div>
            <h3>Plagiarism Inspector</h3>
            <p>AI-powered similarity and content analysis.</p>
          </div>
        </div>
        <div class="feature-card">
          <div class="feature-icon i4"><i class="bi bi-briefcase"></i></div>
          <div>
            <h3>Placement Hub</h3>
            <p>Resume matching and internship drive management.</p>
          </div>
        </div>
      </div>

      <div class="info-row">
        <div class="light-card">
          <div class="eyebrow">Campus Notices</div>
          <?php if (empty($notices)): ?>
            <p class="notice-empty">No new notices at this time.</p>
          <?php else: ?>
            <ul class="notice-list">
              <?php foreach ($notices as $n): ?>
                <li>
                  <span class="notice-dot"></span>
                  <span><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <div class="light-card">
          <div class="eyebrow">Digital Campus ID</div>
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
    </section>

    <!-- Login -->
    <aside class="login-column">
      <div class="login-card">
        <h2 id="loginTitle">Welcome back, Student</h2>
        <p class="login-sub" id="loginSub">Sign in to your student portal</p>

        <p class="role-hint" id="roleHint">
          <strong>Student portal</strong> — demo: <code>krrishjeswar@educore.edu</code> / password123
        </p>

        <div class="role-pick" id="rolePick" role="group" aria-label="Choose portal">
          <button type="button" class="role-pick-btn active" data-role="student" data-email="krrishjeswar@educore.edu" data-label="Student">
            <i class="bi bi-mortarboard"></i> Student
          </button>
          <button type="button" class="role-pick-btn" data-role="faculty" data-email="faculty@educore.edu" data-label="Faculty">
            <i class="bi bi-person-workspace"></i> Faculty
          </button>
          <button type="button" class="role-pick-btn" data-role="admin" data-email="admin@educore.edu" data-label="Admin">
            <i class="bi bi-shield-lock"></i> Admin
          </button>
          <button type="button" class="role-pick-btn" data-role="parent" data-email="parent@educore.edu" data-label="Parent">
            <i class="bi bi-people"></i> Parent
          </button>
          <button type="button" class="role-pick-btn" data-role="finance" data-email="finance@educore.edu" data-label="Finance">
            <i class="bi bi-cash-stack"></i> Finance
          </button>
          <button type="button" class="role-pick-btn" data-role="librarian" data-email="librarian@educore.edu" data-label="Librarian">
            <i class="bi bi-book"></i> Library
          </button>
          <button type="button" class="role-pick-btn" data-role="tpo" data-email="tpo@educore.edu" data-label="TPO">
            <i class="bi bi-briefcase"></i> TPO
          </button>
        </div>

        <?php foreach ($errors as $err): ?>
          <div class="alert alert-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>

          <form method="POST" action="<?= htmlspecialchars(url('index.php')) ?>" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="krrishjeswar@educore.edu" value="<?= $oldEmail ?>" required autofocus>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <button type="button" class="field-icon-btn" id="togglePw" aria-label="Show password"><i class="bi bi-eye"></i></button>
          </div>

          <button type="submit" class="btn btn-gradient btn-block">
            <i class="bi bi-box-arrow-in-right"></i> Log in to EduCore
          </button>
        </form>

        <div class="divider-label">or continue with</div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <span class="text-muted">New here?</span>
          <a href="<?= htmlspecialchars(url('signup.php')) ?>" class="text-link">Create an account →</a>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span class="text-muted">Forgot password?</span>
          <a href="<?= htmlspecialchars(url('forgotPassword.php')) ?>" class="text-link">Reset it →</a>
        </div>
      </div>

      <div class="ai-promo">
        <h3><i class="bi bi-chat-heart" style="color:#6366f1"></i> Need help signing in?</h3>
        <p>Our Gemini-powered assistant can guide you to the right portal.</p>
        <a href="<?= htmlspecialchars(url('homePageChatbot.php')) ?>" class="btn btn-ghost btn-block">
          <i class="bi bi-stars"></i> Open AI Assistant
        </a>
      </div>
    </aside>

  </div>

  <footer class="login-footer">
    Edu<span class="brand-gradient">Core</span> v1.0.0 · Dr. B.C. Roy Engineering College · Ardent PHP Internship 2026
  </footer>

</div>

<script>
const ROLES = {
  student:   { email: 'krrishjeswar@educore.edu', label: 'Student' },
  faculty:   { email: 'faculty@educore.edu', label: 'Faculty' },
  admin:     { email: 'admin@educore.edu', label: 'Admin' },
  parent:    { email: 'parent@educore.edu', label: 'Parent' },
  finance:   { email: 'finance@educore.edu', label: 'Finance' },
  librarian: { email: 'librarian@educore.edu', label: 'Librarian' },
  tpo:       { email: 'tpo@educore.edu', label: 'TPO' }
};

const emailInput = document.getElementById('email');
const loginTitle = document.getElementById('loginTitle');
const loginSub = document.getElementById('loginSub');
const roleHint = document.getElementById('roleHint');
const bodyEl = document.body;

document.querySelectorAll('.role-pick-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const role = btn.dataset.role;
    const label = btn.dataset.label;
    const email = btn.dataset.email;

    document.querySelectorAll('.role-pick-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    bodyEl.dataset.portal = role;
    loginTitle.textContent = `Welcome back, ${label}`;
    loginSub.textContent = `Sign in to your ${label.toLowerCase()} portal`;
    roleHint.innerHTML = `<strong>${label} portal</strong> — demo: <code>${email}</code> / password123`;

    if (!emailInput.value || Object.values(ROLES).some(r => r.email === emailInput.value)) {
      emailInput.value = email;
    }
    emailInput.placeholder = email;
  });
});

const pwInput = document.getElementById('password');
const pwToggle = document.getElementById('togglePw');
pwToggle.addEventListener('click', () => {
  const isHidden = pwInput.type === 'password';
  pwInput.type = isHidden ? 'text' : 'password';
  pwToggle.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
  pwToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
});

const badge = document.getElementById('idBadge');
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches === false) {
  badge.addEventListener('mousemove', (e) => {
    const rect = badge.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width - 0.5;
    const y = (e.clientY - rect.top) / rect.height - 0.5;
    badge.style.transform = `rotateX(${y * -6}deg) rotateY(${x * 8}deg)`;
  });
  badge.addEventListener('mouseleave', () => {
    badge.style.transform = 'rotateX(0deg) rotateY(0deg)';
  });
}
</script>

</body>
</html>
