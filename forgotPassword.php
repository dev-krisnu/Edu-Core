<?php
/**
 * forgotPassword.php — EduCore password reset
 * Two-step, token-based flow:
 *   Step 1: user submits their email -> we generate a random token, store
 *           only its hash + an expiry in users.reset_token_hash /
 *           reset_token_expires, and hand them a link containing the raw
 *           token (never stored raw, so a DB leak can't be replayed).
 *   Step 2: user follows the link with ?token=... and sets a new password.
 *
 * No SMTP/mailer is wired up in this project yet, so instead of silently
 * failing we surface the reset link directly on screen, clearly marked as
 * a dev-mode fallback. Swap in a real mail send once PHPMailer/SMTP creds
 * are configured — see the TODO below.
 */

declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors       = [];
$success      = '';
$devResetLink = ''; // dev-mode fallback display, see TODO above
$stage        = isset($_GET['token']) ? 'reset' : 'request';
$oldEmail     = '';

// ---- Stage 1: request a reset link ----
if ($stage === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $email = trim((string)($_POST['email'] ?? ''));
    $oldEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Always show the same success message whether or not the email
            // exists, so this form can't be used to enumerate accounts.
            $success = "If that email is registered, we've sent a password reset link. It expires in 30 minutes.";

            if ($user) {
                $rawToken  = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

                $upd = $pdo->prepare(
                    'UPDATE users SET reset_token_hash = :hash, reset_token_expires = :exp WHERE id = :id'
                );
                $upd->execute(['hash' => $tokenHash, 'exp' => $expiresAt, 'id' => $user['id']]);

                $resetLink = url('forgotPassword.php') . '?token=' . $rawToken;

                // TODO: replace with a real mailer (PHPMailer/SMTP) once
                // credentials exist, e.g.:
                //   $mailer->send($email, 'Reset your EduCore password', $resetLink);
                $devResetLink = $resetLink;
            }
        } catch (Throwable $e) {
            error_log('[EduCore] Password reset request error: ' . $e->getMessage());
            $errors[] = 'Something went wrong on our end. Please try again.';
        }
    }
}

// ---- Stage 2: validate token, show/handle the new-password form ----
$resetUser = null;
if ($stage === 'reset') {
    $rawToken = (string)($_GET['token'] ?? '');
    if ($rawToken !== '') {
        try {
            $pdo = getDbConnection();
            $tokenHash = hash('sha256', $rawToken);
            $stmt = $pdo->prepare(
                'SELECT id, full_name FROM users
                 WHERE reset_token_hash = :hash AND reset_token_expires > NOW() LIMIT 1'
            );
            $stmt->execute(['hash' => $tokenHash]);
            $resetUser = $stmt->fetch();
        } catch (Throwable $e) {
            error_log('[EduCore] Password reset token lookup error: ' . $e->getMessage());
        }
    }

    if (!$resetUser) {
        $errors[] = 'This reset link is invalid or has expired. Request a new one below.';
        $stage = 'request';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
        $submittedToken = $_POST['csrf_token'] ?? '';
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
            $errors[] = 'Your session expired. Please try again.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            try {
                $pdo = getDbConnection();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare(
                    'UPDATE users SET password_hash = :hash, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = :id'
                );
                $upd->execute(['hash' => $hash, 'id' => $resetUser['id']]);

                $success = 'Your password has been reset. You can now log in.';
                $stage = 'done';
            } catch (Throwable $e) {
                error_log('[EduCore] Password reset update error: ' . $e->getMessage());
                $errors[] = 'Something went wrong on our end. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password · EduCore</title>
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

<div class="container-shell" style="padding: 24px; max-width: 560px; margin: 60px auto;">

  <div class="glass-strip" style="display:flex; align-items:center; gap:14px; padding: 16px 24px; margin-bottom: 20px;">
    <div class="logo-mark">EC</div>
    <div>
      <div class="h-display" style="font-size:1.15rem;">Edu<span class="brand-gradient">Core</span></div>
      <div class="text-muted" style="font-size:0.78rem;">Campus Management System</div>
    </div>
  </div>

  <div class="glass-panel" style="padding: 36px;">

    <?php if ($stage === 'done'): ?>

      <h1 class="h-display" style="font-size:1.6rem; margin-bottom: 18px;">Password reset</h1>
      <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
      <a href="index.php" class="btn btn-gradient btn-block" style="margin-top:12px;">Back to login</a>

    <?php elseif ($stage === 'reset' && $resetUser): ?>

      <h1 class="h-display" style="font-size:1.6rem; margin-bottom: 8px;">Set a new password</h1>
      <p class="text-muted" style="font-size:0.85rem; margin-bottom:22px;">Hi <?= htmlspecialchars($resetUser['full_name'], ENT_QUOTES, 'UTF-8') ?>, choose a new password below.</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endforeach; ?>

      <form method="POST" action="<?= htmlspecialchars(url('forgotPassword.php') . '?token=' . urlencode((string)($_GET['token'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="reset_password" value="1">

        <div class="field">
          <label for="password">New password</label>
          <input type="password" id="password" name="password" placeholder="At least 6 characters" required minlength="6">
        </div>

        <div class="field">
          <label for="confirm_password">Confirm new password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
        </div>

        <button type="submit" class="btn btn-gradient btn-block">Reset password</button>
      </form>

    <?php else: ?>

      <h1 class="h-display" style="font-size:1.6rem; margin-bottom: 8px;">Forgot your password?</h1>
      <p class="text-muted" style="font-size:0.85rem; margin-bottom:22px;">Enter your account email and we'll send you a reset link.</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endforeach; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <?php if ($devResetLink): ?>
        <div class="alert" style="border-color: var(--amber-300, #f59e0b); font-family: 'JetBrains Mono', monospace; font-size:0.78rem; word-break: break-all;">
          <strong style="display:block; margin-bottom:6px; font-family: 'Plus Jakarta Sans', sans-serif;">DEV MODE — no email service configured yet</strong>
          <a href="<?= htmlspecialchars($devResetLink, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($devResetLink, ENT_QUOTES, 'UTF-8') ?></a>
        </div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" action="forgotPassword.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="request_reset" value="1">

        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= $oldEmail ?>" required autofocus>
        </div>

        <button type="submit" class="btn btn-gradient btn-block">Send reset link</button>
      </form>
      <?php endif; ?>

      <div class="divider-label">or</div>
      <div style="text-align:center;">
        <a href="index.php" class="text-link">← Back to login</a>
      </div>

    <?php endif; ?>

  </div>
</div>

</body>
</html>