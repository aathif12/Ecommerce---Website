<?php
require_once '../config/helpers.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

// Success message
$success_order = null;
if (isset($_GET['success'])) {
    $oid = intval($_GET['success']);
    $success_order = $db->query("SELECT * FROM orders WHERE id=$oid AND user_id=$uid")->fetch_assoc();
}

// Filters
$status_f = sanitize($_GET['status'] ?? '');
$where = ["o.user_id=$uid"];
if ($status_f)
    $where[] = "o.status='$status_f'";
$where_sql = 'WHERE ' . implode(' AND ', $where);

$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 8;
$offset = ($page_num - 1) * $per_page;
$total = $db->query("SELECT COUNT(*) c FROM orders o $where_sql")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

$orders = $db->query("
    SELECT o.*, GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') as product_names, SUM(oi.quantity) as total_items
    FROM orders o
    LEFT JOIN order_items oi ON o.id=oi.order_id
    LEFT JOIN products p ON oi.product_id=p.id
    $where_sql
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Single Order
$view_order = null;
if (isset($_GET['id'])) {
    $oid = intval($_GET['id']);
    $view_order = $db->query("SELECT * FROM orders WHERE id=$oid AND user_id=$uid")->fetch_assoc();
    if ($view_order) {
        $view_order['items'] = $db->query("SELECT oi.*, p.name, p.image, p.brand FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=$oid")->fetch_all(MYSQLI_ASSOC);
    }
}

$cart_count = getCartCount();
$wish_count = getWishlistCount();
$statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Orders — ElecStore</title>
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

            <?php if ($success_order): ?>
                <div
                    style="background:rgba(46,204,113,0.08);border:1px solid rgba(46,204,113,0.3);border-radius:var(--radius-xl);padding:40px;text-align:center;margin-bottom:32px">
                    <div style="font-size:3.5rem;margin-bottom:12px">🎉</div>
                    <h2 style="color:var(--success);margin-bottom:8px">Order Placed Successfully!</h2>
                    <p style="color:var(--text-muted);margin-bottom:4px">Order Number: <strong
                            style="color:var(--primary-light)">
                            <?= $success_order['order_number'] ?>
                        </strong></p>
                    <p style="color:var(--text-muted);margin-bottom:20px;font-size:0.9rem">We'll send you updates as your
                        order progresses. Total: <strong>$
                            <?= number_format($success_order['total'], 2) ?>
                        </strong></p>
                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php endif; ?>

            <?php if ($view_order): ?>
                <!-- Order Detail -->
                <div style="margin-bottom:12px"><a href="orders.php"
                        style="color:var(--text-muted);font-size:0.85rem;display:flex;align-items:center;gap:6px"><i
                            class="bi bi-arrow-left"></i> Back to Orders</a></div>
                <div
                    style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap">
                    <div>
                        <h1 style="font-size:1.4rem;font-weight:800">Order <span style="color:var(--primary-light)">
                                <?= $view_order['order_number'] ?>
                            </span></h1>
                        <p style="color:var(--text-muted);font-size:0.85rem">
                            <?= date('F j, Y \a\t g:i A', strtotime($view_order['created_at'])) ?>
                        </p>
                    </div>
                    <span class="status-badge status-<?= $view_order['status'] ?>"
                        style="padding:8px 20px;font-size:0.9rem">
                        <?= ucfirst($view_order['status']) ?>
                    </span>
                </div>

                <!-- Progress Bar -->
                <?php
                $steps = ['pending' => 0, 'confirmed' => 1, 'processing' => 2, 'shipped' => 3, 'delivered' => 4];
                $current_step = $steps[$view_order['status']] ?? 0;
                $cancelled = $view_order['status'] === 'cancelled';
                ?>
                <?php if (!$cancelled): ?>
                    <div class="card" style="margin-bottom:24px">
                        <div class="card-body">
                            <div
                                style="display:flex;align-items:center;justify-content:space-between;position:relative;padding:0 20px">
                                <div
                                    style="position:absolute;top:16px;left:40px;right:40px;height:2px;background:var(--border)">
                                </div>
                                <div
                                    style="position:absolute;top:16px;left:40px;width:calc(<?= $current_step * 25 ?>%);height:2px;background:var(--primary);transition:width 0.8s ease">
                                </div>
                                <?php foreach (['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'] as $si => $slabel): ?>
                                    <div
                                        style="display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;z-index:1">
                                        <div
                                            style="width:32px;height:32px;border-radius:50%;border:2px solid <?= $si <= $current_step ? 'var(--primary)' : 'var(--border)' ?>;background:<?= $si <= $current_step ? 'var(--primary)' : 'var(--bg-card)' ?>;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700">
                                            <?= $si < $current_step ? '<i class="bi bi-check-lg" style="color:#fff"></i>' : ($si == $current_step ? '<span style="color:#fff">' . ($si + 1) . '</span>' : '<span style="color:var(--text-muted)">' . ($si + 1) . '</span>') ?>
                                        </div>
                                        <span
                                            style="font-size:0.72rem;font-weight:600;color:<?= $si <= $current_step ? 'var(--primary-light)' : 'var(--text-muted)' ?>">
                                            <?= $slabel ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
                    <div>
                        <div class="card" style="margin-bottom:20px">
                            <div class="card-header">
                                <h3 class="card-title">Items Ordered</h3>
                            </div>
                            <?php foreach ($view_order['items'] as $item): ?>
                                <div
                                    style="display:flex;align-items:center;gap:16px;padding:16px 20px;border-bottom:1px solid var(--border)">
                                    <img src="../assets/images/products/<?= $item['image'] ?>"
                                        alt="<?= sanitize($item['name']) ?>"
                                        style="width:64px;height:64px;object-fit:cover;border-radius:12px;background:var(--bg-input);flex-shrink:0"
                                        onerror="this.src='../assets/images/placeholder.jpg'" />
                                    <div style="flex:1">
                                        <div style="font-weight:700;margin-bottom:2px">
                                            <?= sanitize($item['name']) ?>
                                        </div>
                                        <div style="font-size:0.8rem;color:var(--text-muted)">
                                            <?= sanitize($item['brand']) ?> · Qty:
                                            <?= $item['quantity'] ?>
                                        </div>
                                    </div>
                                    <div style="font-weight:800;color:var(--primary-light)">$
                                        <?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div style="padding:16px 20px">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem">
                                    <span style="color:var(--text-muted)">Subtotal</span><span>$
                                        <?= number_format($view_order['subtotal'], 2) ?>
                                    </span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem">
                                    <span style="color:var(--text-muted)">Shipping</span><span>
                                        <?= $view_order['shipping_cost'] == 0 ? '<span style="color:var(--success)">FREE</span>' : '$' . number_format($view_order['shipping_cost'], 2) ?>
                                    </span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.9rem">
                                    <span style="color:var(--text-muted)">Tax</span><span>$
                                        <?= number_format($view_order['tax'], 2) ?>
                                    </span></div>
                                <div
                                    style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:900;padding-top:10px;border-top:1px solid var(--border)">
                                    <span>Total</span><span style="color:var(--primary-light)">$
                                        <?= number_format($view_order['total'], 2) ?>
                                    </span></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="card" style="margin-bottom:16px">
                            <div class="card-header">
                                <h3 class="card-title">Shipping To</h3>
                            </div>
                            <div class="card-body">
                                <p style="color:var(--text-secondary);font-size:0.9rem">
                                    <?= nl2br(sanitize($view_order['shipping_address'])) ?><br>
                                    <?= sanitize($view_order['shipping_city']) ?>
                                    <?= sanitize($view_order['shipping_zip']) ?>
                                </p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Payment</h3>
                            </div>
                            <div class="card-body">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span
                                        style="color:var(--text-muted)">Method</span><strong
                                        style="text-transform:uppercase">
                                        <?= $view_order['payment_method'] ?>
                                    </strong></div>
                                <div style="display:flex;justify-content:space-between"><span
                                        style="color:var(--text-muted)">Status</span><span
                                        class="status-badge status-<?= $view_order['payment_status'] === 'paid' ? 'delivered' : ($view_order['payment_status'] === 'refunded' ? 'cancelled' : 'pending') ?>">
                                        <?= ucfirst($view_order['payment_status']) ?>
                                    </span></div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Orders List -->
                <div
                    style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
                    <div>
                        <h1 style="font-size:1.5rem;font-weight:800">My Orders</h1>
                        <p style="color:var(--text-muted);font-size:0.85rem">
                            <?= number_format($total) ?> orders total
                        </p>
                    </div>
                    <a href="shop.php" class="btn btn-primary btn-sm"><i class="bi bi-shop"></i> Continue Shopping</a>
                </div>

                <!-- Status Tabs -->
                <div style="display:flex;gap:8px;margin-bottom:20px;overflow-x:auto;padding-bottom:4px">
                    <a href="orders.php" class="btn btn-sm <?= !$status_f ? 'btn-primary' : 'btn-outline' ?>">All</a>
                    <?php foreach ($statuses as $s): ?>
                        <a href="orders.php?status=<?= $s ?>"
                            class="btn btn-sm <?= $status_f === $s ? 'btn-primary' : 'btn-outline' ?>">
                            <?= ucfirst($s) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="empty-state card" style="padding:60px">
                        <div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
                        <h3>No orders yet</h3>
                        <p>Start shopping and your orders will appear here</p>
                        <a href="shop.php" class="btn btn-primary">Shop Now</a>
                    </div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:16px">
                        <?php foreach ($orders as $order): ?>
                            <div class="card" style="transition:var(--transition);cursor:pointer"
                                onclick="window.location='orders.php?id=<?= $order['id'] ?>'"
                                onmouseover="this.style.borderColor='rgba(108,99,255,0.3)';this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.borderColor='var(--border)';this.style.transform=''">
                                <div style="padding:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                                    <div style="flex:1;min-width:200px">
                                        <div style="font-weight:800;color:var(--primary-light);margin-bottom:4px;font-size:0.9rem">
                                            <?= $order['order_number'] ?>
                                        </div>
                                        <div style="font-size:0.82rem;color:var(--text-muted)">
                                            <?= date('M j, Y', strtotime($order['created_at'])) ?> ·
                                            <?= $order['total_items'] ?? 0 ?> items
                                        </div>
                                        <div
                                            style="font-size:0.82rem;color:var(--text-secondary);margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px">
                                            <?= sanitize(substr($order['product_names'] ?? '', 0, 60)) ?>
                                            <?= strlen($order['product_names'] ?? '') > 60 ? '...' : '' ?>
                                        </div>
                                    </div>
                                    <div style="text-align:center">
                                        <div style="font-size:1.1rem;font-weight:900">$
                                            <?= number_format($order['total'], 2) ?>
                                        </div>
                                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase">
                                            <?= $order['payment_method'] ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                    <i class="bi bi-chevron-right" style="color:var(--text-muted)"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>&status=<?= $status_f ?>" class="page-btn <?= $i == $page_num ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
    <div id="toast-container"></div>

    <style>
        @media (max-width: 768px) {
            div[style*="grid-template-columns:2fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script src="../assets/js/app.js"></script>
</body>

</html>