<?php
/**
 * Admin - User Management
 * Manage all system users (students, faculty, staff)
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['admin']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();
$filter_role = trim($_GET['role'] ?? '');

// Fetch users with filter
$sql = "SELECT * FROM users WHERE role != 'super_admin'";
$params = [];
if ($filter_role) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
}
$sql .= " ORDER BY created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by role
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE role != 'super_admin' GROUP BY role");
$roleCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roleCounts[$row['role']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 14px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: linear-gradient(120deg, #EF4444, #FCA5A5);
            border-color: transparent;
            color: white;
        }

        .filter-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .users-table th {
            background: rgba(239, 68, 68, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(239, 68, 68, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .users-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(239, 68, 68, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .users-table tbody tr:hover {
            background: rgba(239, 68, 68, 0.05);
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .role-student {
            background: rgba(99, 102, 241, 0.2);
            color: #93C5FD;
        }

        .role-faculty {
            background: rgba(139, 92, 246, 0.2);
            color: #D8B4FE;
        }

        .role-admin {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .role-finance {
            background: rgba(34, 211, 238, 0.2);
            color: #67E8F9;
        }

        .role-library {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .role-parent {
            background: rgba(249, 115, 22, 0.2);
            color: #FDBA74;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #EF4444, #FCA5A5);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 6px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
        }

        .action-btn.edit {
            background: rgba(99, 102, 241, 0.2);
            color: #93C5FD;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .status-active {
            color: #6EE7B7;
        }

        .status-inactive {
            color: #FCA5A5;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 24px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #FCA5A5;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.6);
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .users-table {
                font-size: 0.85rem;
            }

            .users-table th,
            .users-table td {
                padding: 8px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body data-role="admin">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="admin-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">User Management</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Manage all system users and their roles</p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($users); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <?php foreach ($roleCounts as $role => $count): ?>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $count; ?></div>
                            <div class="stat-label"><?php echo ucfirst($role); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filter Bar -->
                <h2 class="section-title">
                    <i class="bi bi-funnel"></i> Filter by Role
                </h2>
                <div class="filter-bar">
                    <a href="?role=" class="filter-btn <?php echo !$filter_role ? 'active' : ''; ?>">
                        All Users
                    </a>
                    <?php foreach (array_keys($roleCounts) as $role): ?>
                        <a href="?role=<?php echo urlencode($role); ?>" class="filter-btn <?php echo $filter_role === $role ? 'active' : ''; ?>">
                            <?php echo ucfirst($role); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Users Table -->
                <h2 class="section-title">
                    <i class="bi bi-people"></i> Users (<?php echo count($users); ?>)
                </h2>

                <?php if (count($users) > 0): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                $joinedDate = new DateTime($user['created_at']);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                            <?php echo $user['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo rand(0, 1) ? 'active' : 'inactive'; ?>">
                                            <i class="bi bi-circle-fill"></i>
                                            <?php echo rand(0, 1) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $joinedDate->format('M d, Y'); ?></td>
                                    <td>
                                        <button class="action-btn edit" onclick="alert('Edit user: ' + '<?php echo addslashes($user['name']); ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="action-btn" onclick="alert('Delete user: ' + '<?php echo addslashes($user['name']); ?>')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(239, 68, 68, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No users found matching the selected filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
