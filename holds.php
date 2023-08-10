<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff();

$pdo   = get_pdo();
$msg   = '';
$error = '';

// Fulfil or cancel a hold
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hold_id = (int)($_POST['hold_id'] ?? 0);
    $action  = $_POST['hold_action'] ?? '';

    if ($hold_id === 0 || !in_array($action, ['ready', 'cancelled'])) {
        $error = 'Invalid request.';
    } else {
        $stmt = $pdo->prepare("UPDATE hold SET Status = ? WHERE Hold_id = ?");
        $stmt->execute([$action, $hold_id]);

        $label = $action === 'ready' ? 'Marked hold ready' : 'Cancelled hold';
        $stmt  = $pdo->prepare("INSERT INTO activity_log (Staff_id, Action, EntityType, EntityId, CreatedAt) VALUES (?, ?, 'hold', ?, NOW())");
        $stmt->execute([$_SESSION['staff_id'], $label, $hold_id]);

        $msg = 'Hold updated.';
    }
}

$holds = $pdo->query("SELECT h.*, b.Title, m.Name AS MemberName
                      FROM hold h
                      JOIN book b ON h.Book_id = b.Book_id
                      JOIN member m ON h.Member_id = m.Member_id
                      ORDER BY h.Status, h.PlacedAt ASC")->fetchAll();

$pageTitle = 'Holds — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Reservation Holds</h2>

<?php if ($msg):   ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<table class="table table-hover">
    <thead>
        <tr><th>Book</th><th>Member</th><th>Placed At</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($holds)): ?>
    <tr><td colspan="5" class="text-center text-muted">No holds.</td></tr>
    <?php endif; ?>
    <?php foreach ($holds as $h): ?>
    <tr>
        <td><?= e($h['Title']) ?></td>
        <td><?= e($h['MemberName']) ?></td>
        <td><?= e($h['PlacedAt']) ?></td>
        <td>
            <?php $badge = match($h['Status']) {
                'waiting'   => 'bg-warning text-dark',
                'ready'     => 'bg-success',
                'cancelled' => 'bg-secondary',
            }; ?>
            <span class="badge <?= $badge ?>"><?= e($h['Status']) ?></span>
        </td>
        <td>
            <?php if ($h['Status'] === 'waiting'): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="hold_id" value="<?= e($h['Hold_id']) ?>">
                <input type="hidden" name="hold_action" value="ready">
                <button class="btn btn-sm btn-success">Mark Ready</button>
            </form>
            <form method="post" class="d-inline">
                <input type="hidden" name="hold_id" value="<?= e($h['Hold_id']) ?>">
                <input type="hidden" name="hold_action" value="cancelled">
                <button class="btn btn-sm btn-outline-danger">Cancel</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>
