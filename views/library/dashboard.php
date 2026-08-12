<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['librarian']);
require_once __DIR__ . '/../../config/database.php';

$db = getDB();
$books = $db->query('SELECT * FROM library_books ORDER BY title')->fetchAll();
$totalBooks = array_sum(array_column($books, 'total_copies'));
$available = array_sum(array_column($books, 'available_copies'));
$issued = $totalBooks - $available;

$pageTitle = 'Library Hub';
$basePath = '../..';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header fade-in">
    <h1>Library <span class="gradient-text">Hub</span></h1>
    <p>QR-code circulation, catalog management & fine tracking</p>
</div>

<div class="stat-grid fade-in">
    <div class="stat-card" style="--card-accent:#ec4899">
        <div class="stat-card-value"><?= count($books) ?></div>
        <div class="stat-card-label">Unique Titles</div>
    </div>
    <div class="stat-card" style="--card-accent:#06b6d4">
        <div class="stat-card-value"><?= $totalBooks ?></div>
        <div class="stat-card-label">Total Copies</div>
    </div>
    <div class="stat-card" style="--card-accent:#10b981">
        <div class="stat-card-value"><?= $available ?></div>
        <div class="stat-card-label">Available</div>
    </div>
    <div class="stat-card" style="--card-accent:#f59e0b">
        <div class="stat-card-value"><?= $issued ?></div>
        <div class="stat-card-label">Issued</div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6">
        <a href="qr_desk.php" class="module-card d-block fade-in" style="--module-color:#06b6d4;--icon-bg:rgba(6,182,212,0.1)">
            <div class="module-icon"><i class="bi bi-qr-code-scan"></i></div>
            <h4>QR Circulation Desk</h4>
            <p>Sub-second book issuing & returns via QR scanner</p>
        </a>
    </div>
    <div class="col-lg-6">
        <div class="module-card fade-in" style="--module-color:#8b5cf6;--icon-bg:rgba(139,92,246,0.1)">
            <div class="module-icon"><i class="bi bi-collection"></i></div>
            <h4>Digital Repository</h4>
            <p>ISBN metadata, barcode generator & e-book vault</p>
        </div>
    </div>
</div>

<div class="content-card fade-in">
    <div class="content-card-header"><h3><i class="bi bi-bookshelf me-2" style="color:#ec4899"></i>Book Catalog</h3></div>
    <table class="educore-table">
        <thead><tr><th>QR Code</th><th>Title</th><th>Author</th><th>Category</th><th>Shelf</th><th>Available</th></tr></thead>
        <tbody>
        <?php foreach ($books as $b): ?>
        <tr>
            <td><code><?= htmlspecialchars($b['qr_code']) ?></code></td>
            <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
            <td><?= htmlspecialchars($b['author']) ?></td>
            <td><span class="badge-status badge-active"><?= htmlspecialchars($b['category']) ?></span></td>
            <td><?= htmlspecialchars($b['shelf_location']) ?></td>
            <td><?= $b['available_copies'] ?>/<?= $b['total_copies'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
