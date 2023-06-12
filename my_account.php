<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_login();

$pdo = get_pdo();
$mid = $_SESSION['member_id'];

// Current loans — naive query
$loans = $pdo->query("SELECT l.*, b.Title FROM loan l JOIN book b ON l.Book_id = b.Book_id WHERE l.Member_id = $mid ORDER BY l.LoanDate DESC")->fetchAll();

// Holds — naive query
$holds = $pdo->query("SELECT h.*, b.Title FROM hold h JOIN book b ON h.Book_id = b.Book_id WHERE h.Member_id = $mid ORDER BY h.PlacedAt DESC")->fetchAll();

// Fines — naive query
$fines = $pdo->query("SELECT f.*, b.Title FROM fine f JOIN loan l ON f.Loan_id = l.Loan_id JOIN book b ON l.Book_id = b.Book_id WHERE l.Member_id = $mid ORDER BY f.Fine_id DESC")->fetchAll();

$pageTitle = 'My Account — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">My Account</h2>
<p>Welcome, <strong><?= e($_SESSION['member_name']) ?></strong>.</p>

<h4 class="mt-4">Current Loans</h4>
<?php if (empty($loans)): ?>
<p class="text-muted">No loans on record.</p>
<?php else: ?>
<table class="table table-sm table-hover">
    <thead><tr><th>Book</th><th>Loan Date</th><th>Due Date</th><th>Returned</th></tr></thead>
    <tbody>
    <?php foreach ($loans as $l): ?>
    <?php $overdue = !$l['ReturnDate'] && strtotime($l['DueDate']) < time(); ?>
    <tr class="<?= $overdue ? 'table-danger' : '' ?>">
        <td><?= e($l['Title']) ?></td>
        <td><?= e($l['LoanDate']) ?></td>
        <td><?= e($l['DueDate']) ?></td>
        <td><?= $l['ReturnDate'] ? e($l['ReturnDate']) : ($overdue ? '<span class="text-danger">Overdue</span>' : 'Active') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h4 class="mt-4">My Holds</h4>
<?php if (empty($holds)): ?>
<p class="text-muted">No holds placed.</p>
<?php else: ?>
<table class="table table-sm">
    <thead><tr><th>Book</th><th>Placed</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($holds as $h): ?>
    <tr>
        <td><?= e($h['Title']) ?></td>
        <td><?= e($h['PlacedAt']) ?></td>
        <td>
            <?php $badge = match($h['Status']) {
                'waiting'   => 'bg-warning text-dark',
                'ready'     => 'bg-success',
                'cancelled' => 'bg-secondary',
            }; ?>
            <span class="badge <?= $badge ?>"><?= e($h['Status']) ?></span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h4 class="mt-4">Fines</h4>
<?php if (empty($fines)): ?>
<p class="text-muted">No fines.</p>
<?php else: ?>
<table class="table table-sm">
    <thead><tr><th>Book</th><th>Amount</th><th>Status</th><th>Paid At</th></tr></thead>
    <tbody>
    <?php foreach ($fines as $f): ?>
    <tr class="<?= !$f['Paid'] ? 'table-warning' : '' ?>">
        <td><?= e($f['Title']) ?></td>
        <td>$<?= number_format((float)$f['Amount'], 2) ?></td>
        <td><?= $f['Paid'] ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning text-dark">Unpaid</span>' ?></td>
        <td><?= $f['PaidAt'] ? e($f['PaidAt']) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
