<?php
/**
 * 2fa.php — TOTP verification gate
 * Reached only after index.php has already verified the password for an
 * account with two_factor_enabled = 1. Session holds a "pending" identity
 * (not yet a real logged-in session) until the 6-digit code checks out.
 */

declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/TOTP.php';

// No pending 2FA challenge? Nothing to verify — send them back to login.
if (empty($_SESSION['pending_2fa_user_id'])) {
    header('Location: ' . url('index.php'));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$pendingName = $_SESSION['pending_2fa_name'] ?? 'there';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $codeParts = $_POST['code'] ?? [];
    $code = is_array($codeParts) ? implode('', $codeParts) : (string)$codeParts;

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Your session expired. Please log in again.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $errors[] = 'Enter the 6-digit code from your authenticator app.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare(
                'SELECT id, full_name, role, status, two_factor_secret
                 FROM users WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $_SESSION['pending_2fa_user_id']]);
            $user = $stmt->fetch();

            if (!$user || empty($user['two_factor_secret'])) {
                $errors[] = 'Two-factor setup is incomplete for this account. Contact your administrator.';
            } elseif ($user['status'] === 'suspended') {
                $errors[] = 'This account has been suspended. Contact your administrator.';
            } elseif (!TOTP::verifyCode($user['two_factor_secret'], $code)) {
                $errors[] = 'That code is incorrect or has expired. Try the latest code from your app.';
            } else {
                // Fully authenticated now — promote the pending session.
                session_regenerate_id(true);
                unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_role'], $_SESSION['pending_2fa_name']);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ' . roleDashboardPathFor($user['role']));
                exit;
            }
        } catch (Throwable $e) {
            error_log('[EduCore] 2FA verification error: ' . $e->getMessage());
            $errors[] = 'Something went wrong on our end. Please try again.';
        }
    }
}

/** Same role->dashboard map as index.php, kept local so this page has no
 *  dependency on index.php's routing function being defined. */
function roleDashboardPathFor(string $role): string
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Your Identity · EduCore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  .otp-group { display:flex; gap:10px; justify-content:center; margin: 22px 0; }
  .otp-group input {
    width: 46px; height: 56px; text-align:center; font-size:1.4rem;
    font-family:'JetBrains Mono', monospace; font-weight:600;
    border-radius: 12px; border: 1px solid var(--border-glass, rgba(255,255,255,0.12));
    background: var(--surface-glass, rgba(255,255,255,0.04)); color: inherit;
  }
  .otp-group input:focus { outline: none; border-color: var(--cyan-300, #22d3ee); }
</style>
</head>
<body>

<div class="aurora-bg">
  <div class="aurora-blob b1"></div>
  <div class="aurora-blob b2"></div>
  <div class="aurora-blob b3"></div>
</div>
<div class="grain"></div>

<div class="container-shell" style="padding: 24px; max-width: 480px; margin: 70px auto;">

  <div class="glass-strip" style="display:flex; align-items:center; gap:14px; padding: 16px 24px; margin-bottom: 20px;">
    <div class="logo-mark">EC</div>
    <div>
      <div class="h-display" style="font-size:1.15rem;">Edu<span class="brand-gradient">Core</span></div>
      <div class="text-muted" style="font-size:0.78rem;">Campus Management System</div>
    </div>
  </div>

  <div class="glass-panel" style="padding: 36px; text-align:center;">
    <div class="pill" style="margin-bottom:16px;">TWO-FACTOR VERIFICATION</div>
    <h1 class="h-display" style="font-size:1.5rem; margin-bottom: 8px;">Hi <?= htmlspecialchars($pendingName, ENT_QUOTES, 'UTF-8') ?>, verify it's you</h1>
    <p class="text-muted" style="font-size:0.85rem; margin-bottom:10px;">Enter the 6-digit code from your authenticator app.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error" style="text-align:left;"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="POST" action="2fa.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="otp-group" id="otpGroup">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" name="code[]" inputmode="numeric" pattern="\d*" maxlength="1" autocomplete="one-time-code" <?= $i === 0 ? 'autofocus' : '' ?>>
        <?php endfor; ?>
      </div>

      <button type="submit" class="btn btn-gradient btn-block">Verify & continue</button>
    </form>

    <div class="divider-label">or</div>
    <a href="index.php" class="text-link">← Back to login</a>
  </div>
</div>

<script>
  // Auto-advance between the 6 OTP boxes and support pasting a full code.
  const boxes = Array.from(document.querySelectorAll('#otpGroup input'));
  boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '').slice(0, 1);
      if (box.value && boxes[i + 1]) boxes[i + 1].focus();
    });
    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && boxes[i - 1]) boxes[i - 1].focus();
    });
    box.addEventListener('paste', (e) => {
      const digits = (e.clipboardData.getData('text').match(/\d/g) || []).slice(0, 6);
      if (digits.length) {
        e.preventDefault();
        digits.forEach((d, idx) => { if (boxes[idx]) boxes[idx].value = d; });
        boxes[Math.min(digits.length, 6) - 1].focus();
      }
    });
  });
</script>

</body>
</html>