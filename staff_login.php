<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo   = get_pdo();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';

    // Naive: MD5 hash and string-interpolated query
    $hash   = md5($pass);
    $result = $pdo->query("SELECT * FROM staff WHERE Username = '$username' AND Password = '$hash'");
    $staff  = $result->fetch();

    if ($staff) {
        $_SESSION['staff_id']       = $staff['Staff_id'];
        $_SESSION['staff_username'] = $staff['Username'];
        $_SESSION['staff_role']     = $staff['Role'];
        redirect('/staff_dashboard.php');
    } else {
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Staff Login — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<div class="row justify-content-center">
<div class="col-md-5">
<h2 class="mb-4">Staff Login</h2>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username"
               value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Log In</button>
</form>
</div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
