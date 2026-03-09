<?php
require_once '../config/helpers.php';
requireAdmin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$err = '';

// ===== HANDLE POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $brand = sanitize($_POST['brand'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $sale_price = $_POST['sale_price'] !== '' ? floatval($_POST['sale_price']) : null;
    $stock = intval($_POST['stock'] ?? 0);
    $sku = sanitize($_POST['sku'] ?? '');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');

    // Image upload
    $image = $_POST['existing_image'] ?? 'placeholder.jpg';
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = uniqid('prod_') . '.' . $ext;
            $dest = '../assets/images/products/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image = $filename;
            }
        }
    }

    if ($id) {
        // Update
        $db->query("UPDATE products SET name='$name',slug='$slug',category_id=$category_id,
            brand='$brand',price=$price,sale_price=" . ($sale_price !== null ? $sale_price : 'NULL') . ",
            stock=$stock,sku='$sku',description='$description',image='$image',
            is_featured=$is_featured,is_active=$is_active WHERE id=$id");
        $msg = 'Product updated successfully!';
        $action = 'list';
    } else {
        // Insert
        $check = $db->query("SELECT id FROM products WHERE slug='$slug'")->fetch_assoc();
        if ($check)
            $slug .= '-' . time();
        $db->query("INSERT INTO products (name,slug,category_id,brand,price,sale_price,stock,sku,description,image,is_featured,is_active)
            VALUES ('$name','$slug',$category_id,'$brand',$price," . ($sale_price !== null ? $sale_price : 'NULL') . ",$stock,'$sku','$description','$image',$is_featured,$is_active)");
        $msg = 'Product added successfully!';
        $action = 'list';
    }
}

// ===== DELETE =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->query("DELETE FROM products WHERE id=$id");
    $msg = 'Product deleted.';
    $action = 'list';
}

// ===== EDIT: Load product =====
$edit_product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $edit_product = $db->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
    if (!$edit_product) {
        $action = 'list';
        $err = 'Product not found.';
    }
}

// ===== LIST: Fetch products =====
$search = sanitize($_GET['search'] ?? '');
$cat_f = intval($_GET['cat'] ?? 0);
$where = [];
if ($search)
    $where[] = "(p.name LIKE '%$search%' OR p.brand LIKE '%$search%' OR p.sku LIKE '%$search%')";
if ($cat_f)
    $where[] = "p.category_id=$cat_f";
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page_num - 1) * $per_page;
$total = $db->query("SELECT COUNT(*) c FROM products p $where_sql")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);
$products = $db->query("SELECT p.*,c.name as cat_name,
    (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id) as likes
    FROM products p LEFT JOIN categories c ON p.category_id=c.id
    $where_sql ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Products — Admin — ElecStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
</head>

<body class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include 'includes/topbar.php'; ?>
        <div class="admin-content">

            <?php if ($action === 'add' || $action === 'edit'): ?>
                <!-- ===== ADD / EDIT FORM ===== -->
                <div class="page-header">
                    <div>
                        <a href="products.php"
                            style="color:var(--text-muted);font-size:0.85rem;display:flex;align-items:center;gap:6px;margin-bottom:8px"><i
                                class="bi bi-arrow-left"></i> Back to Products</a>
                        <h1 class="page-title">
                            <?= $action === 'edit' ? 'Edit Product' : 'Add New Product' ?>
                        </h1>
                    </div>
                </div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if ($edit_product): ?><input type="hidden" name="id" value="<?= $edit_product['id'] ?>" />
                    <?php endif; ?>
                    <input type="hidden" name="existing_image" value="<?= $edit_product['image'] ?? 'placeholder.jpg' ?>" />

                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
                        <div>
                            <div class="card" style="margin-bottom:20px">
                                <div class="card-header">
                                    <h3 class="card-title">Product Information</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="form-label">Product Name *</label>
                                        <input type="text" name="name" class="form-control"
                                            value="<?= sanitize($edit_product['name'] ?? '') ?>"
                                            placeholder="e.g. Samsung Galaxy S24" required />
                                    </div>
                                    <div class="admin-form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Brand *</label>
                                            <input type="text" name="brand" class="form-control"
                                                value="<?= sanitize($edit_product['brand'] ?? '') ?>"
                                                placeholder="e.g. Samsung" required />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">SKU</label>
                                            <input type="text" name="sku" class="form-control"
                                                value="<?= sanitize($edit_product['sku'] ?? '') ?>"
                                                placeholder="e.g. SAM-S24-BLK" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="5"
                                            placeholder="Detailed product description..."><?= $edit_product['description'] ?? '' ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Pricing & Inventory</h3>
                                </div>
                                <div class="card-body">
                                    <div class="admin-form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Regular Price ($) *</label>
                                            <input type="number" name="price" class="form-control" step="0.01" min="0"
                                                value="<?= $edit_product['price'] ?? '' ?>" placeholder="0.00" required />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Sale Price ($) <small
                                                    style="color:var(--text-muted)">(optional)</small></label>
                                            <input type="number" name="sale_price" class="form-control" step="0.01" min="0"
                                                value="<?= $edit_product['sale_price'] ?? '' ?>"
                                                placeholder="Leave blank for no sale" />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Stock Quantity *</label>
                                            <input type="number" name="stock" class="form-control" min="0"
                                                value="<?= $edit_product['stock'] ?? 0 ?>" required />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Category *</label>
                                            <select name="category_id" class="form-control" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>" <?= (($edit_product['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>>
                                                        <?= sanitize($cat['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="card" style="margin-bottom:20px">
                                <div class="card-header">
                                    <h3 class="card-title">Product Image</h3>
                                </div>
                                <div class="card-body">
                                    <div class="image-upload-area" id="img-drop"
                                        onclick="document.getElementById('prod-img').click()">
                                        <?php if (!empty($edit_product['image'])): ?>
                                            <img id="img-preview" src="../assets/images/products/<?= $edit_product['image'] ?>"
                                                alt="Preview" />
                                        <?php else: ?>
                                            <img id="img-preview" src="#" style="display:none" />
                                        <?php endif; ?>
                                        <i class="bi bi-cloud-upload"
                                            style="font-size:2rem;color:var(--text-muted);margin-bottom:8px;display:block"></i>
                                        <p>Click to upload or drag & drop<br><small>JPG, PNG, WebP (max 5MB)</small></p>
                                        <input type="file" id="prod-img" name="image" accept="image/*" />
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Options</h3>
                                </div>
                                <div class="card-body">
                                    <label
                                        style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:14px">
                                        <input type="checkbox" name="is_featured" value="1"
                                            <?= !empty($edit_product['is_featured']) ? 'checked' : '' ?>
                                        style="width:16px;height:16px;accent-color:var(--primary)"/>
                                        <span><strong>Featured Product</strong><br><small
                                                style="color:var(--text-muted)">Show on homepage</small></span>
                                    </label>
                                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                                        <input type="checkbox" name="is_active" value="1"
                                            <?= !isset($edit_product['is_active']) || $edit_product['is_active'] ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--primary)"/>
                                        <span><strong>Active / Published</strong><br><small
                                                style="color:var(--text-muted)">Visible to customers</small></span>
                                    </label>
                                </div>
                            </div>

                            <div style="margin-top:16px;display:flex;gap:10px">
                                <button type="submit" class="btn btn-primary" style="flex:1">
                                    <i class="bi bi-check-lg"></i>
                                    <?= $action === 'edit' ? 'Update Product' : 'Add Product' ?>
                                </button>
                                <a href="products.php" class="btn btn-outline">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>

            <?php else: ?>
                <!-- ===== PRODUCTS LIST ===== -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Products</h1>
                        <p class="page-subtitle">
                            <?= number_format($total) ?> products in store
                        </p>
                    </div>
                    <a href="products.php?action=add" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
                </div>

                <?php if ($msg): ?>
                    <div class="toast info active" style="position:relative;margin-bottom:16px;animation:none"><i
                            class="bi bi-info-circle-fill toast-icon"></i><span class="toast-msg">
                            <?= $msg ?>
                        </span></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header" style="flex-wrap:wrap;gap:12px">
                        <form method="GET" action="" style="display:flex;gap:10px;flex:1;flex-wrap:wrap">
                            <div class="admin-search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" placeholder="Search products..."
                                    value="<?= sanitize($search) ?>" />
                            </div>
                            <select name="cat" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat_f == $cat['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm"><i class="bi bi-search"></i>
                                Search</button>
                            <?php if ($search || $cat_f): ?><a href="products.php" class="btn btn-outline btn-sm"><i
                                        class="bi bi-x"></i> Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div style="overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Likes</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/products/<?= $p['image'] ?>"
                                                alt="<?= sanitize($p['name']) ?>" class="product-thumb"
                                                onerror="this.src='../assets/images/placeholder.jpg'" />
                                        </td>
                                        <td>
                                            <div style="font-weight:700;font-size:0.9rem">
                                                <?= sanitize($p['name']) ?>
                                            </div>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">
                                                <?= sanitize($p['brand']) ?> ·
                                                <?= $p['sku'] ?>
                                            </div>
                                        </td>
                                        <td><span style="font-size:0.82rem;color:var(--text-secondary)">
                                                <?= sanitize($p['cat_name'] ?? 'Uncategorized') ?>
                                            </span></td>
                                        <td>
                                            <div style="font-weight:700">$
                                                <?= number_format($p['price'], 2) ?>
                                            </div>
                                            <?php if ($p['sale_price']): ?>
                                                <div style="font-size:0.78rem;color:var(--accent)">Sale: $
                                                    <?= number_format($p['sale_price'], 2) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span
                                                style="font-weight:700;color:<?= $p['stock'] < 5 ? 'var(--danger)' : ($p['stock'] < 20 ? 'var(--warning)' : 'var(--success)') ?>">
                                                <?= $p['stock'] ?>
                                            </span>
                                        </td>
                                        <td><span style="color:var(--accent);font-weight:700"><i class="bi bi-heart-fill"></i>
                                                <?= $p['likes'] ?>
                                            </span></td>
                                        <td>
                                            <?php if ($p['is_active']): ?>
                                                <span class="status-badge status-delivered">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-cancelled">Inactive</span>
                                            <?php endif; ?>
                                            <?php if ($p['is_featured']): ?>
                                                <span class="status-badge status-confirmed" style="margin-left:4px">Featured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="products.php?action=edit&id=<?= $p['id'] ?>"
                                                    class="btn btn-outline btn-sm"><i class="bi bi-pencil"></i></a>
                                                <button onclick="confirmDelete(<?= $p['id'] ?>, '<?= sanitize($p['name']) ?>')"
                                                    class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
                                                <h3>No products found</h3>
                                                <p>Add your first product to get started</p><a href="products.php?action=add"
                                                    class="btn btn-primary">Add Product</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div style="padding:16px;border-top:1px solid var(--border)">
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?>&search=<?= $search ?>&cat=<?= $cat_f ?>"
                                        class="page-btn <?= $i == $page_num ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="overlay" id="del-overlay">
        <div class="modal" style="max-width:400px">
            <div style="text-align:center;padding:8px">
                <div style="font-size:3rem;margin-bottom:12px">🗑️</div>
                <h3 style="margin-bottom:8px">Delete Product?</h3>
                <p id="del-msg" style="color:var(--text-muted);margin-bottom:24px;font-size:0.9rem"></p>
                <div style="display:flex;gap:10px;justify-content:center">
                    <a id="del-confirm-btn" href="#" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</a>
                    <button onclick="document.getElementById('del-overlay').classList.remove('active')"
                        class="btn btn-outline">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Image preview
        document.getElementById('prod-img')?.addEventListener('change', function () {
            if (this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.getElementById('img-preview');
                    img.src = e.target.result;
                    img.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        function confirmDelete(id, name) {
            document.getElementById('del-msg').textContent = `Are you sure you want to delete "${name}"? This cannot be undone.`;
            document.getElementById('del-confirm-btn').href = `products.php?delete=${id}`;
            document.getElementById('del-overlay').classList.add('active');
        }

        document.getElementById('del-overlay')?.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('active');
        });
    </script>
</body>

</html>