<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff();

$pdo = get_pdo();

// Summary stats
$book_count   = $pdo->query("SELECT COUNT(*) FROM book")->fetchColumn();
$member_count = $pdo->query("SELECT COUNT(*) FROM member")->fetchColumn();
$active_loans = $pdo->query("SELECT COUNT(*) FROM loan WHERE ReturnDate IS NULL")->fetchColumn();
$overdue      = $pdo->query("SELECT COUNT(*) FROM loan WHERE ReturnDate IS NULL AND DueDate < CURDATE()")->fetchColumn();
$pending_holds= $pdo->query("SELECT COUNT(*) FROM hold WHERE Status = 'waiting'")->fetchColumn();
$unpaid_fines = $pdo->query("SELECT COALESCE(SUM(Amount),0) FROM fine WHERE Paid = 0")->fetchColumn();

$pageTitle = 'Staff Dashboard — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Staff Dashboard</h2>
<p class="text-muted">Welcome back, <strong><?= e($_SESSION['staff_username']) ?></strong>
    <span class="badge bg-secondary"><?= e($_SESSION['staff_role']) ?></span>
</p>

<div class="row g-3 mb-5">
    <div class="col-sm-4 col-lg-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3><?= e($book_count) ?></h3>
                <p class="mb-0 text-muted">Books</p>
            </div>
        </div>
    </div>
    <div class="col-sm-4 col-lg-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3><?= e($member_count) ?></h3>
                <p class="mb-0 text-muted">Members</p>
            </div>
        </div>
    </div>
    <div class="col-sm-4 col-lg-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3><?= e($active_loans) ?></h3>
                <p class="mb-0 text-muted">Active Loans</p>
            </div>
        </div>
    </div>
    <div class="col-sm-4 col-lg-2">
        <div class="card text-center h-100 <?= $overdue > 0 ? 'border-danger' : '' ?>">
            <div class="card-body">
                <h3 class="<?= $overdue > 0 ? 'text-danger' : '' ?>"><?= e($overdue) ?></h3>
                <p class="mb-0 text-muted">Overdue</p>
            </div>
        </div>
    </div>
    <div class="col-sm-4 col-lg-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3><?= e($pending_holds) ?></h3>
                <p class="mb-0 text-muted">Holds</p>
            </div>
        </div>
    </div>
    <div class="col-sm-4 col-lg-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3>$<?= number_format((float)$unpaid_fines, 2) ?></h3>
                <p class="mb-0 text-muted">Unpaid Fines</p>
            </div>
        </div>
    </div>
</div>

<h4 class="mb-3">Quick Actions</h4>
<div class="row g-2">
    <div class="col-auto"><a href="/checkout.php" class="btn btn-success">Check Out Book</a></div>
    <div class="col-auto"><a href="/return.php" class="btn btn-primary">Process Return</a></div>
    <div class="col-auto"><a href="/manage_books.php" class="btn btn-outline-secondary">Manage Books</a></div>
    <div class="col-auto"><a href="/holds.php" class="btn btn-outline-secondary">View Holds</a></div>
    <div class="col-auto"><a href="/overdue.php" class="btn btn-outline-danger">Overdue Report</a></div>
    <?php if ($_SESSION['staff_role'] === 'admin'): ?>
    <div class="col-auto"><a href="/staff_admin.php" class="btn btn-outline-dark">Admin Panel</a></div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
