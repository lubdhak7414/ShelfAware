<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_login();

$pdo = get_pdo();
$mid = (int)$_SESSION['member_id'];
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = (int)($_POST['loan_id'] ?? 0);

    if ($loan_id === 0) {
        $error = 'Invalid request.';
    } else {
        // Fetch loan — must belong to this member and be active
        $stmt = $pdo->prepare(
            "SELECT l.*, b.Title, b.Book_id
             FROM loan l
             JOIN book b ON l.Book_id = b.Book_id
             WHERE l.Loan_id = ? AND l.Member_id = ? AND l.ReturnDate IS NULL"
        );
        $stmt->execute([$loan_id, $mid]);
        $loan = $stmt->fetch();

        if (!$loan) {
            $error = 'Loan not found or you do not have permission to renew it.';
        } elseif ((int)$loan['renewals'] >= 2) {
            $error = 'This loan has already been renewed the maximum number of times (2). Please return the book.';
        } else {
            // Check for an active hold (Status = waiting) on this book
            $hold_stmt = $pdo->prepare(
                "SELECT 1 FROM hold WHERE Book_id = ? AND Status = 'waiting' LIMIT 1"
            );
            $hold_stmt->execute([$loan['Book_id']]);
            $has_hold = $hold_stmt->fetch();

            if ($has_hold) {
                $error = 'This book cannot be renewed because another member is waiting for it. Please return it by the due date.';
            } else {
                // Extend due date by 14 days and increment renewals counter
                $stmt = $pdo->prepare(
                    "UPDATE loan
                     SET DueDate = DATE_ADD(DueDate, INTERVAL 14 DAY),
                         renewals = renewals + 1
                     WHERE Loan_id = ?"
                );
                $stmt->execute([$loan_id]);
                $msg = 'Loan renewed successfully for "' . htmlspecialchars($loan['Title']) . '". Your new due date is 14 days from the previous due date.';
            }
        }
    }
}

// Fetch active loans for this member to show the renew form
$stmt = $pdo->prepare(
    "SELECT l.*, b.Title, b.Book_id,
            (SELECT COUNT(1) FROM hold h WHERE h.Book_id = l.Book_id AND h.Status = 'waiting') AS WaitingHolds
     FROM loan l
     JOIN book b ON l.Book_id = b.Book_id
     WHERE l.Member_id = ? AND l.ReturnDate IS NULL
     ORDER BY l.DueDate ASC"
);
$stmt->execute([$mid]);
$active_loans = $stmt->fetchAll();

$pageTitle = 'Renew Loans — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Renew a Loan</h2>

<?php if ($msg):   ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (empty($active_loans)): ?>
<div class="alert alert-info">You have no active loans to renew.</div>
<?php else: ?>
<p class="text-muted">Loans can be renewed up to 2 times, and only when no other member is waiting for the book.</p>
<table class="table table-hover">
    <thead>
        <tr>
            <th>Book</th>
            <th>Due Date</th>
            <th>Renewals Used</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($active_loans as $l): ?>
    <?php
        $overdue     = strtotime($l['DueDate']) < time();
        $can_renew   = (int)$l['renewals'] < 2 && (int)$l['WaitingHolds'] === 0;
        $row_class   = $overdue ? 'table-danger' : '';
    ?>
    <tr class="<?= $row_class ?>">
        <td><?= e($l['Title']) ?></td>
        <td>
            <?= e($l['DueDate']) ?>
            <?php if ($overdue): ?>
            <span class="badge bg-danger ms-1">Overdue</span>
            <?php endif; ?>
        </td>
        <td>
            <?= (int)$l['renewals'] ?> / 2
            <?php if ((int)$l['WaitingHolds'] > 0): ?>
            <span class="badge bg-warning text-dark ms-1">Hold pending</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($can_renew): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="loan_id" value="<?= e($l['Loan_id']) ?>">
                <button type="submit" class="btn btn-sm btn-primary">Renew (+14 days)</button>
            </form>
            <?php elseif ((int)$l['renewals'] >= 2): ?>
            <span class="text-muted small">Max renewals reached</span>
            <?php else: ?>
            <span class="text-muted small">Hold by another member</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<a href="/my_account.php" class="btn btn-outline-secondary mt-2">Back to My Account</a>

<?php require __DIR__ . '/partials/footer.php'; ?>
