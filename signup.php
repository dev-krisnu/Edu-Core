<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$message = '';
$error = '';

if (isset($_POST['signup'])) {
    $name = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $db = getDB();
            $check = $db->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hash, $phone, $role]);
                $message = 'Account created successfully! You can now login.';
            }
        } catch (Exception $e) {
            $error = 'Database error. Please import database/schema.sql first.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduCore — Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/educore.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-container fade-in" style="max-width:600px;grid-template-columns:1fr">
        <div class="login-form-panel">
            <div class="text-center mb-4">
                <div class="brand-logo d-inline-flex mb-3" style="width:60px;height:60px;background:var(--bg-gradient);border-radius:16px;color:white;font-size:1.5rem">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h2 style="font-weight:800">Create Account</h2>
                <p class="text-muted">Join EduCore — AI-Powered Educational ERP</p>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-floating-custom">
                    <label>Full Name</label>
                    <input type="text" name="fullname" required placeholder="John Doe">
                </div>
                <div class="form-floating-custom">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="you@educore.edu">
                </div>
                <div class="form-floating-custom">
                    <label>Phone</label>
                    <input type="tel" name="phone" placeholder="+91 9876543210">
                </div>
                <div class="form-floating-custom">
                    <label>Role</label>
                    <select name="role" class="form-select" style="border-radius:12px;padding:12px 16px;border:2px solid var(--border-light)">
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="parent">Parent</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-floating-custom">
                            <label>Password</label>
                            <input type="password" name="password" required placeholder="••••••••">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating-custom">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" required placeholder="••••••••">
                        </div>
                    </div>
                </div>
                <button type="submit" name="signup" class="btn btn-gradient w-100 py-3 mt-2">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="index.php" class="text-decoration-none">Already have an account? Sign In →</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
