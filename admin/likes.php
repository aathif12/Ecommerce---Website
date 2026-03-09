<?php
require_once '../config/helpers.php';
requireAdmin();

$db = getDB();

// Likes with product details
$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page_num - 1) * $per_page;
$search = sanitize($_GET['search'] ?? '');
$where = $search ? "WHERE p.name LIKE '%$search%' OR p.brand LIKE '%$search%'" : '';

$total = $db->query("SELECT COUNT(DISTINCT p.id) c FROM products p $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

$products_likes = $db->query("
    SELECT p.id, p.name, p.image, p.brand, p.price, p.sale_price, p.category_id,
           COUNT(pl.id) as like_count,
           c.name as cat_name
    FROM products p
    LEFT JOIN product_likes pl ON p.id = pl.product_id
    LEFT JOIN categories c ON p.category_id = c.id
    $where
    GROUP BY p.id
    ORDER BY like_count DESC, p.name ASC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

$total_likes = $db->query("SELECT COUNT(*) c FROM product_likes")->fetch_assoc()['c'];
$top_likers = $db->query("
    SELECT u.id, u.name, u.email, COUNT(pl.id) as like_count
    FROM users u JOIN product_likes pl ON u.id=pl.user_id
    GROUP BY u.id ORDER BY like_count DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Likes / Wishlist — Admin — ElecStore</title>
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
                    <h1 class="page-title">Likes & Wishlist</h1>
                    <p class="page-subtitle">
                        <?= number_format($total_likes) ?> total likes across all products
                    </p>
                </div>
            </div>

            <div class="admin-grid-2" style="margin-bottom:24px">
                <div class="card"
                    style="background:linear-gradient(135deg,rgba(255,107,107,0.08),rgba(231,76,60,0.05))">
                    <div class="card-body" style="display:flex;align-items:center;gap:16px">
                        <div
                            style="width:56px;height:56px;background:rgba(255,107,107,0.12);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--accent)">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <div>
                            <div style="font-size:2rem;font-weight:900">
                                <?= number_format($total_likes) ?>
                            </div>
                            <div style="color:var(--text-muted);font-size:0.85rem">Total Likes</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header" style="padding:16px 20px">
                        <h3 class="card-title" style="font-size:0.95rem">Top Wishlisters</h3>
                    </div>
                    <div style="padding:0">
                        <?php foreach ($top_likers as $i => $u): ?>
                            <div
                                style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border)">
                                <div
                                    style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div
                                        style="font-size:0.88rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                        <?= sanitize($u['name']) ?>
                                    </div>
                                </div>
                                <div style="color:var(--accent);font-weight:700;font-size:0.88rem"><i
                                        class="bi bi-heart-fill"></i>
                                    <?= $u['like_count'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($top_likers)): ?>
                            <div style="padding:20px;text-align:center;color:var(--text-muted)">No likes yet</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Products by Likes</h3>
                    <form method="GET" style="display:flex;gap:8px">
                        <div class="admin-search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" placeholder="Search products..."
                                value="<?= sanitize($search) ?>" />
                        </div>
                        <button type="submit" class="btn btn-outline btn-sm"><i class="bi bi-search"></i></button>
                        <?php if ($search): ?><a href="likes.php" class="btn btn-outline btn-sm"><i
                                    class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>❤️ Likes</th>
                                <th>Popularity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $max_likes = $products_likes[0]['like_count'] ?? 1;
                            foreach ($products_likes as $i => $p):
                                $pct = $max_likes > 0 ? round(($p['like_count'] / $max_likes) * 100) : 0;
                                ?>
                                <tr>
                                    <td style="color:var(--text-muted);font-weight:700">
                                        <?= $offset + $i + 1 ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:12px">
                                            <img src="../assets/images/products/<?= $p['image'] ?>"
                                                alt="<?= sanitize($p['name']) ?>"
                                                style="width:40px;height:40px;object-fit:cover;border-radius:8px"
                                                onerror="this.src='../assets/images/placeholder.jpg'" />
                                            <span style="font-weight:600;font-size:0.9rem">
                                                <?= sanitize($p['name']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="font-size:0.85rem;color:var(--text-muted)">
                                        <?= sanitize($p['brand']) ?>
                                    </td>
                                    <td style="font-size:0.82rem;color:var(--text-secondary)">
                                        <?= sanitize($p['cat_name'] ?? 'N/A') ?>
                                    </td>
                                    <td style="font-weight:700">
                                        <?= $p['sale_price'] ? '$' . number_format($p['sale_price'], 2) : '$' . number_format($p['price'], 2) ?>
                                    </td>
                                    <td>
                                        <span
                                            style="font-size:1rem;font-weight:800;color:<?= $p['like_count'] > 0 ? 'var(--accent)' : 'var(--text-muted)' ?>">
                                            <i class="bi bi-heart-fill"></i>
                                            <?= $p['like_count'] ?>
                                        </span>
                                    </td>
                                    <td style="min-width:120px">
                                        <div
                                            style="background:var(--bg-input);border-radius:4px;height:8px;overflow:hidden">
                                            <div
                                                style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--accent),var(--primary));border-radius:4px;transition:width 0.5s">
                                            </div>
                                        </div>
                                        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:3px">
                                            <?= $pct ?>%
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products_likes)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-state-icon"><i class="bi bi-heart"></i></div>
                                            <h3>No likes data</h3>
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
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                                    class="page-btn <?= $i == $page_num ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <script src="../assets/js/app.js"></script>
</body>

</html>