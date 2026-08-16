<?php
/**
 * Library Dashboard
 * Book management, circulation, and inventory
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['librarian']);

$pdo = getDbConnection();

try {
    $totalBooks = $pdo->query('SELECT COUNT(*) as count FROM library_books')->fetch()['count'] ?? 0;
    $availableBooks = $pdo->query('SELECT COUNT(*) as count FROM library_books WHERE total_copies > issued_copies')->fetch()['count'] ?? 0;
    $issuedCopies = $pdo->query('SELECT COALESCE(SUM(issued_copies), 0) as total FROM library_books')->fetch()['total'] ?? 0;
    $activeFines = $pdo->query('SELECT COUNT(*) as count FROM library_circulation WHERE status = "overdue"')->fetch()['count'] ?? 0;
    $recentCirc = $pdo->query('SELECT * FROM library_circulation ORDER BY created_at DESC LIMIT 8')->fetchAll();
} catch (Exception $e) {
    error_log('[Library Dashboard] Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .lib-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .metric-lib {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(110, 231, 183, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .metric-lib:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.25);
        }

        .metric-icon-lib {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, #10B981, #6EE7B7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .metric-number-lib {
            font-size: 2rem;
            font-weight: 800;
            color: #6EE7B7;
            margin-bottom: 4px;
        }

        .metric-text-lib {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .lib-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }

        .lib-action {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.08));
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #6EE7B7;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .lib-action:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(16, 185, 129, 0.15));
            border-color: #10B981;
            transform: translateY(-2px);
        }

        .circ-table {
            width: 100%;
            border-collapse: collapse;
        }

        .circ-table th {
            background: rgba(16, 185, 129, 0.15);
            padding: 12px;
            text-align: left;
            font-weight: 700;
            color: #6EE7B7;
            font-size: 0.85rem;
            border-bottom: 2px solid rgba(16, 185, 129, 0.3);
        }

        .circ-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.15);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .circ-table tr:hover {
            background: rgba(16, 185, 129, 0.08);
        }

        .status-badge-lib {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-issued {
            background: rgba(34, 197, 94, 0.2);
            color: #86EFAC;
        }

        .status-returned {
            background: rgba(59, 130, 246, 0.2);
            color: #93C5FD;
        }

        .status-overdue {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .section-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="portal-page" data-role="library">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="container">
                <!-- Header -->
                <div style="margin-bottom: 40px;">
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0 0 8px 0;">Inventory Management</p>
                    <h1 class="h-display" style="margin: 0;">Library Dashboard</h1>
                </div>

                <!-- Metrics -->
                <div class="lib-metrics">
                    <div class="metric-lib">
                        <div class="metric-icon-lib"><i class="bi bi-book"></i></div>
                        <div class="metric-number-lib"><?php echo $totalBooks; ?></div>
                        <div class="metric-text-lib">Total Books</div>
                    </div>
                    <div class="metric-lib">
                        <div class="metric-icon-lib"><i class="bi bi-check-circle"></i></div>
                        <div class="metric-number-lib"><?php echo $availableBooks; ?></div>
                        <div class="metric-text-lib">Available Books</div>
                    </div>
                    <div class="metric-lib">
                        <div class="metric-icon-lib"><i class="bi bi-hand-index"></i></div>
                        <div class="metric-number-lib"><?php echo $issuedCopies; ?></div>
                        <div class="metric-text-lib">Copies Issued</div>
                    </div>
                    <div class="metric-lib">
                        <div class="metric-icon-lib"><i class="bi bi-exclamation-circle"></i></div>
                        <div class="metric-number-lib"><?php echo $activeFines; ?></div>
                        <div class="metric-text-lib">Overdue Books</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-bottom: 40px;">
                    <h2 class="section-title" style="margin-bottom: 20px;">Quick Actions</h2>
                    <div class="lib-actions">
                        <a href="./repository.php" class="lib-action">
                            <i class="bi bi-collection"></i>
                            <span>Repository</span>
                        </a>
                        <a href="./qrDesk.php" class="lib-action">
                            <i class="bi bi-qr-code"></i>
                            <span>QR Desk</span>
                        </a>
                        <a href="./fines.php" class="lib-action">
                            <i class="bi bi-cash-coin"></i>
                            <span>Fines</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Circulation -->
                <div class="section-box">
                    <h2 class="section-title" style="margin-bottom: 20px;">Recent Activity</h2>
                    <div style="overflow-x: auto;">
                        <table class="circ-table">
                            <thead>
                                <tr>
                                    <th>Book ID</th>
                                    <th>User ID</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentCirc)): ?>
                                    <?php foreach ($recentCirc as $circ): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($circ['book_id'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($circ['user_id'] ?? 'N/A'); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($circ['circ_type'] ?? 'unknown')); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($circ['created_at'])); ?></td>
                                            <td>
                                                <span class="status-badge-lib status-<?php echo $circ['status'] ?? 'issued'; ?>">
                                                    <?php echo ucfirst($circ['status'] ?? 'issued'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: rgba(255, 255, 255, 0.5);">No recent activity</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/educore.js"></script>
</body>
</html>
