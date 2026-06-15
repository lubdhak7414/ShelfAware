<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = get_pdo();

$search        = trim($_GET['q'] ?? '');
$author_search = trim($_GET['author'] ?? '');
$cat_id        = (int)($_GET['cat'] ?? 0);
$available_only = !empty($_GET['available']);
$sort          = $_GET['sort'] ?? 'title_asc';
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 25;

// Allowed sort options mapped to ORDER BY clauses
$sort_options = [
    'title_asc'   => 'b.Title ASC',
    'author_asc'  => 'b.Author ASC',
    'year_desc'   => 'b.Year DESC',
];
$order_by = $sort_options[$sort] ?? 'b.Title ASC';

// Fetch categories with per-category book counts for the filter dropdown
$categories = $pdo->query(
    "SELECT c.*, COUNT(b.Book_id) AS BookCount
     FROM category c
     LEFT JOIN book b ON b.Category_id = c.Category_id
     GROUP BY c.Category_id
     ORDER BY c.Name"
)->fetchAll();

// Build WHERE clause dynamically
$conditions = ["1=1"];
$params     = [];

if ($search !== '') {
    $conditions[] = "(b.Title LIKE ? OR b.ISBN LIKE ?)";
    $like          = '%' . $search . '%';
    $params[]      = $like;
    $params[]      = $like;
}

if ($author_search !== '') {
    $conditions[] = "b.Author LIKE ?";
    $params[]     = '%' . $author_search . '%';
}

if ($cat_id > 0) {
    $conditions[] = "b.Category_id = ?";
    $params[]     = $cat_id;
}

if ($available_only) {
    $conditions[] = "b.CopiesAvailable > 0";
}

$where = "WHERE " . implode(" AND ", $conditions);

// Total count for pagination
$cnt_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM book b JOIN category c ON b.Category_id = c.Category_id $where"
);
$cnt_stmt->execute($params);
$total_books = (int)$cnt_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_books / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Fetch page of books
$sql  = "SELECT b.*, c.Name AS CategoryName
         FROM book b JOIN category c ON b.Category_id = c.Category_id
         $where
         ORDER BY $order_by
         LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$pageTitle = APP_NAME . ' — Catalogue';
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Library Catalogue</h2>

<form method="get" class="mb-4">
    <div class="row g-2 mb-2">
        <div class="col-sm-5">
            <input type="text" name="q" class="form-control"
                   placeholder="Search title or ISBN&hellip;"
                   value="<?= e($search) ?>">
        </div>
        <div class="col-sm-4">
            <input type="text" name="author" class="form-control"
                   placeholder="Search by author&hellip;"
                   value="<?= e($author_search) ?>">
        </div>
        <div class="col-sm-3">
            <select name="cat" class="form-select">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat['Category_id']) ?>"
                    <?= $cat_id === (int)$cat['Category_id'] ? 'selected' : '' ?>>
                    <?= e($cat['Name']) ?> (<?= (int)$cat['BookCount'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row g-2 align-items-center">
        <div class="col-sm-3">
            <select name="sort" class="form-select">
                <option value="title_asc"  <?= $sort === 'title_asc'  ? 'selected' : '' ?>>Title A–Z</option>
                <option value="author_asc" <?= $sort === 'author_asc' ? 'selected' : '' ?>>Author A–Z</option>
                <option value="year_desc"  <?= $sort === 'year_desc'  ? 'selected' : '' ?>>Year (newest first)</option>
            </select>
        </div>
        <div class="col-sm-auto">
            <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="available" id="chk_available"
                       value="1" <?= $available_only ? 'checked' : '' ?>>
                <label class="form-check-label" for="chk_available">Show only available</label>
            </div>
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="/index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<?php if (empty($books) && $page === 1): ?>
<div class="alert alert-info">No books found matching your criteria.</div>
<?php else: ?>
<p class="text-muted small mb-3">
    Showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total_books) ?> of <?= $total_books ?> books
</p>
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

<?php if ($total_pages > 1): ?>
<?php
$qs = http_build_query(array_filter([
    'q'         => $search,
    'author'    => $author_search,
    'cat'       => $cat_id ?: null,
    'available' => $available_only ? '1' : null,
    'sort'      => $sort !== 'title_asc' ? $sort : null,
]));
$base = '/index.php' . ($qs ? '?' . $qs . '&' : '?');
?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page - 1 ?>">Previous</a>
        </li>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page + 1 ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
