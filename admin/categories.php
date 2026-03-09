<?php
require_once '../config/helpers.php';
requireAdmin();

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $icon = sanitize($_POST['icon'] ?? 'bi-grid');
    $desc = sanitize($_POST['description'] ?? '');

    if ($id) {
        $db->query("UPDATE categories SET name='$name',slug='$slug',icon='$icon',description='$desc' WHERE id=$id");
        $msg = 'Category updated!';
    } else {
        $db->query("INSERT INTO categories (name,slug,icon,description) VALUES ('$name','$slug','$icon','$desc')");
        $msg = 'Category added!';
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->query("DELETE FROM categories WHERE id=$id");
    $msg = 'Category deleted.';
}

$edit_cat = null;
if (isset($_GET['edit'])) {
    $edit_cat = $db->query("SELECT * FROM categories WHERE id=" . intval($_GET['edit']))->fetch_assoc();
}

$categories = $db->query("SELECT c.*, COUNT(p.id) as pcount FROM categories c LEFT JOIN products p ON c.id=p.category_id GROUP BY c.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$icons = ['bi-phone', 'bi-laptop', 'bi-headphones', 'bi-tablet', 'bi-camera', 'bi-smartwatch', 'bi-joystick', 'bi-plug', 'bi-router', 'bi-tv', 'bi-earbuds', 'bi-battery-full'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Categories — Admin — ElecStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
</head>

<body class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include 'includes/topbar.php'; ?>
        <div class="admin-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Categories</h1>
                    <p class="page-subtitle">Manage product categories</p>
                </div>
            </div>
            <?php if ($msg): ?>
                <div class="toast info" style="position:relative;margin-bottom:16px;animation:none"><i
                        class="bi bi-info-circle-fill toast-icon"></i><span class="toast-msg">
                        <?= $msg ?>
                    </span></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">
                <!-- List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Categories</h3>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><i class="bi <?= $cat['icon'] ?>"
                                                style="font-size:1.2rem;color:var(--primary-light)"></i></td>
                                        <td><strong>
                                                <?= sanitize($cat['name']) ?>
                                            </strong></td>
                                        <td style="font-size:0.82rem;color:var(--text-muted)">
                                            <?= $cat['slug'] ?>
                                        </td>
                                        <td><strong>
                                                <?= $cat['pcount'] ?>
                                            </strong></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="?edit=<?= $cat['id'] ?>" class="btn btn-outline btn-sm"><i
                                                        class="bi bi-pencil"></i></a>
                                                <a href="?delete=<?= $cat['id'] ?>" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Delete this category?')"><i
                                                        class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add/Edit Form -->
                <div class="card" style="position:sticky;top:80px">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?= $edit_cat ? 'Edit Category' : 'Add Category' ?>
                        </h3>
                        <?php if ($edit_cat): ?><a href="categories.php" class="btn btn-outline btn-sm">Cancel</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($edit_cat): ?><input type="hidden" name="id" value="<?= $edit_cat['id'] ?>" />
                            <?php endif; ?>
                            <div class="form-group">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= sanitize($edit_cat['name'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Icon</label>
                                <select name="icon" class="form-control">
                                    <?php foreach ($icons as $ic): ?>
                                        <option value="<?= $ic ?>" <?= (($edit_cat['icon'] ?? '') === $ic) ? 'selected' : '' ?>>
                                            <?= $ic ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"
                                    rows="3"><?= sanitize($edit_cat['description'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%">
                                <i class="bi bi-check-lg"></i>
                                <?= $edit_cat ? 'Update' : 'Add Category' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/app.js"></script>
</body>

</html>