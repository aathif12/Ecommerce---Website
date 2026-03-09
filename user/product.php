<?php
require_once '../config/helpers.php';
requireLogin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

$product = $db->query("
    SELECT p.*, c.name as cat_name,
    (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id) as likes,
    (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id AND user_id={$_SESSION['user_id']}) as is_liked,
    ROUND(COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id=p.id AND is_approved=1),0),1) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE product_id=p.id AND is_approved=1) as review_count
    FROM products p LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.id=$id AND p.is_active=1
")->fetch_assoc();

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Track view
$db->query("UPDATE products SET views=views+1 WHERE id=$id");

// Reviews
$reviews = $db->query("
    SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id=u.id
    WHERE r.product_id=$id AND r.is_approved=1 ORDER BY r.created_at DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Submit review
$rev_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = htmlspecialchars(trim($_POST['comment'] ?? ''));
    if ($rating >= 1 && $rating <= 5 && $comment) {
        // Check if already reviewed
        $existing = $db->query("SELECT id FROM reviews WHERE user_id={$_SESSION['user_id']} AND product_id=$id")->fetch_assoc();
        if ($existing) {
            $rev_msg = 'You have already reviewed this product.';
        } else {
            $uid = $_SESSION['user_id'];
            $db->query("INSERT INTO reviews (user_id,product_id,rating,comment) VALUES ($uid,$id,$rating,'$comment')");
            $rev_msg = 'Review submitted successfully!';
            header("Location: product.php?id=$id&reviewed=1");
            exit;
        }
    } else {
        $rev_msg = 'Please provide a rating and comment.';
    }
}

// Related products
$related = $db->query("
    SELECT p.*, (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id) as likes
    FROM products p WHERE p.category_id={$product['category_id']} AND p.id != $id AND p.is_active=1
    ORDER BY RAND() LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

$cart_count = getCartCount();
$wish_count = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>
        <?= sanitize($product['name']) ?> — ElecStore
    </title>
    <meta name="description" content="<?= substr(sanitize($product['description']), 0, 160) ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>

<body>

    <nav class="navbar" id="main-navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-logo">
                <div class="logo-icon">⚡</div>Elec<span>Store</span>
            </a>
            <div class="nav-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="nav-search-input" placeholder="Search products..." />
                <div class="search-dropdown" id="search-dropdown"></div>
            </div>
            <div class="nav-actions">
                <a href="wishlist.php" class="nav-action-btn"><i class="bi bi-heart"></i><span class="nav-badge"
                        id="wishlist-count" style="display:<?= $wish_count > 0 ? 'flex' : 'none' ?>">
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
                        <span class="nav-user-name">
                            <?= sanitize(explode(' ', $_SESSION['name'])[0]) ?>
                        </span>
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

            <!-- Breadcrumb -->
            <div
                style="display:flex;align-items:center;gap:8px;font-size:0.82rem;color:var(--text-muted);margin-bottom:28px;flex-wrap:wrap">
                <a href="shop.php" style="color:var(--text-muted)">Shop</a>
                <i class="bi bi-chevron-right"></i>
                <a href="shop.php?cat=<?= $product['category_id'] ?>" style="color:var(--text-muted)">
                    <?= sanitize($product['cat_name'] ?? '') ?>
                </a>
                <i class="bi bi-chevron-right"></i>
                <span style="color:var(--text-primary)">
                    <?= sanitize($product['name']) ?>
                </span>
            </div>

            <!-- Product Detail -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:60px">
                <!-- Image -->
                <div>
                    <div
                        style="background:linear-gradient(135deg,var(--bg-card),var(--bg-card2));border-radius:var(--radius-xl);overflow:hidden;border:1px solid var(--border);padding:20px;height:460px;display:flex;align-items:center;justify-content:center">
                        <img src="../assets/images/products/<?= $product['image'] ?>"
                            alt="<?= sanitize($product['name']) ?>"
                            style="max-width:100%;max-height:100%;object-fit:contain"
                            onerror="this.src='../assets/images/placeholder.jpg'" id="main-img" />
                    </div>
                </div>

                <!-- Info -->
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                        <span
                            style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--primary-light)">
                            <?= sanitize($product['brand']) ?>
                        </span>
                        <span style="color:var(--border)">|</span>
                        <span style="font-size:0.75rem;color:var(--text-muted)">
                            <?= sanitize($product['cat_name'] ?? '') ?>
                        </span>
                    </div>

                    <h1 style="font-size:1.6rem;line-height:1.3;margin-bottom:16px;font-weight:800">
                        <?= sanitize($product['name']) ?>
                    </h1>

                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
                        <div class="stars" style="font-size:1rem">
                            <?php for ($i = 1; $i <= 5; $i++)
                                echo $i <= round($product['avg_rating']) ? '★' : '☆'; ?>
                        </div>
                        <span style="font-size:0.85rem;color:var(--text-muted)">(
                            <?= $product['review_count'] ?> reviews)
                        </span>
                        <span style="color:var(--border)">|</span>
                        <span style="font-size:0.85rem;color:var(--accent);font-weight:700"><i
                                class="bi bi-heart-fill"></i>
                            <?= $product['likes'] ?> likes
                        </span>
                        <span style="color:var(--border)">|</span>
                        <span style="font-size:0.82rem;color:var(--text-muted)"><i class="bi bi-eye"></i>
                            <?= number_format($product['views']) ?> views
                        </span>
                    </div>

                    <!-- Price -->
                    <div
                        style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:20px;margin-bottom:24px">
                        <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap">
                            <span style="font-size:2rem;font-weight:900;color:var(--text-primary)">
                                $
                                <?= number_format($product['sale_price'] ?: $product['price'], 2) ?>
                            </span>
                            <?php if ($product['sale_price']): ?>
                                <span style="text-decoration:line-through;color:var(--text-muted);font-size:1.1rem">$
                                    <?= number_format($product['price'], 2) ?>
                                </span>
                                <span
                                    style="background:var(--accent);color:#fff;padding:4px 12px;border-radius:50px;font-size:0.8rem;font-weight:700">
                                    SAVE
                                    <?= round((1 - $product['sale_price'] / $product['price']) * 100) ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:8px;font-size:0.82rem;color:var(--success)">
                            <?= $product['stock'] > 10 ? '<i class="bi bi-check-circle-fill"></i> In Stock' : ($product['stock'] > 0 ? "<i class='bi bi-exclamation-circle-fill' style='color:var(--warning)'></i> Only {$product['stock']} left" : '<i class="bi bi-x-circle-fill" style="color:var(--danger)"></i> Out of Stock') ?>
                        </div>
                    </div>

                    <!-- Add to Cart -->
                    <?php if ($product['stock'] > 0): ?>
                        <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">
                            <div
                                style="display:flex;align-items:center;gap:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden">
                                <button onclick="changeQty(-1)"
                                    style="width:42px;height:50px;background:none;border:none;color:var(--text-secondary);font-size:1.2rem;cursor:pointer;transition:var(--transition-fast)"
                                    onmouseover="this.style.background='rgba(108,99,255,0.1)'"
                                    onmouseout="this.style.background='none'">−</button>
                                <input type="number" id="qty-input" value="1" min="1" max="<?= $product['stock'] ?>"
                                    style="width:50px;text-align:center;background:none;border:none;color:var(--text-primary);font-size:1rem;font-weight:700;padding:0;outline:none" />
                                <button onclick="changeQty(1)"
                                    style="width:42px;height:50px;background:none;border:none;color:var(--text-secondary);font-size:1.2rem;cursor:pointer;transition:var(--transition-fast)"
                                    onmouseover="this.style.background='rgba(108,99,255,0.1)'"
                                    onmouseout="this.style.background='none'">+</button>
                            </div>
                            <button class="btn btn-primary btn-lg" style="flex:1;min-width:150px" id="add-cart-btn"
                                onclick="Cart.add(<?= $product['id'] ?>, parseInt(document.getElementById('qty-input').value), this)">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                        <a href="cart.php?buy_now=<?= $id ?>" onclick="return quickBuy(event)" class="btn btn-accent"
                            style="width:100%;padding:14px;font-size:1rem;justify-content:center">
                            <i class="bi bi-lightning-fill"></i> Buy Now
                        </a>
                    <?php else: ?>
                        <div class="btn btn-outline"
                            style="width:100%;padding:14px;justify-content:center;cursor:not-allowed;opacity:0.6">Out of
                            Stock</div>
                    <?php endif; ?>

                    <div
                        style="display:flex;gap:10px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                        <button onclick="Wishlist.toggle(<?= $product['id'] ?>, this)"
                            class="btn btn-outline <?= $product['is_liked'] ? 'liked-btn' : '' ?>"
                            style="flex:1;<?= $product['is_liked'] ? 'border-color:var(--accent);color:var(--accent)' : '' ?>">
                            <i class="bi bi-heart<?= $product['is_liked'] ? '-fill' : '' ?>"></i>
                            <?= $product['is_liked'] ? 'Wishlisted' : 'Add to Wishlist' ?>
                        </button>
                        <button onclick="shareProduct()" class="btn btn-outline"><i class="bi bi-share"></i>
                            Share</button>
                    </div>

                    <!-- Product Info -->
                    <div style="margin-top:20px;padding:16px;background:var(--bg-input);border-radius:var(--radius-md)">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:0.85rem">
                            <div><span style="color:var(--text-muted)">Brand:</span> <strong>
                                    <?= sanitize($product['brand']) ?>
                                </strong></div>
                            <div><span style="color:var(--text-muted)">SKU:</span> <strong>
                                    <?= $product['sku'] ?>
                                </strong></div>
                            <div><span style="color:var(--text-muted)">Category:</span> <strong>
                                    <?= sanitize($product['cat_name'] ?? '') ?>
                                </strong></div>
                            <div><span style="color:var(--text-muted)">Stock:</span> <strong>
                                    <?= $product['stock'] ?> units
                                </strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description + Reviews -->
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:60px">
                <!-- Description -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <div style="display:flex;gap:20px">
                                <button onclick="switchTab('desc')" class="tab-btn active"
                                    id="tab-desc">Description</button>
                                <button onclick="switchTab('rev')" class="tab-btn" id="tab-rev">Reviews (
                                    <?= $product['review_count'] ?>)
                                </button>
                            </div>
                        </div>
                        <div class="card-body" id="panel-desc">
                            <p style="color:var(--text-secondary);line-height:1.8">
                                <?= nl2br(sanitize($product['description'])) ?>
                            </p>
                        </div>
                        <div class="card-body" id="panel-rev" style="display:none">
                            <!-- Write Review -->
                            <?php if (isset($_GET['reviewed'])): ?>
                                <div
                                    style="padding:12px 16px;background:rgba(46,204,113,0.1);border:1px solid rgba(46,204,113,0.3);border-radius:var(--radius-sm);margin-bottom:20px;color:var(--success)">
                                    <i class="bi bi-check-circle-fill"></i> Review submitted successfully!
                                </div>
                            <?php endif; ?>

                            <div
                                style="background:var(--bg-input);border-radius:var(--radius-md);padding:20px;margin-bottom:24px">
                                <h4 style="margin-bottom:16px">Write a Review</h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="review" value="1" />
                                    <div style="margin-bottom:14px">
                                        <label
                                            style="display:block;font-size:0.85rem;color:var(--text-muted);margin-bottom:8px">Rating</label>
                                        <div class="star-rating" id="star-rating">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>"
                                                    <?= $i == 5 ? 'required' : '' ?>/>
                                                <label for="star<?= $i ?>" title="<?= $i ?> stars">★</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Your Review</label>
                                        <textarea name="comment" class="form-control" rows="3"
                                            placeholder="Share your thoughts about this product..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Submit Review</button>
                                </form>
                            </div>

                            <!-- Reviews List -->
                            <?php if (empty($reviews)): ?>
                                <div class="empty-state" style="padding:32px">
                                    <div class="empty-state-icon"><i class="bi bi-chat"></i></div>
                                    <h3>No reviews yet</h3>
                                    <p>Be the first to review this product</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($reviews as $r): ?>
                                    <div style="padding:16px 0;border-bottom:1px solid var(--border)">
                                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                                            <div
                                                style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">
                                                <?= strtoupper(substr($r['user_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:700;font-size:0.9rem">
                                                    <?= sanitize($r['user_name']) ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted)">
                                                    <?= date('M j, Y', strtotime($r['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="stars" style="margin-left:auto">
                                                <?= str_repeat('★', $r['rating']) ?>
                                                <?= str_repeat('☆', 5 - $r['rating']) ?>
                                            </div>
                                        </div>
                                        <p style="color:var(--text-secondary);font-size:0.9rem;line-height:1.6">
                                            <?= sanitize($r['comment']) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Summary -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why ElecStore?</h3>
                        </div>
                        <div class="card-body">
                            <?php $perks = [
                                ['bi-truck', 'Free Delivery', 'On orders over $50'],
                                ['bi-shield-check', '2-Year Warranty', 'All products covered'],
                                ['bi-arrow-return-left', '30-Day Returns', 'No questions asked'],
                                ['bi-lock', 'Secure Payment', '256-bit encryption'],
                            ];
                            foreach ($perks as $perk): ?>
                                <div
                                    style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
                                    <div
                                        style="width:38px;height:38px;background:rgba(108,99,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary-light);flex-shrink:0">
                                        <i class="bi <?= $perk[0] ?>"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:0.88rem">
                                            <?= $perk[1] ?>
                                        </div>
                                        <div style="font-size:0.78rem;color:var(--text-muted)">
                                            <?= $perk[2] ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php if ($related): ?>
                <div>
                    <div class="section-header">
                        <div class="section-tag"><i class="bi bi-grid"></i> Related Products</div>
                        <h2 class="section-title">You Might Also Like</h2>
                    </div>
                    <div class="products-grid">
                        <?php foreach ($related as $rp):
                            $rp_price = $rp['sale_price'] ?: $rp['price'];
                            $rp_disc = $rp['sale_price'] ? round((1 - $rp['sale_price'] / $rp['price']) * 100) : 0;
                            ?>
                            <div class="product-card" onclick="window.location='product.php?id=<?= $rp['id'] ?>'">
                                <div class="product-image-wrap">
                                    <img src="../assets/images/products/<?= $rp['image'] ?>" alt="<?= sanitize($rp['name']) ?>"
                                        onerror="this.src='../assets/images/placeholder.jpg'" loading="lazy" />
                                    <?php if ($rp_disc >= 5): ?>
                                        <div class="product-badge badge-sale">-
                                            <?= $rp_disc ?>%
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-brand">
                                        <?= sanitize($rp['brand']) ?>
                                    </div>
                                    <div class="product-name">
                                        <?= sanitize($rp['name']) ?>
                                    </div>
                                    <div class="product-price-row">
                                        <span class="product-price">$
                                            <?= number_format($rp_price, 2) ?>
                                        </span>
                                        <button class="product-add-btn"
                                            onclick="event.stopPropagation();Cart.add(<?= $rp['id'] ?>,1,this)"><i
                                                class="bi bi-cart-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <div id="toast-container"></div>

    <style>
        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 600;
            padding: 0 0 12px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: var(--transition-fast);
            font-family: inherit;
        }

        .tab-btn.active {
            color: var(--primary-light);
            border-bottom-color: var(--primary);
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            gap: 4px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .star-rating input:checked~label,
        .star-rating label:hover,
        .star-rating label:hover~label {
            color: var(--warning);
        }

        @media (max-width:768px) {
            div[style*="grid-template-columns:1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }

            div[style*="grid-template-columns:2fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script src="../assets/js/app.js"></script>
    <script>
        function changeQty(delta) {
            const input = document.getElementById('qty-input');
            const v = parseInt(input.value) + delta;
            input.value = Math.max(1, Math.min(<?= $product['stock'] ?>, v));
        }

        function switchTab(tab) {
            ['desc', 'rev'].forEach(t => {
                document.getElementById('panel-' + t).style.display = t === tab ? 'block' : 'none';
                document.getElementById('tab-' + t).classList.toggle('active', t === tab);
            });
        }

        function shareProduct() {
            if (navigator.share) {
                navigator.share({ title: '<?= sanitize($product['name']) ?>', url: window.location.href });
            } else {
                navigator.clipboard.writeText(window.location.href);
                Toast.success('Link copied to clipboard!');
            }
        }

        async function quickBuy(e) {
            e.preventDefault();
            await Cart.add(<?= $product['id'] ?>, parseInt(document.getElementById('qty-input').value));
            window.location.href = 'cart.php';
        }
    </script>
</body>

</html>