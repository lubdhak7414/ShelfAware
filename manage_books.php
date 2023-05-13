<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_staff();

$pdo    = get_pdo();
$action = $_GET['action'] ?? 'list';
$msg    = '';
$error  = '';

// DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $pdo->query("DELETE FROM book WHERE Book_id = $id");
    redirect('/manage_books.php?deleted=1');
}

// ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = $_POST['title'] ?? '';
    $author   = $_POST['author'] ?? '';
    $isbn     = $_POST['isbn'] ?? '';
    $cat      = $_POST['category_id'] ?? '';
    $total    = $_POST['copies_total'] ?? 1;
    $avail    = $_POST['copies_available'] ?? 1;
    $year     = $_POST['year'] ?? '';

    if ($title === '' || $author === '') {
        $error = 'Title and Author are required.';
    } elseif (isset($_POST['book_id']) && $_POST['book_id'] !== '') {
        // UPDATE — naive string interpolation
        $bid = $_POST['book_id'];
        $pdo->query("UPDATE book SET Title='$title', Author='$author', ISBN='$isbn',
                     Category_id=$cat, CopiesTotal=$total, CopiesAvailable=$avail, Year=$year
                     WHERE Book_id=$bid");
        redirect('/manage_books.php?updated=1');
    } else {
        // INSERT — naive string interpolation
        $pdo->query("INSERT INTO book (Title, Author, ISBN, Category_id, CopiesTotal, CopiesAvailable, Year)
                     VALUES ('$title', '$author', '$isbn', $cat, $total, $avail, $year)");
        redirect('/manage_books.php?added=1');
    }
}

// Fetch edit target
$edit_book  = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id        = $_GET['id'];
    $edit_book = $pdo->query("SELECT * FROM book WHERE Book_id = $id")->fetch();
}

$categories = $pdo->query("SELECT * FROM category ORDER BY Name")->fetchAll();
$books      = $pdo->query("SELECT b.*, c.Name AS CategoryName FROM book b JOIN category c ON b.Category_id=c.Category_id ORDER BY b.Title")->fetchAll();

$pageTitle = 'Manage Books — ' . APP_NAME;
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Books</h2>
    <a href="/manage_books.php?action=add" class="btn btn-success">+ Add Book</a>
</div>

<?php if (!empty($_GET['added'])):   ?><div class="alert alert-success">Book added.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Book updated.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-warning">Book deleted.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="card mb-4">
    <div class="card-header"><?= $action === 'edit' ? 'Edit Book' : 'Add New Book' ?></div>
    <div class="card-body">
        <form method="post">
            <?php if ($edit_book): ?>
            <input type="hidden" name="book_id" value="<?= e($edit_book['Book_id']) ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title"
                           value="<?= e($edit_book['Title'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Author</label>
                    <input type="text" class="form-control" name="author"
                           value="<?= e($edit_book['Author'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ISBN</label>
                    <input type="text" class="form-control" name="isbn"
                           value="<?= e($edit_book['ISBN'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['Category_id']) ?>"
                            <?= isset($edit_book) && $edit_book['Category_id'] == $cat['Category_id'] ? 'selected' : '' ?>>
                            <?= e($cat['Name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <input type="number" class="form-control" name="year"
                           value="<?= e($edit_book['Year'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Copies (Total)</label>
                    <input type="number" class="form-control" name="copies_total" min="1"
                           value="<?= e($edit_book['CopiesTotal'] ?? 1) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Copies (Available)</label>
                    <input type="number" class="form-control" name="copies_available" min="0"
                           value="<?= e($edit_book['CopiesAvailable'] ?? 1) ?>">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <?= $action === 'edit' ? 'Update Book' : 'Add Book' ?>
                </button>
                <a href="/manage_books.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Title</th><th>Author</th><th>Category</th><th>Year</th>
            <th>Copies</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($books as $b): ?>
    <tr>
        <td><a href="/book.php?id=<?= e($b['Book_id']) ?>"><?= e($b['Title']) ?></a></td>
        <td><?= e($b['Author']) ?></td>
        <td><?= e($b['CategoryName']) ?></td>
        <td><?= e($b['Year']) ?></td>
        <td><?= e($b['CopiesAvailable']) ?> / <?= e($b['CopiesTotal']) ?></td>
        <td>
            <a href="/manage_books.php?action=edit&id=<?= e($b['Book_id']) ?>"
               class="btn btn-sm btn-outline-primary">Edit</a>
            <a href="/manage_books.php?action=delete&id=<?= e($b['Book_id']) ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Delete this book?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>
