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
requireRole(['librarian']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle book edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_book') {
    $book_id = intval($_POST['book_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $total_copies = intval($_POST['total_copies'] ?? 0);
    $available_copies = intval($_POST['available_copies'] ?? 0);

    if ($book_id && $title && $total_copies >= 0 && $available_copies >= 0 && $available_copies <= $total_copies) {
        try {
            $stmt = $pdo->prepare("
                UPDATE library_books SET title = ?, author = ?, category = ?, total_copies = ?, available_copies = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $author, $category, $total_copies, $available_copies, $book_id]);
            $message = 'Book updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = 'Available copies cannot exceed total copies.';
        $messageType = 'error';
    }
}

$category_filter = trim($_GET['category'] ?? '');

// Fetch books
$sql = "SELECT * FROM library_books WHERE 1=1";
$params = [];

if ($category_filter) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY title ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$stmt = $pdo->query("SELECT DISTINCT category FROM library_books ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(total_copies) as total_qty, SUM(available_copies) as available FROM library_books");
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

                <?php if ($message): ?>
                    <div style="padding:14px; border-radius:8px; margin-bottom:20px; border-left:4px solid <?php echo $messageType === 'success' ? '#10B981' : '#EF4444'; ?>; background:rgba(<?php echo $messageType === 'success' ? '16,185,129' : '239,68,68'; ?>,0.1); color:<?php echo $messageType === 'success' ? '#6EE7B7' : '#FCA5A5'; ?>;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

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
                                if ($available > $book['total_copies'] / 2) {
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
                                    <td><strong><?php echo htmlspecialchars(substr($book['title'], 0, 30)); ?></strong></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                    <td><span class="category-badge"><?php echo htmlspecialchars($book['category']); ?></span></td>
                                    <td><?php echo $book['total_copies']; ?></td>
                                    <td><?php echo $book['available_copies']; ?></td>
                                    <td>
                                        <span class="availability-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" type="button"
                                            onclick='openEditBookModal(<?php echo json_encode([
                                                "id" => $book["id"],
                                                "title" => $book["title"],
                                                "author" => $book["author"],
                                                "category" => $book["category"],
                                                "total_copies" => $book["total_copies"],
                                                "available_copies" => $book["available_copies"],
                                            ]); ?>)'>
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

    <!-- Edit Book Modal -->
    <div id="editBookOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
        <div style="background:#1a1a3e; border:1px solid rgba(16,185,129,0.3); border-radius:16px; padding:28px; max-width:420px; width:90%;">
            <h2 style="color:#F5F4FF; margin:0 0 20px 0;"><i class="bi bi-pencil-square"></i> Edit Book</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_book">
                <input type="hidden" name="book_id" id="edit_book_id">
                <?php foreach ([['title','Title','text'],['author','Author','text'],['category','Category','text'],['total_copies','Total Copies','number'],['available_copies','Available Copies','number']] as [$field, $label, $type]): ?>
                <div style="margin-bottom:12px;">
                    <label style="display:block; margin-bottom:6px; color:rgba(245,244,255,0.8);"><?php echo $label; ?></label>
                    <input type="<?php echo $type; ?>" name="<?php echo $field; ?>" id="edit_<?php echo $field; ?>"
                        style="width:100%; padding:10px; border-radius:8px; border:1px solid rgba(16,185,129,0.3); background:rgba(255,255,255,0.05); color:#F5F4FF;" required>
                </div>
                <?php endforeach; ?>
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="submit" class="action-btn" style="flex:1; background:linear-gradient(120deg,#10B981,#6EE7B7);">Save Changes</button>
                    <button type="button" class="action-btn" onclick="document.getElementById('editBookOverlay').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openEditBookModal(book) {
            document.getElementById('edit_book_id').value = book.id;
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_category').value = book.category;
            document.getElementById('edit_total_copies').value = book.total_copies;
            document.getElementById('edit_available_copies').value = book.available_copies;
            document.getElementById('editBookOverlay').style.display = 'flex';
        }
    </script>
</body>
</html>
