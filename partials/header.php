<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/index.php"><?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/index.php">Catalogue</a></li>
                <?php if (!empty($_SESSION['staff_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="/staff_dashboard.php">Staff Area</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (!empty($_SESSION['member_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="/my_account.php">My Account</a></li>
                <li class="nav-item"><a class="nav-link" href="/renew.php">Renew Loans</a></li>
                <li class="nav-item"><a class="nav-link" href="/logout.php">Log Out</a></li>
                <?php elseif (!empty($_SESSION['staff_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="/staff_dashboard.php"><?= e($_SESSION['staff_username'] ?? 'Staff') ?></a></li>
                <li class="nav-item"><a class="nav-link" href="/logout.php">Log Out</a></li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="/login.php">Member Login</a></li>
                <li class="nav-item"><a class="nav-link" href="/staff_login.php">Staff Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
