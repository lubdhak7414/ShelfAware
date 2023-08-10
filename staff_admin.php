<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff('admin');

$pdo   = get_pdo();
$msg   = '';
$error = '';

// Add new staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['librarian', 'admin']) ? $_POST['role'] : 'librarian';
    $pass     = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $pass === '') {
        $error = 'All fields are required.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO staff (Username, Password, Email, Role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $email, $role]);

        $new_id = $pdo->lastInsertId();
        $action = 'Added staff account: ' . $username;
        $stmt   = $pdo->prepare("INSERT INTO activity_log (Staff_id, Action, EntityType, EntityId, CreatedAt) VALUES (?, ?, 'staff', ?, NOW())");
        $stmt->execute([$_SESSION['staff_id'], $action, $new_id]);
        $msg = 'Staff account created.';
    }
}

// Delete staff
if (isset($_GET['delete_staff'])) {
    $sid = (int)$_GET['delete_staff'];
    if ($sid !== (int)$_SESSION['staff_id']) {
        $stmt = $pdo->prepare("DELETE FROM staff WHERE Staff_id = ?");
        $stmt->execute([$sid]);
        $stmt = $pdo->prepare("INSERT INTO activity_log (Staff_id, Action, EntityType, EntityId, CreatedAt) VALUES (?, 'Deleted staff account', 'staff', ?, NOW())");
        $stmt->execute([$_SESSION['staff_id'], $sid]);
        redirect('/staff_admin.php?deleted=1');
    }
}

$staff_list = $pdo->query("SELECT * FROM staff ORDER BY Username")->fetchAll();
$logs       = $pdo->query("SELECT l.*, s.Username FROM activity_log l JOIN staff s ON l.Staff_id = s.Staff_id ORDER BY l.CreatedAt DESC LIMIT 50")->fetchAll();

$pageTitle = 'Admin Panel — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Admin Panel</h2>

<?php if ($msg):   ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-warning">Staff account deleted.</div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <h4>Staff Accounts</h4>
        <table class="table table-sm table-hover">
            <thead><tr><th>Username</th><th>Email</th><th>Role</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($staff_list as $s): ?>
            <tr>
                <td><?= e($s['Username']) ?></td>
                <td><?= e($s['Email']) ?></td>
                <td><span class="badge bg-secondary"><?= e($s['Role']) ?></span></td>
                <td>
                    <?php if ($s['Staff_id'] != $_SESSION['staff_id']): ?>
                    <a href="/staff_admin.php?delete_staff=<?= e($s['Staff_id']) ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Remove this account?')">Remove</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h5 class="mt-4">Add Staff Account</h5>
        <form method="post">
            <div class="mb-2">
                <input type="text" class="form-control form-control-sm" name="username" placeholder="Username" required>
            </div>
            <div class="mb-2">
                <input type="email" class="form-control form-control-sm" name="email" placeholder="Email" required>
            </div>
            <div class="mb-2">
                <input type="password" class="form-control form-control-sm" name="password" placeholder="Password" required>
            </div>
            <div class="mb-2">
                <select class="form-select form-select-sm" name="role">
                    <option value="librarian">Librarian</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="add_staff" class="btn btn-sm btn-success">Add Staff</button>
        </form>
    </div>

    <div class="col-lg-7">
        <h4>Recent Activity Log</h4>
        <table class="table table-sm table-striped">
            <thead><tr><th>Time</th><th>Staff</th><th>Action</th><th>Entity</th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
            <tr><td colspan="4" class="text-muted">No activity yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td><small><?= e($l['CreatedAt']) ?></small></td>
                <td><?= e($l['Username']) ?></td>
                <td><?= e($l['Action']) ?></td>
                <td><small><?= e($l['EntityType']) ?> #<?= e($l['EntityId']) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
