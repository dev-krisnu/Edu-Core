<?php
/**
 * Library Search - Book Catalog Search
 * Search and browse library books with QR code scanning
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$books = [];
$searchQuery = '';
$selectedCategory = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searchQuery = trim($_GET['search'] ?? '');
    $selectedCategory = trim($_GET['category'] ?? '');
    
    $sql = "SELECT *, title AS book_title FROM library_books WHERE 1=1";
    $params = [];
    
    if ($searchQuery) {
        $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
        $params = ["%$searchQuery%", "%$searchQuery%", "%$searchQuery%"];
    }
    
    if ($selectedCategory) {
        $sql .= " AND category = ?";
        $params[] = $selectedCategory;
    }
    
    $sql .= " ORDER BY title ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get categories
$stmt = $pdo->query("SELECT DISTINCT category FROM library_books ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get user's borrowed books
$stmt = $pdo->prepare("
    SELECT lc.*, lb.title AS book_title
    FROM library_circulation lc
    JOIN library_books lb ON lc.book_id = lb.id
    WHERE lc.student_id = ? AND lc.returned_at IS NULL
    ORDER BY lc.issued_at DESC
");
$stmt->execute([$currentUser['id']]);
$borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Search - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .library-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-section {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr 200px auto;
            gap: 12px;
        }

        .search-form input,
        .search-form select {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .search-form input::placeholder {
            color: rgba(245, 244, 255, 0.4);
        }

        .search-form input:focus,
        .search-form select:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .search-btn {
            padding: 12px 24px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
        }

        .book-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .book-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.2);
        }

        .book-cover {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #6366F1, #22D3EE);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.3);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        }

        .book-info {
            padding: 14px;
        }

        .book-title {
            font-weight: 700;
            color: #F5F4FF;
            font-size: 0.95rem;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .book-author {
            color: rgba(245, 244, 255, 0.6);
            font-size: 0.8rem;
            margin-bottom: 6px;
        }

        .book-category {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(99, 102, 241, 0.2);
            border-radius: 4px;
            color: rgba(245, 244, 255, 0.8);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .availability-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 6px;
        }

        .available {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .unavailable {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .borrowed-section {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-top: 40px;
        }

        .borrowed-section h2 {
            margin: 0 0 16px 0;
            color: #F5F4FF;
        }

        .borrowed-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 12px;
        }

        .borrowed-item {
            background: rgba(139, 92, 246, 0.1);
            border-left: 4px solid #8B5CF6;
            padding: 12px;
            border-radius: 8px;
        }

        .borrowed-title {
            font-weight: 600;
            color: #F5F4FF;
            margin-bottom: 4px;
        }

        .borrowed-meta {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: rgba(245, 244, 255, 0.6);
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .borrowed-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="student">
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
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Library Search</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Search and borrow books from our collection</p>
                </div>

                <!-- Search Section -->
                <div class="search-section">
                    <form method="GET" class="search-form">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search by book title, author, or ISBN..." 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                        >
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selectedCategory === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="search-btn">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </form>
                </div>

                <!-- Books Grid -->
                <?php if (count($books) > 0): ?>
                    <div class="books-grid">
                        <?php foreach ($books as $book): 
                            $isAvailable = $book['available_copies'] > 0;
                        ?>
                            <div class="book-card" onclick='openBookDetailModal(<?php echo json_encode([
                                "title" => $book["book_title"],
                                "author" => $book["author"],
                                "isbn" => $book["isbn"] ?? "N/A",
                                "category" => $book["category"],
                                "shelf" => $book["shelf_location"] ?? "Ask librarian",
                                "available" => (int) $book["available_copies"],
                                "total" => (int) $book["total_copies"],
                            ]); ?>)'>
                                <div class="book-cover">
                                    <?php echo strtoupper(substr($book['book_title'], 0, 2)); ?>
                                </div>
                                <div class="book-info">
                                    <div class="book-title"><?php echo htmlspecialchars(substr($book['book_title'], 0, 30)); ?></div>
                                    <div class="book-author">by <?php echo htmlspecialchars(substr($book['author'], 0, 20)); ?></div>
                                    <div style="margin: 8px 0 0 0;">
                                        <span class="book-category"><?php echo htmlspecialchars($book['category']); ?></span>
                                        <span class="availability-badge <?php echo $isAvailable ? 'available' : 'unavailable'; ?>">
                                            <?php echo $isAvailable ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="bi bi-search"></i>
                        <p>No books found matching your search criteria.</p>
                    </div>
                <?php endif; ?>

                <!-- Borrowed Books Section -->
                <?php if (count($borrowedBooks) > 0): ?>
                    <div class="borrowed-section">
                        <h2><i class="bi bi-bookmark-fill"></i> My Borrowed Books (<?php echo count($borrowedBooks); ?>)</h2>
                        <div class="borrowed-list">
                            <?php foreach ($borrowedBooks as $borrowed): 
                                $dueDate = new DateTime($borrowed['due_date']);
                                $today = new DateTime();
                                $isOverdue = $dueDate < $today;
                            ?>
                                <div class="borrowed-item">
                                    <div class="borrowed-title"><?php echo htmlspecialchars($borrowed['book_title']); ?></div>
                                    <div class="borrowed-meta">
                                        <strong>Issued:</strong> <?php echo date('M d, Y', strtotime($borrowed['issued_date'])); ?><br>
                                        <strong>Due:</strong> <?php echo date('M d, Y', strtotime($borrowed['due_date'])); ?>
                                        <?php if ($isOverdue): ?>
                                            <br><span style="color: #FCA5A5;"><strong>⚠ OVERDUE</strong></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="bookDetailOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;" onclick="if(event.target===this) document.getElementById('bookDetailOverlay').style.display='none'">
        <div style="background:#1a1a3e; border:1px solid rgba(99,102,241,0.3); border-radius:16px; padding:28px; max-width:440px; width:90%;">
            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:16px;">
                <h2 id="bd_title" style="color:#F5F4FF; margin:0;"></h2>
                <button type="button" onclick="document.getElementById('bookDetailOverlay').style.display='none'" style="background:none; border:none; color:rgba(245,244,255,0.6); font-size:1.4rem; cursor:pointer;">&times;</button>
            </div>
            <p style="color:rgba(245,244,255,0.7);">by <span id="bd_author"></span></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-tag"></i> <span id="bd_category"></span></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-upc-scan"></i> ISBN: <span id="bd_isbn"></span></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-signpost"></i> Shelf: <span id="bd_shelf"></span></p>
            <p style="color:rgba(245,244,255,0.6);"><i class="bi bi-stack"></i> <span id="bd_copies"></span></p>
            <p style="color:rgba(245,244,255,0.85); margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.1); font-size:0.9rem;">
                To check this book out, visit the library counter with your Student ID — circulation is handled in person via the library's QR desk.
            </p>
        </div>
    </div>
    <script>
        function openBookDetailModal(b) {
            document.getElementById('bd_title').textContent = b.title;
            document.getElementById('bd_author').textContent = b.author;
            document.getElementById('bd_category').textContent = b.category;
            document.getElementById('bd_isbn').textContent = b.isbn;
            document.getElementById('bd_shelf').textContent = b.shelf;
            document.getElementById('bd_copies').textContent = b.available + ' of ' + b.total + ' copies available';
            document.getElementById('bookDetailOverlay').style.display = 'flex';
        }
    </script>
</body>
</html>
