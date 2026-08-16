<?php
/**
 * EduCore - Session, CSRF & RBAC Middleware
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . url('index.php'));
        exit;
    }
}

function requireRole(array $allowedRoles): void
{
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;text-align:center;padding:60px;"><h1>403 — Access Denied</h1><p>You do not have permission to view this page.</p><a href="dashboard.php">← Back to Dashboard</a></div>');
    }
}

function getCurrentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT id, full_name, email, role, photo FROM users WHERE id = ? AND status = "active"');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function logAction(string $action, string $module = 'system'): void
{
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO system_logs (user_id, action, module, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $module,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    } catch (Exception $e) {
        // Silently fail logging
    }
}

function getRoleDashboardPath(string $role): string
{
    require_once __DIR__ . '/../config/app.php';
    $map = [
        'super_admin' => 'admin/dashboard.php',
        'faculty'     => 'faculty/dashboard.php',
        'student'     => 'student/dashboard.php',
        'parent'      => 'parent/dashboard.php',
        'finance'     => 'finance/dashboard.php',
        'librarian'   => 'library/dashboard.php',
        'tpo'         => 'placements/dashboard.php',
    ];
    return url($map[$role] ?? 'student/dashboard.php');
}

function getRoleLabel(string $role): string
{
    $labels = [
        'super_admin' => 'Super Admin',
        'faculty'     => 'Faculty',
        'student'     => 'Student',
        'parent'      => 'Parent',
        'finance'     => 'Finance Officer',
        'librarian'   => 'Librarian',
        'tpo'         => 'TPO',
    ];
    return $labels[$role] ?? ucfirst($role);
}

function getRoleColor(string $role): string
{
    $colors = [
        'super_admin' => '#ef4444',
        'faculty'     => '#8b5cf6',
        'student'     => '#06b6d4',
        'parent'      => '#10b981',
        'finance'     => '#f59e0b',
        'librarian'   => '#ec4899',
        'tpo'         => '#6366f1',
    ];
    return $colors[$role] ?? '#6366f1';
}