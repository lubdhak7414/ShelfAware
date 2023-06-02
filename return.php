<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff();

$pdo   = get_pdo();
$msg   = '';
$error = '';
$fine_added = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = $_POST['loan_id'] ?? '';

    if ($loan_id === '') {
        $error = 'Please select a loan.';
    } else {
        // Naive: raw query with POST data
        $loan = $pdo->query("SELECT l.*, b.Title, b.Book_id FROM loan l JOIN book b ON l.Book_id = b.Book_id WHERE l.Loan_id = $loan_id AND l.ReturnDate IS NULL")->fetch();

        if (!$loan) {
            $error = 'Loan not found or already returned.';
        } else {
            $pdo->beginTransaction();

            // Mark returned
            $pdo->query("UPDATE loan SET ReturnDate = CURDATE() WHERE Loan_id = $loan_id");

            // Calculate fine if overdue
            $due  = new DateTime($loan['DueDate']);
            $now  = new DateTime();
            $diff = $now->diff($due);

            if ($diff->invert && $diff->days > 0) {
                $days   = $diff->days;
                $amount = $days * FINE_PER_DAY;
                $pdo->query("INSERT INTO fine (Loan_id, Amount, Paid) VALUES ($loan_id, $amount, 0)");
                $fine_added = $amount;
            }

            // Restore copy count
            $book_id = $loan['Book_id'];
            $pdo->query("UPDATE book SET CopiesAvailable = CopiesAvailable + 1 WHERE Book_id = $book_id");

            // Activate a waiting hold if one exists
            $hold = $pdo->query("SELECT * FROM hold WHERE Book_id = $book_id AND Status = 'waiting' ORDER BY PlacedAt ASC LIMIT 1")->fetch();
            if ($hold) {
                $hold_id = $hold['Hold_id'];
                $pdo->query("UPDATE hold SET Status = 'ready' WHERE Hold_id = $hold_id");
            }

            // Log activity
            $staff_id = $_SESSION['staff_id'];
            $pdo->query("INSERT INTO activity_log (Staff_id, Action, EntityType, EntityId, CreatedAt)
                         VALUES ($staff_id, 'Processed return', 'loan', $loan_id, NOW())");

            $pdo->commit();

            $msg = 'Return processed for "' . htmlspecialchars($loan['Title']) . '".';
            if ($fine_added !== null) {
                $msg .= ' Fine of $' . number_format($fine_added, 2) . ' applied.';
            }
        }
    }
}

// Active loans
$loans = $pdo->query("SELECT l.*, b.Title, m.Name AS MemberName
                      FROM loan l
                      JOIN book b ON l.Book_id = b.Book_id
                      JOIN member m ON l.Member_id = m.Member_id
                      WHERE l.ReturnDate IS NULL
                      ORDER BY l.DueDate ASC")->fetchAll();

$pageTitle = 'Process Return — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Process Return</h2>

<?php if ($msg):   ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="post">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Select Loan to Return</label>
                    <select class="form-select" name="loan_id" required>
                        <option value="">— Choose a loan —</option>
                        <?php foreach ($loans as $l): ?>
                        <?php $overdue = strtotime($l['DueDate']) < time(); ?>
                        <option value="<?= e($l['Loan_id']) ?>">
                            <?= e($l['Title']) ?> — <?= e($l['MemberName']) ?>
                            (due <?= e($l['DueDate']) ?><?= $overdue ? ' — OVERDUE' : '' ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Process Return</button>
                </div>
            </div>
        </form>
    </div>
</div>

<h5>Currently Active Loans</h5>
<table class="table table-sm table-hover">
    <thead><tr><th>Book</th><th>Member</th><th>Loan Date</th><th>Due Date</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($loans as $l): ?>
    <?php $overdue = strtotime($l['DueDate']) < time(); ?>
    <tr class="<?= $overdue ? 'table-danger' : '' ?>">
        <td><?= e($l['Title']) ?></td>
        <td><?= e($l['MemberName']) ?></td>
        <td><?= e($l['LoanDate']) ?></td>
        <td><?= e($l['DueDate']) ?></td>
        <td><?= $overdue ? '<span class="text-danger fw-bold">Overdue</span>' : 'Active' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>
