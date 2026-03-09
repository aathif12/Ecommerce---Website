<?php
require_once '../config/helpers.php';
requireLogin();

$db = getDB();
$uid = $_SESSION['user_id'];

$products = $db->query("
    SELECT p.*, 1 as is_liked,
    (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id) as likes
    FROM product_likes pl JOIN products p ON pl.product_id=p.id
    WHERE pl.user_id=$uid AND p.is_active=1
    ORDER BY pl.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$cart_count = getCartCount();
$wish_count = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Wishlist — ElecStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>

<body>
    <nav class="navbar" id="main-navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-logo">
                <div class="logo-icon">⚡</div>Elec<span>Store</span>
            </a>
            <div class="nav-search"><i class="bi bi-search search-icon"></i><input type="text" id="nav-search-input"
                    placeholder="Search products..." />
                <div class="search-dropdown" id="search-dropdown"></div>
            </div>
            <div class="nav-actions">
                <a href="wishlist.php" class="nav-action-btn" style="border-color:var(--accent);color:var(--accent)"><i
                        class="bi bi-heart-fill"></i><span class="nav-badge" id="wishlist-count"
                        style="display:<?= $wish_count > 0 ? 'flex' : 'none' ?>">
                        <?= $wish_count ?>
                    </span></a>
                <a href="cart.php" class="nav-action-btn"><i class="bi bi-bag"></i><span class="nav-badge"
                        id="cart-count" style="display:<?= $cart_count > 0 ? 'flex' : 'none' ?>">
                        <?= $cart_count ?>
                    </span></a>
                <div class="nav-user-menu">
                    <div class="nav-user-btn" id="nav-user-btn">
                        <div class="nav-user-avatar">
                            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="nav-dropdown" id="nav-user-dropdown">
                        <a href="orders.php" class="nav-dropdown-item"><i class="bi bi-receipt"></i> My Orders</a>
                        <a href="../logout.php" class="nav-dropdown-item danger"><i class="bi bi-box-arrow-right"></i>
                            Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container" style="padding-top:28px;padding-bottom:60px">

            <div
                style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
                <div>
                    <h1 style="font-size:1.5rem;font-weight:800"><i class="bi bi-heart-fill"
                            style="color:var(--accent)"></i> My Wishlist</h1>
                    <p style="color:var(--text-muted);font-size:0.85rem">
                        <?= count($products) ?> saved items
                    </p>
                </div>
                <?php if (!empty($products)): ?>
                    <button onclick="addAllToCart()" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Add All to
                        Cart</button>
                <?php endif; ?>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state" style="padding:80px 24px">
                    <div class="empty-state-icon"><i class="bi bi-heart"></i></div>
                    <h3>Your wishlist is empty</h3>
                    <p>Browse products and click the heart icon to save items you love</p>
                    <a href="shop.php" class="btn btn-primary">Discover Products</a>
                </div>
            <?php else: ?>
                <div class="products-grid" id="wishlist-grid">
                    <?php foreach ($products as $p):
                        $price = $p['sale_price'] ?: $p['price'];
                        $disc = $p['sale_price'] ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
                        ?>
                        <div class="product-card" id="wish-card-<?= $p['id'] ?>">
                            <div class="product-image-wrap" onclick="window.location='product.php?id=<?= $p['id'] ?>'">
                                <img src="../assets/images/products/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>"
                                    onerror="this.src='../assets/images/placeholder.jpg'" loading="lazy" />
                                <?php if ($disc >= 5): ?>
                                    <div class="product-badge badge-sale">-
                                        <?= $disc ?>%
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions" style="opacity:1;transform:none">
                                <button class="product-action-btn liked" title="Remove from wishlist"
                                    onclick="removeFromWishlist(<?= $p['id'] ?>, this)">
                                    <i class="bi bi-heart-fill"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <div class="product-brand">
                                    <?= sanitize($p['brand']) ?>
                                </div>
                                <div class="product-name" onclick="window.location='product.php?id=<?= $p['id'] ?>'">
                                    <?= sanitize($p['name']) ?>
                                </div>
                                <div class="product-price-row">
                                    <div>
                                        <span class="product-price">$
                                            <?= number_format($price, 2) ?>
                                        </span>
                                        <?php if ($p['sale_price']): ?><span class="product-old-price">$
                                                <?= number_format($p['price'], 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($p['stock'] > 0): ?>
                                        <button class="product-add-btn" onclick="Cart.add(<?= $p['id'] ?>,1,this)"
                                            title="Add to cart"><i class="bi bi-cart-plus"></i></button>
                                    <?php else: ?>
                                        <span style="font-size:0.72rem;color:var(--danger)">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <div id="toast-container"></div>
    <script src="../assets/js/app.js"></script>
    <script>
        const wishlistIds = <?= json_encode(array_column($products, 'id')) ?>;

        async function removeFromWishlist(pid, btn) {
            const res = await Ajax.post('../api/likes.php', { action: 'toggle', product_id: pid });
            if (res.success && !res.liked) {
                const card = document.getElementById('wish-card-' + pid);
                card.style.opacity = '0';
                card.style.transform = 'scale(0.85)';
                card.style.transition = 'all 0.3s ease';
                setTimeout(() => card.remove(), 300);
                Toast.info('Removed from wishlist');
                const badge = document.getElementById('wishlist-count');
                if (badge && res.total !== undefined) {
                    badge.textContent = res.total;
                    badge.style.display = res.total > 0 ? 'flex' : 'none';
                }
            }
        }

        async function addAllToCart() {
            let added = 0;
            for (const pid of wishlistIds) {
                const res = await Ajax.post('../api/cart.php', { action: 'add', product_id: pid, quantity: 1 });
                if (res.success) { added++; Cart.updateCount(res.count); }
            }
            Toast.success(`${added} item(s) added to cart!`);
        }
    </script>
</body>

</html>