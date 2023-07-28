<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo   = get_pdo();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $pass === '') {
        $error = 'Name, email and password are required.';
    } else {
        // Use bcrypt instead of md5
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        // Prepared statement to prevent SQL injection
        $stmt = $pdo->prepare("INSERT INTO member (Name, Email, Phone, JoinDate, Password) VALUES (?, ?, ?, CURDATE(), ?)");
        $stmt->execute([$name, $email, $phone, $hash]);

        redirect('/login.php?registered=1');
    }
}

$pageTitle = 'Register — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<div class="row justify-content-center">
<div class="col-md-6">
<h2 class="mb-4">Create an Account</h2>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?= e($_POST['name'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">Phone (optional)</label>
        <input type="text" class="form-control" id="phone" name="phone"
               value="<?= e($_POST['phone'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Register</button>
    <a href="/login.php" class="btn btn-link">Already have an account?</a>
</form>
</div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
