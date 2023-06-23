<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff();

$pdo = get_pdo();

// Naive: straightforward query (no user input here, but part of the pattern)
$loans = $pdo->query("
    SELECT l.*, b.Title, m.Name AS MemberName, m.Email,
           DATEDIFF(CURDATE(), l.DueDate) AS DaysOverdue,
           DATEDIFF(CURDATE(), l.DueDate) * " . FINE_PER_DAY . " AS EstimatedFine
    FROM loan l
    JOIN book b ON l.Book_id = b.Book_id
    JOIN member m ON l.Member_id = m.Member_id
    WHERE l.ReturnDate IS NULL AND l.DueDate < CURDATE()
    ORDER BY l.DueDate ASC
")->fetchAll();

$pageTitle = 'Overdue Report — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Overdue Loans</h2>

<?php if (empty($loans)): ?>
<div class="alert alert-success">No overdue loans.</div>
<?php else: ?>
<p class="text-muted"><?= count($loans) ?> overdue loan(s) found.</p>
<table class="table table-hover">
    <thead>
        <tr>
            <th>Book</th>
            <th>Member</th>
            <th>Email</th>
            <th>Due Date</th>
            <th>Days Overdue</th>
            <th>Est. Fine</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($loans as $l): ?>
    <tr class="table-danger">
        <td><a href="/book.php?id=<?= e($l['Book_id']) ?>"><?= e($l['Title']) ?></a></td>
        <td><?= e($l['MemberName']) ?></td>
        <td><?= e($l['Email']) ?></td>
        <td><?= e($l['DueDate']) ?></td>
        <td class="fw-bold text-danger"><?= e($l['DaysOverdue']) ?></td>
        <td>$<?= number_format((float)$l['EstimatedFine'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5">Total estimated fines:</th>
            <th>$<?= number_format(array_sum(array_column($loans, 'EstimatedFine')), 2) ?></th>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<a href="/staff_dashboard.php" class="btn btn-outline-secondary mt-2">Back to Dashboard</a>

<?php require __DIR__ . '/partials/footer.php'; ?>
