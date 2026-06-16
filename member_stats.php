<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff('admin');

$pdo = get_pdo();

// Main stats query: one row per member with loan/fine aggregates
$stats = $pdo->query(
    "SELECT
         m.Member_id,
         m.Name,
         m.Email,
         m.JoinDate,
         COUNT(DISTINCT l.Loan_id)                                                AS TotalLoans,
         SUM(CASE WHEN l.ReturnDate IS NOT NULL AND l.ReturnDate <= l.DueDate THEN 1 ELSE 0 END) AS OnTimeReturns,
         SUM(CASE WHEN l.ReturnDate IS NOT NULL AND l.ReturnDate > l.DueDate  THEN 1 ELSE 0 END) AS LateReturns,
         ROUND(AVG(CASE WHEN l.ReturnDate IS NOT NULL AND l.ReturnDate > l.DueDate
                        THEN DATEDIFF(l.ReturnDate, l.DueDate) ELSE NULL END), 1)  AS AvgDaysOverdue,
         COUNT(DISTINCT f.Fine_id)                                                AS TotalFines,
         SUM(CASE WHEN f.Paid = 0 THEN 1 ELSE 0 END)                             AS UnpaidFines,
         COALESCE(SUM(f.Amount), 0)                                               AS TotalFineAmount,
         COALESCE(SUM(CASE WHEN f.Paid = 0 THEN f.Amount ELSE 0 END), 0)         AS UnpaidFineAmount
     FROM member m
     LEFT JOIN loan l ON l.Member_id = m.Member_id
     LEFT JOIN fine f ON f.Loan_id = l.Loan_id
     GROUP BY m.Member_id
     ORDER BY TotalLoans DESC, m.Name ASC"
)->fetchAll();

// Most borrowed genre per member (subquery approach — one query per member to keep SQL readable)
$genre_cache = [];
$genre_stmt  = $pdo->prepare(
    "SELECT c.Name AS CategoryName, COUNT(*) AS cnt
     FROM loan l
     JOIN book b ON l.Book_id = b.Book_id
     JOIN category c ON b.Category_id = c.Category_id
     WHERE l.Member_id = ?
     GROUP BY c.Category_id
     ORDER BY cnt DESC
     LIMIT 1"
);
foreach ($stats as $row) {
    $genre_stmt->execute([$row['Member_id']]);
    $top = $genre_stmt->fetch();
    $genre_cache[$row['Member_id']] = $top ? $top['CategoryName'] : '—';
}

$pageTitle = 'Member Borrowing Stats — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Member Borrowing Statistics</h2>
<p class="text-muted">Admin view — one row per registered member.</p>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <th>Member</th>
            <th>Joined</th>
            <th class="text-center">Total<br>Loans</th>
            <th class="text-center">On-Time<br>Returns</th>
            <th class="text-center">Late<br>Returns</th>
            <th class="text-center">Avg Days<br>Overdue</th>
            <th class="text-center">Fines<br>Issued</th>
            <th class="text-center">Unpaid<br>Fines</th>
            <th class="text-end">Total Fine<br>Amount</th>
            <th class="text-end">Unpaid<br>Amount</th>
            <th>Top Genre</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($stats)): ?>
    <tr><td colspan="11" class="text-center text-muted">No members found.</td></tr>
    <?php endif; ?>
    <?php foreach ($stats as $s): ?>
    <?php $has_debt = (float)$s['UnpaidFineAmount'] > 0; ?>
    <tr class="<?= $has_debt ? 'table-warning' : '' ?>">
        <td>
            <strong><?= e($s['Name']) ?></strong>
            <small class="text-muted d-block"><?= e($s['Email']) ?></small>
        </td>
        <td><?= e($s['JoinDate']) ?></td>
        <td class="text-center"><?= (int)$s['TotalLoans'] ?></td>
        <td class="text-center text-success fw-semibold"><?= (int)$s['OnTimeReturns'] ?></td>
        <td class="text-center <?= (int)$s['LateReturns'] > 0 ? 'text-danger fw-semibold' : '' ?>">
            <?= (int)$s['LateReturns'] ?>
        </td>
        <td class="text-center">
            <?= $s['AvgDaysOverdue'] !== null ? e($s['AvgDaysOverdue']) . ' d' : '—' ?>
        </td>
        <td class="text-center"><?= (int)$s['TotalFines'] ?></td>
        <td class="text-center <?= (int)$s['UnpaidFines'] > 0 ? 'text-danger fw-bold' : '' ?>">
            <?= (int)$s['UnpaidFines'] ?>
        </td>
        <td class="text-end">$<?= number_format((float)$s['TotalFineAmount'], 2) ?></td>
        <td class="text-end <?= $has_debt ? 'text-danger fw-bold' : '' ?>">
            <?= $has_debt ? '$' . number_format((float)$s['UnpaidFineAmount'], 2) : '—' ?>
        </td>
        <td><?= e($genre_cache[$s['Member_id']]) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot class="table-secondary fw-semibold">
        <tr>
            <td colspan="2">Totals</td>
            <td class="text-center"><?= array_sum(array_column($stats, 'TotalLoans')) ?></td>
            <td class="text-center"><?= array_sum(array_column($stats, 'OnTimeReturns')) ?></td>
            <td class="text-center"><?= array_sum(array_column($stats, 'LateReturns')) ?></td>
            <td></td>
            <td class="text-center"><?= array_sum(array_column($stats, 'TotalFines')) ?></td>
            <td class="text-center"><?= array_sum(array_column($stats, 'UnpaidFines')) ?></td>
            <td class="text-end">$<?= number_format(array_sum(array_column($stats, 'TotalFineAmount')), 2) ?></td>
            <td class="text-end">$<?= number_format(array_sum(array_column($stats, 'UnpaidFineAmount')), 2) ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>
</div>

<a href="/staff_admin.php" class="btn btn-outline-secondary mt-2">Back to Admin Panel</a>

<?php require __DIR__ . '/partials/footer.php'; ?>
