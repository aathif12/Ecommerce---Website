<?php
require_once '../config/helpers.php';
requireLogin();

$db = getDB();

// Filters
$search = sanitize($_GET['search'] ?? '');
$cat_id = intval($_GET['cat'] ?? 0);
$sort = sanitize($_GET['sort'] ?? 'newest');
$min_p = floatval($_GET['min'] ?? 0);
$max_p = floatval($_GET['max'] ?? 9999);

$where = ['p.is_active=1'];
if ($search)
    $where[] = "(p.name LIKE '%$search%' OR p.brand LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($cat_id)
    $where[] = "p.category_id=$cat_id";
if ($min_p)
    $where[] = "COALESCE(p.sale_price,p.price) >= $min_p";
if ($max_p < 9999)
    $where[] = "COALESCE(p.sale_price,p.price) <= $max_p";
$where_sql = 'WHERE ' . implode(' AND ', $where);

$order_map = [
    'newest' => 'p.created_at DESC',
    'price_asc' => 'COALESCE(p.sale_price,p.price) ASC',
    'price_desc' => 'COALESCE(p.sale_price,p.price) DESC',
    'popular' => 'likes DESC',
    'name' => 'p.name ASC',
];
$order_sql = $order_map[$sort] ?? 'p.created_at DESC';

$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page_num - 1) * $per_page;

$total = $db->query("SELECT COUNT(*) c FROM products p $where_sql")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

$products = $db->query("
    SELECT p.*,c.name as cat_name,
    (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id) as likes,
    (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id AND user_id={$_SESSION['user_id']}) as is_liked
    FROM products p LEFT JOIN categories c ON p.category_id=c.id
    $where_sql ORDER BY $order_sql LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

$categories = $db->query("SELECT c.*, COUNT(p.id) as pcount FROM categories c LEFT JOIN products p ON c.id=p.category_id AND p.is_active=1 GROUP BY c.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$cart_count = getCartCount();
$wish_count = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shop Electronics — ElecStore</title>
    <meta name="description" content="Shop the latest smartphones, laptops, headphones and more at ElecStore." />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar" id="main-navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-logo">
                <div class="logo-icon">⚡</div>
                Elec<span>Store</span>
            </a>

            <div class="nav-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="nav-search-input" placeholder="Search smartphones, laptops..."
                    value="<?= sanitize($search) ?>" />
                <div class="search-dropdown" id="search-dropdown"></div>
            </div>

            <div class="nav-actions">
                <a href="wishlist.php" class="nav-action-btn" id="wishlist-btn" title="Wishlist">
                    <i class="bi bi-heart"></i>
                    <span class="nav-badge" id="wishlist-count"
                        style="display:<?= $wish_count > 0 ? 'flex' : 'none' ?>">
                        <?= $wish_count ?>
                    </span>
                </a>
                <a href="cart.php" class="nav-action-btn" id="cart-btn" title="Cart">
                    <i class="bi bi-bag"></i>
                    <span class="nav-badge" id="cart-count" style="display:<?= $cart_count > 0 ? 'flex' : 'none' ?>">
                        <?= $cart_count ?>
                    </span>
                </a>
                <a href="orders.php" class="nav-action-btn" title="My Orders">
                    <i class="bi bi-receipt"></i>
                </a>
                <div class="nav-user-menu">
                    <div class="nav-user-btn" id="nav-user-btn">
                        <div class="nav-user-avatar">
                            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                        </div>
                        <span class="nav-user-name">
                            <?= sanitize(explode(' ', $_SESSION['name'])[0]) ?>
                        </span>
                        <i class="bi bi-chevron-down" style="font-size:0.75rem;color:var(--text-muted)"></i>
                    </div>
                    <div class="nav-dropdown" id="nav-user-dropdown">
                        <a href="profile.php" class="nav-dropdown-item"><i class="bi bi-person"></i> My Profile</a>
                        <a href="orders.php" class="nav-dropdown-item"><i class="bi bi-receipt"></i> My Orders</a>
                        <a href="wishlist.php" class="nav-dropdown-item"><i class="bi bi-heart"></i> Wishlist</a>
                        <a href="../logout.php" class="nav-dropdown-item danger"><i class="bi bi-box-arrow-right"></i>
                            Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container" style="padding-top:28px;padding-bottom:60px">

            <!-- shop header -->
            <div
                style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap">
                <div>
                    <h1 style="font-size:1.4rem;font-weight:800">
                        <?php if ($search): ?> Results for "<span style="color:var(--primary-light)">
                                <?= sanitize($search) ?>
                            </span>"
                        <?php elseif ($cat_id):
                            $cat_name = '';
                            foreach ($categories as $c) {
                                if ($c['id'] == $cat_id)
                                    $cat_name = $c['name'];
                            }
                            echo sanitize($cat_name);
                        else: ?>All Products
                        <?php endif; ?>
                    </h1>
                    <p style="color:var(--text-muted);font-size:0.85rem;margin-top:4px">
                        <?= number_format($total) ?> products found
                    </p>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start">

                <!-- Sidebar Filters -->
                <aside>
                    <div class="card" style="position:sticky;top:80px">
                        <div class="card-header">
                            <h3 class="card-title" style="font-size:0.95rem">Filters</h3>
                            <?php if ($search || $cat_id || $min_p || $max_p < 9999): ?>
                                <a href="shop.php" style="font-size:0.8rem;color:var(--accent)">Clear All</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body" style="padding:0">
                            <!-- Categories -->
                            <div style="padding:16px;border-bottom:1px solid var(--border)">
                                <div
                                    style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:12px">
                                    Categories</div>
                                <a href="shop.php?sort=<?= $sort ?>"
                                    class="sidebar-cat-link <?= !$cat_id ? 'active' : '' ?>">
                                    <i class="bi bi-grid"></i> All Products <span>
                                        <?= number_format($total) ?>
                                    </span>
                                </a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="shop.php?cat=<?= $cat['id'] ?>&sort=<?= $sort ?>"
                                        class="sidebar-cat-link <?= $cat_id == $cat['id'] ? 'active' : '' ?>">
                                        <i class="bi <?= $cat['icon'] ?>"></i>
                                        <?= sanitize($cat['name']) ?> <span>
                                            <?= $cat['pcount'] ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <!-- Price Range -->
                            <div style="padding:16px">
                                <div
                                    style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:12px">
                                    Price Range</div>
                                <form method="GET" action="">
                                    <?php if ($cat_id): ?><input type="hidden" name="cat" value="<?= $cat_id ?>" />
                                    <?php endif; ?>
                                    <?php if ($search): ?><input type="hidden" name="search"
                                            value="<?= sanitize($search) ?>" />
                                    <?php endif; ?>
                                    <input type="hidden" name="sort" value="<?= $sort ?>" />
                                    <div style="display:flex;gap:8px;margin-bottom:10px">
                                        <input type="number" name="min" placeholder="Min $" value="<?= $min_p ?: '' ?>"
                                            min="0" class="form-control" style="padding:8px" />
                                        <input type="number" name="max" placeholder="Max $"
                                            value="<?= $max_p < 9999 ? $max_p : '' ?>" min="0" class="form-control"
                                            style="padding:8px" />
                                    </div>
                                    <button type="submit" class="btn btn-outline btn-sm"
                                        style="width:100%">Apply</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Products -->
                <div>
                    <!-- Sort Bar -->
                    <div
                        style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <?php
                            $sort_options = ['newest' => 'Newest', 'popular' => 'Most Liked', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'name' => 'Name A-Z'];
                            foreach ($sort_options as $key => $label):
                                ?>
                                <a href="shop.php?sort=<?= $key ?>&cat=<?= $cat_id ?>&search=<?= urlencode($search) ?>"
                                    class="btn btn-sm <?= $sort === $key ? 'btn-primary' : 'btn-outline' ?>">
                                    <?= $label ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <span style="font-size:0.82rem;color:var(--text-muted)">Page
                            <?= $page_num ?> of
                            <?= max(1, $total_pages) ?>
                        </span>
                    </div>

                    <!-- Grid -->
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-search"></i></div>
                            <h3>No products found</h3>
                            <p>Try adjusting your search or filters</p>
                            <a href="shop.php" class="btn btn-primary">Browse All</a>
                        </div>
                    <?php else: ?>
                        <div class="products-grid" id="products-grid">
                            <?php foreach ($products as $p):
                                $current_price = $p['sale_price'] ?: $p['price'];
                                $discount = $p['sale_price'] ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
                                ?>
                                <div class="product-card">
                                    <div class="product-image-wrap" onclick="window.location='product.php?id=<?= $p['id'] ?>'">
                                        <img src="../assets/images/products/<?= $p['image'] ?>"
                                            alt="<?= sanitize($p['name']) ?>"
                                            onerror="this.src='../assets/images/placeholder.jpg'" loading="lazy" />
                                        <?php if ($discount >= 5): ?>
                                            <div class="product-badge badge-sale">-
                                                <?= $discount ?>%
                                            </div>
                                        <?php elseif ($p['is_featured']): ?>
                                            <div class="product-badge badge-hot">Hot</div>
                                        <?php endif; ?>
                                        <?php if ($p['stock'] == 0): ?>
                                            <div
                                                style="position:absolute;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;color:var(--danger)">
                                                Out of Stock</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-actions">
                                        <button class="product-action-btn <?= $p['is_liked'] ? 'liked' : '' ?>"
                                            onclick="Wishlist.toggle(<?= $p['id'] ?>, this)"
                                            title="<?= $p['is_liked'] ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                                            <i class="bi bi-heart<?= $p['is_liked'] ? '-fill' : '' ?>"></i>
                                        </button>
                                        <button class="product-action-btn" onclick="QuickView.open(<?= $p['id'] ?>)"
                                            title="Quick View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>

                                    <div class="product-info" onclick="window.location='product.php?id=<?= $p['id'] ?>'">
                                        <div class="product-brand">
                                            <?= sanitize($p['brand']) ?>
                                        </div>
                                        <div class="product-name">
                                            <?= sanitize($p['name']) ?>
                                        </div>
                                        <div class="product-rating">
                                            <span class="stars">★★★★☆</span>
                                            <span class="rating-count">(
                                                <?= rand(12, 280) ?>)
                                            </span>
                                            <span style="font-size:0.75rem;color:var(--accent);margin-left:auto"><i
                                                    class="bi bi-heart-fill"></i>
                                                <?= $p['likes'] ?>
                                            </span>
                                        </div>
                                        <div class="product-price-row">
                                            <div>
                                                <span class="product-price">$
                                                    <?= number_format($current_price, 2) ?>
                                                </span>
                                                <?php if ($p['sale_price']): ?>
                                                    <span class="product-old-price">$
                                                        <?= number_format($p['price'], 2) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($p['stock'] > 0): ?>
                                                <button class="product-add-btn"
                                                    onclick="event.stopPropagation();Cart.add(<?= $p['id'] ?>,1,this)"
                                                    title="Add to cart">
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page_num > 1): ?>
                                    <a href="?page=<?= $page_num - 1 ?>&cat=<?= $cat_id ?>&sort=<?= $sort ?>&search=<?= urlencode($search) ?>"
                                        class="page-btn"><i class="bi bi-chevron-left"></i></a>
                                <?php endif; ?>
                                <?php for ($i = max(1, $page_num - 2); $i <= min($total_pages, $page_num + 2); $i++): ?>
                                    <a href="?page=<?= $i ?>&cat=<?= $cat_id ?>&sort=<?= $sort ?>&search=<?= urlencode($search) ?>"
                                        class="page-btn <?= $i == $page_num ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($page_num < $total_pages): ?>
                                    <a href="?page=<?= $page_num + 1 ?>&cat=<?= $cat_id ?>&sort=<?= $sort ?>&search=<?= urlencode($search) ?>"
                                        class="page-btn"><i class="bi bi-chevron-right"></i></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick View Overlay -->
    <div class="overlay" id="quick-view-overlay">
        <div class="modal" style="max-width:700px">
            <button class="modal-close" onclick="QuickView.close()"><i class="bi bi-x-lg"></i></button>
            <div id="quick-view-content"></div>
        </div>
    </div>

    <div id="toast-container"></div>

    <style>
        .sidebar-cat-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 0.87rem;
            color: var(--text-secondary);
            transition: var(--transition-fast);
            cursor: pointer;
            margin-bottom: 2px;
        }

        .sidebar-cat-link span {
            margin-left: auto;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .sidebar-cat-link:hover {
            background: rgba(108, 99, 255, 0.08);
            color: var(--text-primary);
        }

        .sidebar-cat-link.active {
            background: rgba(108, 99, 255, 0.12);
            color: var(--primary-light);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            div[style*="grid-template-columns:240px"] {
                grid-template-columns: 1fr !important;
            }

            aside {
                display: none;
            }
        }
    </style>

    <script src="../assets/js/app.js"></script>
</body>

</html>