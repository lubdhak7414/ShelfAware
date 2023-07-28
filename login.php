<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo   = get_pdo();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    // Use prepared statement and password_verify instead of md5
    $stmt = $pdo->prepare("SELECT * FROM member WHERE Email = ?");
    $stmt->execute([$email]);
    $member = $stmt->fetch();

    if ($member && password_verify($pass, $member['Password'])) {
        $_SESSION['member_id']   = $member['Member_id'];
        $_SESSION['member_name'] = $member['Name'];
        $next = $_GET['next'] ?? '/my_account.php';
        redirect($next);
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Member Login — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<div class="row justify-content-center">
<div class="col-md-5">
<h2 class="mb-4">Member Login</h2>

<?php if (!empty($_GET['registered'])): ?>
<div class="alert alert-success">Registration successful! Please log in.</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Log In</button>
    <a href="/register.php" class="btn btn-link">Create an account</a>
</form>
</div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
