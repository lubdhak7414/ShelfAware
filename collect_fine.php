<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff();

$pdo   = get_pdo();
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fine_id = (int)($_POST['fine_id'] ?? 0);

    if ($fine_id === 0) {
        $error = 'Invalid request.';
    } else {
        // Verify the fine exists and is unpaid
        $stmt = $pdo->prepare("SELECT * FROM fine WHERE Fine_id = ? AND Paid = 0");
        $stmt->execute([$fine_id]);
        $fine = $stmt->fetch();

        if (!$fine) {
            $error = 'Fine not found or already collected.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE fine SET Paid = 1, PaidAt = NOW(), CollectedBy = ? WHERE Fine_id = ?"
            );
            $stmt->execute([$_SESSION['staff_id'], $fine_id]);

            $stmt = $pdo->prepare(
                "INSERT INTO activity_log (Staff_id, Action, EntityType, EntityId, CreatedAt)
                 VALUES (?, 'Collected fine', 'fine', ?, NOW())"
            );
            $stmt->execute([$_SESSION['staff_id'], $fine_id]);

            $msg = 'Fine #' . $fine_id . ' marked as collected.';
        }
    }
}

// List all unpaid fines with member name and book title
$unpaid = $pdo->query(
    "SELECT f.Fine_id, f.Amount, f.Loan_id,
            m.Name AS MemberName, m.Email AS MemberEmail,
            b.Title AS BookTitle,
            l.DueDate, l.ReturnDate
     FROM fine f
     JOIN loan l ON f.Loan_id = l.Loan_id
     JOIN member m ON l.Member_id = m.Member_id
     JOIN book b ON l.Book_id = b.Book_id
     WHERE f.Paid = 0
     ORDER BY f.Fine_id ASC"
)->fetchAll();

// List recently collected fines (last 20)
$paid = $pdo->query(
    "SELECT f.Fine_id, f.Amount, f.PaidAt,
            m.Name AS MemberName,
            b.Title AS BookTitle,
            s.Username AS CollectedByName
     FROM fine f
     JOIN loan l ON f.Loan_id = l.Loan_id
     JOIN member m ON l.Member_id = m.Member_id
     JOIN book b ON l.Book_id = b.Book_id
     LEFT JOIN staff s ON f.CollectedBy = s.Staff_id
     WHERE f.Paid = 1
     ORDER BY f.PaidAt DESC
     LIMIT 20"
)->fetchAll();

$pageTitle = 'Fine Collection — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Fine Collection</h2>

<?php if ($msg):   ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<h4>Unpaid Fines</h4>
<?php if (empty($unpaid)): ?>
<div class="alert alert-success">No unpaid fines.</div>
<?php else: ?>
<table class="table table-hover">
    <thead>
        <tr>
            <th>#</th>
            <th>Member</th>
            <th>Book</th>
            <th>Due Date</th>
            <th>Returned</th>
            <th>Amount</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($unpaid as $f): ?>
    <tr class="table-warning">
        <td><?= e($f['Fine_id']) ?></td>
        <td>
            <?= e($f['MemberName']) ?>
            <small class="text-muted d-block"><?= e($f['MemberEmail']) ?></small>
        </td>
        <td><?= e($f['BookTitle']) ?></td>
        <td><?= e($f['DueDate']) ?></td>
        <td><?= $f['ReturnDate'] ? e($f['ReturnDate']) : '<span class="text-danger">Not returned</span>' ?></td>
        <td class="fw-bold">$<?= number_format((float)$f['Amount'], 2) ?></td>
        <td>
            <form method="post" class="d-inline">
                <input type="hidden" name="fine_id" value="<?= e($f['Fine_id']) ?>">
                <button type="submit" class="btn btn-sm btn-success"
                        onclick="return confirm('Mark fine #<?= e($f['Fine_id']) ?> as collected?')">
                    Mark Collected
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5">Total unpaid:</th>
            <th colspan="2">$<?= number_format(array_sum(array_column($unpaid, 'Amount')), 2) ?></th>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<h4 class="mt-5">Recently Collected Fines</h4>
<?php if (empty($paid)): ?>
<p class="text-muted">No fines collected yet.</p>
<?php else: ?>
<table class="table table-sm table-striped">
    <thead>
        <tr><th>#</th><th>Member</th><th>Book</th><th>Amount</th><th>Collected At</th><th>Collected By</th></tr>
    </thead>
    <tbody>
    <?php foreach ($paid as $f): ?>
    <tr>
        <td><?= e($f['Fine_id']) ?></td>
        <td><?= e($f['MemberName']) ?></td>
        <td><?= e($f['BookTitle']) ?></td>
        <td>$<?= number_format((float)$f['Amount'], 2) ?></td>
        <td><?= e($f['PaidAt']) ?></td>
        <td><?= $f['CollectedByName'] ? e($f['CollectedByName']) : '<span class="text-muted">—</span>' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<a href="/staff_dashboard.php" class="btn btn-outline-secondary mt-2">Back to Dashboard</a>

<?php require __DIR__ . '/partials/footer.php'; ?>
