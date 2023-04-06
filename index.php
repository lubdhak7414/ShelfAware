<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = get_pdo();

$search   = trim($_GET['q'] ?? '');
$cat_id   = (int)($_GET['cat'] ?? 0);

// Fetch categories for filter
$categories = $pdo->query("SELECT * FROM category ORDER BY Name")->fetchAll();

// Build book query
$sql    = "SELECT b.*, c.Name AS CategoryName FROM book b JOIN category c ON b.Category_id = c.Category_id WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql     .= " AND (b.Title LIKE ? OR b.Author LIKE ? OR b.ISBN LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($cat_id > 0) {
    $sql     .= " AND b.Category_id = ?";
    $params[] = $cat_id;
}

$sql .= " ORDER BY b.Title";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$pageTitle = APP_NAME . ' — Catalogue';
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Library Catalogue</h2>

<form method="get" class="row g-2 mb-4">
    <div class="col-sm-6">
        <input type="text" name="q" class="form-control"
               placeholder="Search title, author or ISBN&hellip;"
               value="<?= e($search) ?>">
    </div>
    <div class="col-sm-3">
        <select name="cat" class="form-select">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['Category_id']) ?>"
                <?= $cat_id === (int)$cat['Category_id'] ? 'selected' : '' ?>>
                <?= e($cat['Name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-auto">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="/index.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<?php if (empty($books)): ?>
<div class="alert alert-info">No books found matching your criteria.</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php foreach ($books as $book): ?>
    <div class="col">
        <div class="card h-100 book-card">
            <div class="card-body">
                <h5 class="card-title">
                    <a href="/book.php?id=<?= e($book['Book_id']) ?>" class="text-decoration-none">
                        <?= e($book['Title']) ?>
                    </a>
                </h5>
                <p class="card-text text-muted mb-1"><?= e($book['Author']) ?></p>
                <p class="card-text">
                    <small class="text-muted">
                        <?= e($book['CategoryName']) ?>
                        <?php if ($book['Year']): ?>
                        &middot; <?= e($book['Year']) ?>
                        <?php endif; ?>
                    </small>
                </p>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <?php if ((int)$book['CopiesAvailable'] > 0): ?>
                <span class="badge bg-success">
                    <?= e($book['CopiesAvailable']) ?> / <?= e($book['CopiesTotal']) ?> available
                </span>
                <?php else: ?>
                <span class="badge bg-warning text-dark">All copies on loan</span>
                <?php endif; ?>
                <a href="/book.php?id=<?= e($book['Book_id']) ?>" class="btn btn-sm btn-outline-primary">
                    Details
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
