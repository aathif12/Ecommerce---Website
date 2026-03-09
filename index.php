<?php
require_once 'config/helpers.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/shop.php'));
    exit;
}

$db = getDB();
$featured = $db->query("SELECT p.*, (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id) as likes FROM products p WHERE p.is_featured=1 AND p.is_active=1 ORDER BY p.created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$categories = $db->query("SELECT * FROM categories ORDER BY name LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$stats = [
    'products' => $db->query("SELECT COUNT(*) c FROM products WHERE is_active=1")->fetch_assoc()['c'],
    'customers' => $db->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'],
    'brands' => $db->query("SELECT COUNT(DISTINCT brand) c FROM products")->fetch_assoc()['c'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ElecStore — Premium Electronics</title>
    <meta name="description"
        content="Shop the latest smartphones, laptops, headphones and premium electronics at ElecStore. Free shipping on orders over $50." />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        /* Landing-specific */
        .landing-nav .nav-action-btn:not(.cta) {
            display: none;
        }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hero-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .hero-phone {
            width: 280px;
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.15), rgba(255, 107, 107, 0.1));
            border: 1px solid rgba(108, 99, 255, 0.3);
            border-radius: 32px;
            padding: 20px;
            position: relative;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5), 0 0 60px rgba(108, 99, 255, 0.15);
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-16px);
            }
        }

        .hero-phone img {
            width: 100%;
            border-radius: 16px;
        }

        .hero-float-card {
            position: absolute;
            background: rgba(17, 24, 39, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(108, 99, 255, 0.25);
            border-radius: 16px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-md);
            white-space: nowrap;
        }

        .hero-float-card.card1 {
            top: 10%;
            right: -30px;
            animation: float 4s ease-in-out infinite 0.5s;
        }

        .hero-float-card.card2 {
            bottom: 20%;
            left: -40px;
            animation: float 4s ease-in-out infinite 1s;
        }

        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px 24px;
            text-align: center;
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(108, 99, 255, 0.3);
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 14px;
        }

        .feature-card h3 {
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .feature-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Promo banner */
        .promo-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, var(--accent) 100%);
            border-radius: var(--radius-xl);
            overflow: hidden;
            position: relative;
            padding: 60px 48px;
        }

        .promo-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        @media (max-width:768px) {
            .features-grid {
                grid-template-columns: 1fr 1fr;
            }

            .hero-visual {
                display: none;
            }

            .hero-phone {
                width: 220px;
            }
        }

        @media (max-width:480px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar (Landing) -->
    <nav class="navbar landing-nav" id="main-navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <div class="logo-icon">⚡</div>
                Elec<span>Store</span>
            </a>
            <div style="flex:1"></div>
            <div class="nav-cta">
                <a href="login.php" class="btn btn-outline btn-sm">Sign In</a>
                <a href="register.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Get Started</a>
            </div>
        </div>
    </nav>

    <div class="main-content">

        <!-- ===== HERO ===== -->
        <section class="hero">
            <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center">
                <div class="hero-content">
                    <div class="hero-tag">
                        <i class="bi bi-lightning-fill"></i> New Arrivals 2025
                    </div>
                    <h1 class="hero-title">
                        Next-Gen<br>
                        <span class="gradient-text">Electronics</span><br>
                        Delivered.
                    </h1>
                    <p class="hero-desc">
                        Discover the latest smartphones, laptops, headphones and more — all in one place. Premium tech
                        at unbeatable prices.
                    </p>
                    <div class="hero-btns">
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-shop"></i> Shop Now
                        </a>
                        <a href="login.php" class="btn btn-outline btn-lg">
                            Sign In <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number">
                                <?= $stats['products'] ?>+
                            </div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <?= $stats['brands'] ?>+
                            </div>
                            <div class="stat-label">Brands</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Support</div>
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="hero-phone">
                        <img src="assets/images/hero-device.png" alt="Premium Electronics"
                            onerror="this.style.display='none'" style="border-radius:16px" />
                        <div
                            style="background:linear-gradient(180deg,rgba(108,99,255,0) 0%,rgba(108,99,255,0.3) 100%);padding:20px;text-align:center;border-radius:0 0 16px 16px">
                            <div style="font-weight:800;font-size:1rem">iPhone 15 Pro Max</div>
                            <div style="color:var(--primary-light);font-weight:700">$1,199.99</div>
                        </div>
                    </div>
                    <div class="hero-float-card card1">
                        <div
                            style="width:34px;height:34px;background:rgba(46,204,113,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--success);font-size:1rem">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div>
                            <div style="font-size:0.78rem;color:var(--text-muted)">Order Placed!</div>
                            <div style="font-size:0.88rem;font-weight:700">Sony WH-1000XM5</div>
                        </div>
                    </div>
                    <div class="hero-float-card card2">
                        <div style="font-size:1.1rem;color:var(--accent)">❤️</div>
                        <div>
                            <div style="font-size:0.78rem;color:var(--text-muted)">Trending</div>
                            <div style="font-size:0.88rem;font-weight:700">MacBook Pro M3</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== FEATURES ===== -->
        <section class="section">
            <div class="container">
                <div class="features-grid">
                    <?php $feats = [
                        ['bi-truck', 'Free Shipping', 'On all orders over $50. Fast delivery guaranteed.', 'var(--primary)'],
                        ['bi-shield-check', '2-Year Warranty', 'All products come with comprehensive warranty.', 'var(--success)'],
                        ['bi-arrow-return-left', '30-Day Returns', 'Not happy? Return anything within 30 days.', 'var(--info)'],
                        ['bi-headset', '24/7 Support', 'Our experts are always ready to help you.', 'var(--accent)'],
                    ];
                    foreach ($feats as $f): ?>
                        <div class="feature-card">
                            <div class="feature-icon" style="color:<?= $f[3] ?>"><i class="bi <?= $f[0] ?>"></i></div>
                            <h3>
                                <?= $f[1] ?>
                            </h3>
                            <p>
                                <?= $f[2] ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ===== CATEGORIES ===== -->
        <?php if ($categories): ?>
            <section class="section" style="padding-top:0">
                <div class="container">
                    <div class="section-header">
                        <div class="section-tag"><i class="bi bi-grid-fill"></i> Browse</div>
                        <h2 class="section-title">Shop by <span class="gradient-text">Category</span></h2>
                        <p class="section-subtitle">Explore our wide range of electronic categories</p>
                    </div>
                    <div class="categories-grid">
                        <?php foreach ($categories as $cat): ?>
                            <a href="register.php" class="category-card">
                                <div class="category-icon"><i class="bi <?= $cat['icon'] ?>"></i></div>
                                <div class="category-name">
                                    <?= sanitize($cat['name']) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- ===== FEATURED PRODUCTS ===== -->
        <?php if ($featured): ?>
            <section class="section" style="padding-top:0">
                <div class="container">
                    <div class="section-header"
                        style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px">
                        <div>
                            <div class="section-tag"><i class="bi bi-star-fill"></i> Featured</div>
                            <h2 class="section-title">Best <span class="gradient-text">Sellers</span></h2>
                        </div>
                        <a href="register.php" class="btn btn-outline">View All <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="products-grid">
                        <?php foreach ($featured as $p):
                            $price = $p['sale_price'] ?: $p['price'];
                            $disc = $p['sale_price'] ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
                            ?>
                            <div class="product-card" onclick="window.location='register.php'">
                                <div class="product-image-wrap">
                                    <img src="assets/images/products/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>"
                                        onerror="this.src='assets/images/placeholder.jpg'" loading="lazy" />
                                    <?php if ($disc >= 5): ?>
                                        <div class="product-badge badge-sale">-
                                            <?= $disc ?>%
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-badge badge-hot"
                                        style="left:auto;right:12px;<?= $disc >= 5 ? 'display:none' : '' ?>">Hot</div>
                                </div>
                                <div class="product-info">
                                    <div class="product-brand">
                                        <?= sanitize($p['brand']) ?>
                                    </div>
                                    <div class="product-name">
                                        <?= sanitize($p['name']) ?>
                                    </div>
                                    <div class="product-rating">
                                        <span class="stars">★★★★☆</span>
                                        <span style="font-size:0.75rem;color:var(--accent);margin-left:auto"><i
                                                class="bi bi-heart-fill"></i>
                                            <?= $p['likes'] ?>
                                        </span>
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
                                        <div class="product-add-btn"><i class="bi bi-cart-plus"></i></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- ===== PROMO BANNER ===== -->
        <section class="section" style="padding-top:0">
            <div class="container">
                <div class="promo-banner">
                    <div style="position:relative;z-index:1;max-width:520px">
                        <div class="section-tag"
                            style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:#fff;margin-bottom:16px">
                            <i class="bi bi-lightning-fill"></i> Flash Sale
                        </div>
                        <h2 style="font-size:clamp(1.5rem,4vw,2.5rem);font-weight:900;color:#fff;margin-bottom:14px">
                            Up to <span style="color:#FFD700">40% OFF</span><br>on Top Electronics
                        </h2>
                        <p style="color:rgba(255,255,255,0.8);margin-bottom:28px;font-size:1rem">
                            Limited time deals on smartphones, laptops, headphones and more. Don't miss out!
                        </p>
                        <a href="register.php" class="btn"
                            style="background:#fff;color:var(--primary-dark);font-weight:800;font-size:1rem;padding:14px 32px">
                            <i class="bi bi-bag-fill"></i> Shop the Sale
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo" style="display:flex;align-items:center;gap:10px">
                        <div
                            style="width:34px;height:34px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.9rem">
                            ⚡</div>
                        Elec<span style="color:var(--primary-light)">Store</span>
                    </div>
                    <p>Your one-stop destination for the latest and greatest electronics. Premium quality, competitive
                        prices.</p>
                </div>
                <div class="footer-col">
                    <h4>Shop</h4>
                    <ul>
                        <li><a href="register.php">Smartphones</a></li>
                        <li><a href="register.php">Laptops</a></li>
                        <li><a href="register.php">Headphones</a></li>
                        <li><a href="register.php">Cameras</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Account</h4>
                    <ul>
                        <li><a href="login.php">Sign In</a></li>
                        <li><a href="register.php">Register</a></li>
                        <li><a href="register.php">My Orders</a></li>
                        <li><a href="register.php">Wishlist</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Returns</a></li>
                        <li><a href="#">Track Order</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© 2025 ElecStore. All rights reserved.</span>
                <div style="display:flex;gap:16px">
                    <a href="#" style="color:var(--text-muted)">Privacy Policy</a>
                    <a href="#" style="color:var(--text-muted)">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/app.js"></script>
</body>

</html>