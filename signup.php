<?php
/**
 * signup.php — EduCore self-registration
 * Only "public" roles can be created here (student, faculty, parent).
 * Admin/finance/librarian/tpo accounts are provisioned internally by an
 * administrator, never through open signup — the role is whitelisted
 * server-side regardless of what the form submits.
 */

declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

const SELF_SIGNUP_ROLES = ['student', 'faculty', 'parent'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors   = [];
$success  = '';
$old = ['fullname' => '', 'email' => '', 'phone' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $old['fullname'] = trim((string)($_POST['fullname'] ?? ''));
    $old['email']    = trim((string)($_POST['email'] ?? ''));
    $old['phone']    = trim((string)($_POST['phone'] ?? ''));
    $old['role']     = (string)($_POST['role'] ?? 'student');
    $password        = (string)($_POST['password'] ?? '');
    $confirm         = (string)($_POST['confirm_password'] ?? '');

    // Whitelist the role no matter what was submitted.
    $role = in_array($old['role'], SELF_SIGNUP_ROLES, true) ? $old['role'] : 'student';

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($old['fullname'] === '' || $old['email'] === '' || $password === '') {
        $errors[] = 'Full name, email, and password are required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDbConnection();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$old['email']]);

            if ($check->fetch()) {
                $errors[] = 'That email is already registered. Try logging in instead.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$old['fullname'], $old['email'], $hash, $old['phone'] ?: null, $role]);
                $success = 'Account created! You can log in now.';
                $old = ['fullname' => '', 'email' => '', 'phone' => '', 'role' => 'student'];
            }
        } catch (Throwable $e) {
            error_log('[EduCore] Signup error: ' . $e->getMessage());
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
<title>Create Account · EduCore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  .role-pick { display:flex; gap:10px; margin-bottom: 4px; }
  .role-pick label {
    flex:1; text-align:center; padding: 10px 8px; border-radius: 10px;
    border: 1px solid var(--border-glass, rgba(255,255,255,0.12));
    font-size: 0.82rem; cursor:pointer; transition: all .15s ease;
  }
  .role-pick input { position:absolute; opacity:0; pointer-events:none; }
  .role-pick input:checked + label {
    border-color: var(--cyan-300, #22d3ee);
    background: var(--surface-glass-hi, rgba(255,255,255,0.08));
  }
  .field-row { display:grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media (max-width: 560px) { .field-row { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<div class="aurora-bg">
  <div class="aurora-blob b1"></div>
  <div class="aurora-blob b2"></div>
  <div class="aurora-blob b3"></div>
</div>
<div class="grain"></div>

<div class="container-shell" style="padding: 24px; max-width: 560px; margin: 40px auto;">

  <div class="glass-strip" style="display:flex; align-items:center; gap:14px; padding: 16px 24px; margin-bottom: 20px;">
    <div class="logo-mark">EC</div>
    <div>
      <div class="h-display" style="font-size:1.15rem;">Edu<span class="brand-gradient">Core</span></div>
      <div class="text-muted" style="font-size:0.78rem;">Campus Management System</div>
    </div>
  </div>

  <div class="glass-panel" style="padding: 36px;">
    <h1 class="h-display" style="font-size:1.7rem; margin-bottom: 6px;">Create your account</h1>
    <p class="text-muted" style="font-size:0.85rem; margin-bottom:24px;">Join EduCore as a student, faculty member, or parent.</p>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
      <a href="index.php" class="btn btn-gradient btn-block" style="margin-top:8px;">Go to login →</a>
    <?php else: ?>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endforeach; ?>

      <form method="POST" action="signup.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="field">
          <label>I am a…</label>
          <div class="role-pick">
            <?php foreach (['student' => 'Student', 'faculty' => 'Faculty', 'parent' => 'Parent'] as $val => $label): ?>
              <input type="radio" name="role" id="role-<?= $val ?>" value="<?= $val ?>" <?= $old['role'] === $val ? 'checked' : '' ?>>
              <label for="role-<?= $val ?>"><?= $label ?></label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field">
          <label for="fullname">Full name</label>
          <input type="text" id="fullname" name="fullname" placeholder="Full name" value="<?= htmlspecialchars($old['fullname'], ENT_QUOTES, 'UTF-8') ?>" required autofocus>
        </div>

        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="field">
          <label for="phone">Phone (optional)</label>
          <input type="tel" id="phone" name="phone" placeholder="+91 98765 43210" value="<?= htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field-row">
          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="At least 6 characters" required minlength="6">
          </div>
          <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
          </div>
        </div>

        <button type="submit" class="btn btn-gradient btn-block">Create account</button>
      </form>

      <div class="divider-label">or</div>
      <div style="text-align:center;">
        <span class="text-muted" style="font-size:0.85rem;">Already have an account?</span>
        <a href="index.php" class="text-link">Log in →</a>
      </div>

    <?php endif; ?>
  </div>
</div>

</body>
</html>