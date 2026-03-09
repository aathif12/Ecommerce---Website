<?php
require_once '../config/helpers.php';
requireLogin();

$db = getDB();
$uid = $_SESSION['user_id'];

// Fetch cart items
$items = $db->query("
    SELECT c.id as cart_id, c.quantity, p.id, p.name, p.brand, p.image, p.price, p.sale_price, p.stock
    FROM cart c JOIN products p ON c.product_id=p.id
    WHERE c.user_id=$uid
    ORDER BY c.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$subtotal = array_sum(array_map(fn($i) => ($i['sale_price'] ?: $i['price']) * $i['quantity'], $items));
$shipping = $subtotal >= 50 ? 0 : 9.99;
$tax = round($subtotal * 0.08, 2);
$total = $subtotal + $shipping + $tax;
$cart_count = getCartCount();
$wish_count = getWishlistCount();

// Checkout POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (empty($items)) {
        header('Location: cart.php');
        exit;
    }
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $zip = sanitize($_POST['zip'] ?? '');
    $method = sanitize($_POST['payment_method'] ?? 'cod');
    $notes = sanitize($_POST['notes'] ?? '');

    if (!$address || !$city) {
        $err = 'Please fill in shipping address and city.';
    } else {
        $order_num = generateOrderNumber();
        $db->query("INSERT INTO orders (user_id,order_number,subtotal,shipping_cost,tax,total,shipping_address,shipping_city,shipping_zip,payment_method,notes)
            VALUES ($uid,'$order_num',$subtotal,$shipping,$tax,$total,'$address','$city','$zip','$method','$notes')");
        $order_id = $db->insert_id;

        foreach ($items as $item) {
            $price = $item['sale_price'] ?: $item['price'];
            $qty = $item['quantity'];
            $db->query("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES ($order_id,{$item['id']},$qty,$price)");
            $db->query("UPDATE products SET stock=stock-$qty WHERE id={$item['id']}");
        }

        $db->query("DELETE FROM cart WHERE user_id=$uid");
        header("Location: orders.php?success=$order_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cart & Checkout — ElecStore</title>
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
                <a href="wishlist.php" class="nav-action-btn"><i class="bi bi-heart"></i><span class="nav-badge"
                        id="wishlist-count" style="display:<?= $wish_count > 0 ? 'flex' : 'none' ?>">
                        <?= $wish_count ?>
                    </span></a>
                <a href="cart.php" class="nav-action-btn" style="border-color:var(--primary)"><i
                        class="bi bi-bag"></i><span class="nav-badge" id="cart-count"
                        style="display:<?= $cart_count > 0 ? 'flex' : 'none' ?>">
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

            <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:28px">
                <i class="bi bi-bag" style="color:var(--primary-light)"></i> Shopping Cart
                <span style="font-size:1rem;font-weight:500;color:var(--text-muted)">(
                    <?= count($items) ?> items)
                </span>
            </h1>

            <?php if (empty($items)): ?>
                <div class="empty-state" style="padding:80px 24px">
                    <div class="empty-state-icon"><i class="bi bi-bag-x"></i></div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet. Start shopping!</p>
                    <a href="shop.php" class="btn btn-primary btn-lg">Browse Products</a>
                </div>
            <?php else: ?>

                <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">
                    <!-- Cart Items -->
                    <div>
                        <?php if (!empty($err)): ?>
                            <div
                                style="padding:12px 16px;background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);border-radius:var(--radius-sm);color:#e98585;margin-bottom:16px">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <?= $err ?>
                            </div>
                        <?php endif; ?>

                        <div class="card" style="margin-bottom:20px">
                            <div id="cart-list">
                                <?php foreach ($items as $item):
                                    $price = $item['sale_price'] ?: $item['price'];
                                    $item_total = $price * $item['quantity'];
                                    ?>
                                    <div class="cart-item" id="cart-item-<?= $item['id'] ?>"
                                        style="display:flex;align-items:center;gap:16px;padding:20px;border-bottom:1px solid var(--border)">
                                        <img src="../assets/images/products/<?= $item['image'] ?>"
                                            alt="<?= sanitize($item['name']) ?>"
                                            style="width:80px;height:80px;object-fit:cover;border-radius:var(--radius-md);background:var(--bg-input);flex-shrink:0"
                                            onerror="this.src='../assets/images/placeholder.jpg'" />
                                        <div style="flex:1;min-width:0">
                                            <div style="font-weight:700;margin-bottom:4px;font-size:0.95rem">
                                                <?= sanitize($item['name']) ?>
                                            </div>
                                            <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:8px">
                                                <?= sanitize($item['brand']) ?>
                                            </div>
                                            <div style="font-weight:800;color:var(--primary-light)">$
                                                <?= number_format($price, 2) ?>
                                            </div>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            <div
                                                style="display:flex;align-items:center;background:var(--bg-input);border:1px solid var(--border);border-radius:10px;overflow:hidden">
                                                <button onclick="updateQty(<?= $item['id'] ?>,-1,<?= $item['stock'] ?>)"
                                                    style="width:34px;height:34px;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1rem">−</button>
                                                <span id="qty-<?= $item['id'] ?>"
                                                    style="width:30px;text-align:center;font-weight:700;font-size:0.9rem">
                                                    <?= $item['quantity'] ?>
                                                </span>
                                                <button onclick="updateQty(<?= $item['id'] ?>,1,<?= $item['stock'] ?>)"
                                                    style="width:34px;height:34px;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1rem">+</button>
                                            </div>
                                        </div>
                                        <div style="text-align:right;min-width:80px">
                                            <div style="font-weight:800;font-size:1.05rem" id="total-<?= $item['id'] ?>">$
                                                <?= number_format($item_total, 2) ?>
                                            </div>
                                            <button onclick="removeItem(<?= $item['id'] ?>)"
                                                style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:0.8rem;margin-top:6px">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="padding:16px 20px;display:flex;align-items:center;gap:12px">
                                <a href="shop.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Continue
                                    Shopping</a>
                            </div>
                        </div>

                        <!-- Checkout Form -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="bi bi-truck" style="color:var(--primary-light)"></i>
                                    Shipping Details</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="checkout-form">
                                    <input type="hidden" name="checkout" value="1" />
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                                        <div class="form-group" style="grid-column:1/-1">
                                            <label class="form-label">Street Address *</label>
                                            <input type="text" name="address" class="form-control"
                                                placeholder="123 Main Street, Apt 4" required />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">City *</label>
                                            <input type="text" name="city" class="form-control" placeholder="New York"
                                                required />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">ZIP Code</label>
                                            <input type="text" name="zip" class="form-control" placeholder="10001" />
                                        </div>
                                        <div class="form-group" style="grid-column:1/-1">
                                            <label class="form-label">Payment Method</label>
                                            <div style="display:flex;gap:10px;flex-wrap:wrap">
                                                <?php foreach ([['cod', 'bi-cash', 'Cash on Delivery'], ['card', 'bi-credit-card', 'Credit Card'], ['paypal', 'bi-paypal', 'PayPal']] as [$val, $icon, $label]): ?>
                                                    <label style="flex:1;min-width:120px">
                                                        <input type="radio" name="payment_method" value="<?= $val ?>"
                                                            <?= $val === 'cod' ? 'checked' : '' ?> style="display:none"
                                                        class="pay-radio"/>
                                                        <div class="pay-option"
                                                            style="border:1.5px solid var(--border);border-radius:var(--radius-md);padding:12px;text-align:center;cursor:pointer;transition:var(--transition-fast)">
                                                            <i class="bi <?= $icon ?>"
                                                                style="font-size:1.3rem;display:block;margin-bottom:4px"></i>
                                                            <span style="font-size:0.8rem;font-weight:600">
                                                                <?= $label ?>
                                                            </span>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="form-group" style="grid-column:1/-1">
                                            <label class="form-label">Order Notes <small
                                                    style="color:var(--text-muted)">(optional)</small></label>
                                            <textarea name="notes" class="form-control" rows="2"
                                                placeholder="Any special instructions..."></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:8px">
                                        <i class="bi bi-lock-fill"></i> Place Order — $
                                        <?= number_format($total, 2) ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div style="position:sticky;top:80px">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Order Summary</h3>
                            </div>
                            <div class="card-body">
                                <div style="display:flex;justify-content:space-between;margin-bottom:10px">
                                    <span style="color:var(--text-muted)">Subtotal</span>
                                    <span id="sum-subtotal">$
                                        <?= number_format($subtotal, 2) ?>
                                    </span>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:10px">
                                    <span style="color:var(--text-muted)">Shipping</span>
                                    <span>
                                        <?= $shipping == 0 ? '<span style="color:var(--success);font-weight:700">FREE</span>' : '$' . number_format($shipping, 2) ?>
                                    </span>
                                </div>
                                <?php if ($shipping == 0): ?>
                                    <div style="font-size:0.78rem;color:var(--success);margin-bottom:10px"><i
                                            class="bi bi-check-circle-fill"></i> Free shipping applied!</div>
                                <?php else: ?>
                                    <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:10px">Add $
                                        <?= number_format(50 - $subtotal, 2) ?> more for free shipping
                                    </div>
                                <?php endif; ?>
                                <div style="display:flex;justify-content:space-between;margin-bottom:14px">
                                    <span style="color:var(--text-muted)">Tax (8%)</span>
                                    <span>$
                                        <?= number_format($tax, 2) ?>
                                    </span>
                                </div>
                                <div
                                    style="display:flex;justify-content:space-between;padding-top:14px;border-top:1px solid var(--border);font-size:1.1rem;font-weight:800">
                                    <span>Total</span>
                                    <span style="color:var(--primary-light)" id="sum-total">$
                                        <?= number_format($total, 2) ?>
                                    </span>
                                </div>
                            </div>
                            <div style="padding:0 20px 20px">
                                <div
                                    style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:var(--text-muted);margin-bottom:8px">
                                    <i class="bi bi-lock-fill" style="color:var(--success)"></i> Secure checkout</div>
                                <div
                                    style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:var(--text-muted)">
                                    <i class="bi bi-arrow-return-left" style="color:var(--info)"></i> 30-day free returns
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div id="toast-container"></div>

    <style>
        .pay-radio:checked+.pay-option {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.08);
            color: var(--primary-light);
        }

        @media (max-width:768px) {
            div[style*="grid-template-columns:1fr 380px"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script src="../assets/js/app.js"></script>
    <script>
        // Payment option visual
        document.querySelectorAll('.pay-radio').forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('.pay-option').forEach(opt => {
                    opt.style.borderColor = 'var(--border)';
                    opt.style.background = '';
                    opt.style.color = '';
                });
                if (radio.checked) {
                    const opt = radio.nextElementSibling;
                    opt.style.borderColor = 'var(--primary)';
                    opt.style.background = 'rgba(108,99,255,0.08)';
                    opt.style.color = 'var(--primary-light)';
                }
            });
        });
        // init
        document.querySelector('.pay-radio:checked')?.dispatchEvent(new Event('change'));

        const prices = <?= json_encode(array_combine(array_column($items, 'id'), array_map(fn($i) => floatval($i['sale_price'] ?: $i['price']), $items))) ?>;

        async function updateQty(pid, delta, maxStock) {
            const el = document.getElementById('qty-' + pid);
            let qty = parseInt(el.textContent) + delta;
            qty = Math.max(1, Math.min(maxStock, qty));
            const res = await Cart.update(pid, qty);
            if (res.success) {
                el.textContent = qty;
                document.getElementById('total-' + pid).textContent = '$' + (prices[pid] * qty).toFixed(2);
                Cart.updateCount(res.count);
                // Recalc total - reload
                setTimeout(() => location.reload(), 400);
            } else {
                Toast.error(res.error || 'Failed to update');
            }
        }

        async function removeItem(pid) {
            const res = await Cart.remove(pid);
            if (res.success) {
                const el = document.getElementById('cart-item-' + pid);
                el.style.opacity = '0';
                el.style.transform = 'translateX(-20px)';
                el.style.transition = 'all 0.3s ease';
                setTimeout(() => { el.remove(); location.reload(); }, 350);
            }
        }
    </script>
</body>

</html>