<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = get_pdo();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT b.*, c.Name AS CategoryName FROM book b JOIN category c ON b.Category_id = c.Category_id WHERE b.Book_id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    require __DIR__ . '/partials/header.php';
    echo '<div class="alert alert-danger">Book not found.</div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$message = '';
$error   = '';

// Handle hold request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_hold'])) {
    if (empty($_SESSION['member_id'])) {
        redirect('/login.php?next=/book.php?id=' . $id);
    }
    $mid  = $_SESSION['member_id'];
    $stmt = $pdo->prepare("INSERT INTO hold (Book_id, Member_id, PlacedAt, Status) VALUES (?, ?, NOW(), 'waiting')");
    $stmt->execute([$id, $mid]);
    $message = 'Hold placed successfully.';
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (empty($_SESSION['member_id'])) {
        redirect('/login.php?next=/book.php?id=' . $id);
    }
    $mid     = $_SESSION['member_id'];
    $rating  = (int)($_POST['rating'] ?? 3);
    $rating  = max(1, min(5, $rating));
    $comment = trim($_POST['comment'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO review (Book_id, Member_id, Rating, Comment, CreatedAt) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$id, $mid, $rating, $comment]);
    $message = 'Review submitted. Thank you!';
}

// Average rating
$stmt = $pdo->prepare("SELECT AVG(Rating) AS avg_rating, COUNT(*) AS cnt FROM review WHERE Book_id = ?");
$stmt->execute([$id]);
$avg_row    = $stmt->fetch();
$avg_rating = $avg_row['avg_rating'] ? round($avg_row['avg_rating'], 1) : null;

// Reviews
$stmt = $pdo->prepare("SELECT r.*, m.Name AS MemberName FROM review r JOIN member m ON r.Member_id = m.Member_id WHERE r.Book_id = ? ORDER BY r.CreatedAt DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$pageTitle = e($book['Title']) . ' — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <h2><?= e($book['Title']) ?></h2>
        <p class="lead text-muted"><?= e($book['Author']) ?></p>

        <table class="table table-sm w-auto mb-4">
            <tr><th>Category</th><td><?= e($book['CategoryName']) ?></td></tr>
            <?php if ($book['ISBN']): ?>
            <tr><th>ISBN</th><td><?= e($book['ISBN']) ?></td></tr>
            <?php endif; ?>
            <?php if ($book['Year']): ?>
            <tr><th>Year</th><td><?= e($book['Year']) ?></td></tr>
            <?php endif; ?>
            <tr><th>Copies</th><td><?= e($book['CopiesAvailable']) ?> / <?= e($book['CopiesTotal']) ?> available</td></tr>
            <?php if ($avg_rating): ?>
            <tr><th>Rating</th><td><?= e($avg_rating) ?> / 5 (<?= e($avg_row['cnt']) ?> review<?= $avg_row['cnt'] != 1 ? 's' : '' ?>)</td></tr>
            <?php endif; ?>
        </table>

        <?php if ((int)$book['CopiesAvailable'] === 0 && !empty($_SESSION['member_id'])): ?>
        <form method="post">
            <button type="submit" name="place_hold" class="btn btn-warning mb-4">Place a Hold</button>
        </form>
        <?php elseif ((int)$book['CopiesAvailable'] === 0): ?>
        <p><a href="/login.php?next=/book.php?id=<?= e($id) ?>" class="btn btn-warning mb-4">Log in to place a hold</a></p>
        <?php endif; ?>

        <h4>Reviews</h4>
        <?php if (empty($reviews)): ?>
        <p class="text-muted">No reviews yet.</p>
        <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <strong><?= e($rev['MemberName']) ?></strong>
                    <span class="text-warning"><?= str_repeat('★', (int)$rev['Rating']) ?><?= str_repeat('☆', 5 - (int)$rev['Rating']) ?></span>
                </div>
                <?php if ($rev['Comment']): ?>
                <p class="mt-2 mb-0"><?= e($rev['Comment']) ?></p>
                <?php endif; ?>
                <small class="text-muted"><?= e($rev['CreatedAt']) ?></small>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['member_id'])): ?>
        <div class="card mt-3">
            <div class="card-header">Write a Review</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <select class="form-select w-auto" name="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>"><?= str_repeat('★', $i) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comment (optional)</label>
                        <textarea class="form-control" name="comment" rows="3"></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Availability</div>
            <div class="card-body">
                <?php if ((int)$book['CopiesAvailable'] > 0): ?>
                <p class="text-success fw-bold">Available to borrow</p>
                <?php else: ?>
                <p class="text-danger fw-bold">All copies on loan</p>
                <?php endif; ?>
                <a href="/index.php" class="btn btn-outline-secondary btn-sm">Back to Catalogue</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
