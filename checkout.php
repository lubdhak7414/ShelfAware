<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff();

$pdo   = get_pdo();
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id   = (int)($_POST['book_id'] ?? 0);
    $member_id = (int)($_POST['member_id'] ?? 0);

    if ($book_id === 0 || $member_id === 0) {
        $error = 'Please select both a book and a member.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM book WHERE Book_id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if (!$book) {
            $error = 'Book not found.';
        } elseif ((int)$book['CopiesAvailable'] < 1) {
            $error = 'No copies available to check out.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM member WHERE Member_id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();

            if (!$member) {
                $error = 'Member not found.';
            } else {
                // Reject if member already has an active loan for this book
                $dup = $pdo->prepare("SELECT 1 FROM loan WHERE Book_id = ? AND Member_id = ? AND ReturnDate IS NULL");
                $dup->execute([$book_id, $member_id]);
                if ($dup->fetch()) {
                    $error = 'This member already has an active loan for that book.';
                }
            }

            if (!$error && $member) {
                $stmt = $pdo->prepare("INSERT INTO loan (Book_id, Member_id, LoanDate, DueDate) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY))");
                $stmt->execute([$book_id, $member_id, LOAN_DAYS]);

                $loan_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare("UPDATE book SET CopiesAvailable = CopiesAvailable - 1 WHERE Book_id = ?");
                $stmt->execute([$book_id]);

                $stmt = $pdo->prepare("INSERT INTO activity_log (Staff_id, Action, EntityType, EntityId, CreatedAt) VALUES (?, 'Checked out book', 'loan', ?, NOW())");
                $stmt->execute([$_SESSION['staff_id'], $loan_id]);

                $msg = 'Book checked out successfully. Due in ' . LOAN_DAYS . ' days.';
            }
        }
    }
}

$books   = $pdo->query("SELECT * FROM book WHERE CopiesAvailable > 0 ORDER BY Title")->fetchAll();
$members = $pdo->query("SELECT * FROM member ORDER BY Name")->fetchAll();

$pageTitle = 'Check Out — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Check Out a Book</h2>

<?php if ($msg):   ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Book</label>
                    <select class="form-select" name="book_id" required>
                        <option value="">— Select a book —</option>
                        <?php foreach ($books as $b): ?>
                        <option value="<?= e($b['Book_id']) ?>"
                            <?= isset($_POST['book_id']) && $_POST['book_id'] == $b['Book_id'] ? 'selected' : '' ?>>
                            <?= e($b['Title']) ?> (<?= e($b['CopiesAvailable']) ?> available)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Member</label>
                    <select class="form-select" name="member_id" required>
                        <option value="">— Select a member —</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= e($m['Member_id']) ?>"
                            <?= isset($_POST['member_id']) && $_POST['member_id'] == $m['Member_id'] ? 'selected' : '' ?>>
                            <?= e($m['Name']) ?> (<?= e($m['Email']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">Check Out</button>
                <a href="/staff_dashboard.php" class="btn btn-outline-secondary">Back</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
