<?php
/**
 * Library Repository - Book Catalog Management
 * Manage library inventory and book details
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['library']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$category_filter = trim($_GET['category'] ?? '');

// Fetch books
$sql = "SELECT * FROM library_books WHERE 1=1";
$params = [];

if ($category_filter) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY book_title ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$stmt = $pdo->query("SELECT DISTINCT category FROM library_books ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(quantity) as total_qty, SUM(available_copies) as available FROM library_books");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Repository - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .library-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(110, 231, 183, 0.1));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #6EE7B7;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            text-transform: uppercase;
        }

        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 14px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: linear-gradient(120deg, #10B981, #6EE7B7);
            border-color: transparent;
            color: white;
        }

        .books-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .books-table th {
            background: rgba(16, 185, 129, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(16, 185, 129, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .books-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .books-table tbody tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }

        .category-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .availability-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .available {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .low-stock {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
        }

        .out-of-stock {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #10B981, #6EE7B7);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 4px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
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

        @media (max-width: 768px) {
            .books-table {
                font-size: 0.85rem;
            }

            .books-table th,
            .books-table td {
                padding: 8px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
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
            <div class="library-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Book Repository</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Manage library inventory and book catalog</p>
                </div>

                <!-- Statistics -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Books</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_qty']; ?></div>
                        <div class="stat-label">Total Copies</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['available']; ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <a href="?category=" class="filter-btn <?php echo !$category_filter ? 'active' : ''; ?>">
                        All Categories
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?php echo urlencode($cat); ?>" class="filter-btn <?php echo $category_filter === $cat ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Books Table -->
                <h2 class="section-title">
                    <i class="bi bi-book"></i> Books (<?php echo count($books); ?>)
                </h2>

                <?php if (count($books) > 0): ?>
                    <table class="books-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Category</th>
                                <th>Total</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): 
                                $available = $book['available_copies'];
                                if ($available > $book['quantity'] / 2) {
                                    $statusClass = 'available';
                                    $statusText = 'In Stock';
                                } elseif ($available > 0) {
                                    $statusClass = 'low-stock';
                                    $statusText = 'Low Stock';
                                } else {
                                    $statusClass = 'out-of-stock';
                                    $statusText = 'Out of Stock';
                                }
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(substr($book['book_title'], 0, 30)); ?></strong></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                    <td><span class="category-badge"><?php echo htmlspecialchars($book['category']); ?></span></td>
                                    <td><?php echo $book['quantity']; ?></td>
                                    <td><?php echo $book['available_copies']; ?></td>
                                    <td>
                                        <span class="availability-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="alert('Edit: ' + '<?php echo addslashes($book['book_title']); ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(16, 185, 129, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No books found in the selected category.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
