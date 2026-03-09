<?php
require_once '../config/helpers.php';
requireAdmin();

$db = getDB();
$msg = '';

// Update status via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $id = intval($_POST['id']);
    $status = sanitize($_POST['status'] ?? '');
    $allowed = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    if (in_array($status, $allowed) && $id) {
        $db->query("UPDATE orders SET status='$status' WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Order status updated']);
    } else {
        echo json_encode(['error' => 'Invalid request']);
    }
    exit;
}

// Filters
$status_f = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$date_f = sanitize($_GET['date'] ?? '');

$where = [];
if ($status_f)
    $where[] = "o.status='$status_f'";
if ($search)
    $where[] = "(o.order_number LIKE '%$search%' OR u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
if ($date_f)
    $where[] = "DATE(o.created_at)='$date_f'";
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page_num - 1) * $per_page;
$total = $db->query("SELECT COUNT(*) c FROM orders o JOIN users u ON o.user_id=u.id $where_sql")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);
$orders = $db->query("SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o JOIN users u ON o.user_id=u.id
    $where_sql ORDER BY o.created_at DESC LIMIT $per_page OFFSET $offset")->fetch_all(MYSQLI_ASSOC);

// Single Order View
$view_order = null;
if (isset($_GET['id'])) {
    $oid = intval($_GET['id']);
    $view_order = $db->query("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=$oid")->fetch_assoc();
    if ($view_order) {
        $view_order['items'] = $db->query("SELECT oi.*, p.name, p.image, p.brand
            FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=$oid")->fetch_all(MYSQLI_ASSOC);
    }
}

$statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Orders — Admin — ElecStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
</head>

<body class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include 'includes/topbar.php'; ?>
        <div class="admin-content">

            <?php if ($view_order): ?>
                <!-- ===== ORDER DETAIL ===== -->
                <div class="page-header">
                    <div>
                        <a href="orders.php"
                            style="color:var(--text-muted);font-size:0.85rem;display:flex;align-items:center;gap:6px;margin-bottom:8px"><i
                                class="bi bi-arrow-left"></i> Back to Orders</a>
                        <h1 class="page-title">Order <span style="color:var(--primary-light)">
                                <?= $view_order['order_number'] ?>
                            </span></h1>
                        <p class="page-subtitle">
                            <?= date('F j, Y \a\t g:i A', strtotime($view_order['created_at'])) ?>
                        </p>
                    </div>
                    <div>
                        <span class="status-badge status-<?= $view_order['status'] ?>"
                            style="padding:8px 20px;font-size:0.9rem">
                            <?= ucfirst($view_order['status']) ?>
                        </span>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
                    <div>
                        <!-- Items -->
                        <div class="card" style="margin-bottom:20px">
                            <div class="card-header">
                                <h3 class="card-title">Order Items</h3>
                            </div>
                            <div style="padding:0">
                                <?php foreach ($view_order['items'] as $item): ?>
                                    <div
                                        style="display:flex;align-items:center;gap:16px;padding:16px 20px;border-bottom:1px solid var(--border)">
                                        <img src="../assets/images/products/<?= $item['image'] ?>"
                                            alt="<?= sanitize($item['name']) ?>"
                                            style="width:56px;height:56px;object-fit:cover;border-radius:10px;background:var(--bg-input)"
                                            onerror="this.src='../assets/images/placeholder.jpg'" />
                                        <div style="flex:1">
                                            <div style="font-weight:700">
                                                <?= sanitize($item['name']) ?>
                                            </div>
                                            <div style="font-size:0.8rem;color:var(--text-muted)">
                                                <?= sanitize($item['brand']) ?>
                                            </div>
                                        </div>
                                        <div style="text-align:center">
                                            <div style="font-size:0.8rem;color:var(--text-muted)">Qty</div>
                                            <div style="font-weight:700">
                                                <?= $item['quantity'] ?>
                                            </div>
                                        </div>
                                        <div style="text-align:right">
                                            <div style="font-size:0.8rem;color:var(--text-muted)">Price</div>
                                            <div style="font-weight:700">$
                                                <?= number_format($item['price'], 2) ?>
                                            </div>
                                        </div>
                                        <div style="text-align:right">
                                            <div style="font-size:0.8rem;color:var(--text-muted)">Total</div>
                                            <div style="font-weight:800;color:var(--primary-light)">$
                                                <?= number_format($item['price'] * $item['quantity'], 2) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="padding:16px 20px;border-top:1px solid var(--border)">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem">
                                    <span style="color:var(--text-muted)">Subtotal</span>
                                    <span>$
                                        <?= number_format($view_order['subtotal'], 2) ?>
                                    </span>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem">
                                    <span style="color:var(--text-muted)">Shipping</span>
                                    <span>$
                                        <?= number_format($view_order['shipping_cost'], 2) ?>
                                    </span>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem">
                                    <span style="color:var(--text-muted)">Tax</span>
                                    <span>$
                                        <?= number_format($view_order['tax'], 2) ?>
                                    </span>
                                </div>
                                <div
                                    style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;padding-top:10px;border-top:1px solid var(--border)">
                                    <span>Total</span>
                                    <span style="color:var(--primary-light)">$
                                        <?= number_format($view_order['total'], 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Update Status -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Update Order Status</h3>
                            </div>
                            <div class="card-body">
                                <div style="display:flex;gap:10px;flex-wrap:wrap">
                                    <?php foreach ($statuses as $s): ?>
                                        <button onclick="updateStatus(<?= $view_order['id'] ?>, '<?= $s ?>')"
                                            class="btn btn-sm <?= $view_order['status'] === $s ? 'btn-primary' : 'btn-outline' ?>"
                                            id="status-btn-<?= $s ?>">
                                            <?= ucfirst($s) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <!-- Customer Info -->
                        <div class="card" style="margin-bottom:16px">
                            <div class="card-header">
                                <h3 class="card-title">Customer</h3>
                            </div>
                            <div class="card-body">
                                <div style="font-weight:700;margin-bottom:4px">
                                    <?= sanitize($view_order['user_name']) ?>
                                </div>
                                <div style="color:var(--text-muted);font-size:0.85rem;margin-bottom:2px"><i
                                        class="bi bi-envelope"></i>
                                    <?= sanitize($view_order['user_email']) ?>
                                </div>
                                <?php if ($view_order['user_phone']): ?>
                                    <div style="color:var(--text-muted);font-size:0.85rem"><i class="bi bi-telephone"></i>
                                        <?= sanitize($view_order['user_phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Shipping -->
                        <div class="card" style="margin-bottom:16px">
                            <div class="card-header">
                                <h3 class="card-title">Shipping Address</h3>
                            </div>
                            <div class="card-body">
                                <div style="color:var(--text-secondary);font-size:0.9rem;line-height:1.7">
                                    <?= nl2br(sanitize($view_order['shipping_address'])) ?><br>
                                    <?= sanitize($view_order['shipping_city']) ?>
                                    <?= sanitize($view_order['shipping_zip']) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Payment</h3>
                            </div>
                            <div class="card-body">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                                    <span style="color:var(--text-muted)">Method</span>
                                    <span style="font-weight:700;text-transform:uppercase">
                                        <?= $view_order['payment_method'] ?>
                                    </span>
                                </div>
                                <div style="display:flex;justify-content:space-between">
                                    <span style="color:var(--text-muted)">Status</span>
                                    <span
                                        class="status-badge status-<?= $view_order['payment_status'] === 'paid' ? 'delivered' : ($view_order['payment_status'] === 'refunded' ? 'cancelled' : 'pending') ?>">
                                        <?= ucfirst($view_order['payment_status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ===== ORDERS LIST ===== -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Orders</h1>
                        <p class="page-subtitle">
                            <?= number_format($total) ?> total orders
                        </p>
                    </div>
                </div>

                <!-- Status Filter Tabs -->
                <div style="display:flex;gap:8px;margin-bottom:20px;overflow-x:auto;padding-bottom:4px;flex-wrap:wrap">
                    <a href="orders.php" class="btn btn-sm <?= !$status_f ? 'btn-primary' : 'btn-outline' ?>">All</a>
                    <?php foreach ($statuses as $s): ?>
                        <a href="orders.php?status=<?= $s ?>&search=<?= urlencode($search) ?>"
                            class="btn btn-sm <?= $status_f === $s ? 'btn-primary' : 'btn-outline' ?>">
                            <?= ucfirst($s) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="card">
                    <div class="card-header" style="flex-wrap:wrap;gap:12px">
                        <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap">
                            <?php if ($status_f): ?><input type="hidden" name="status" value="<?= $status_f ?>" />
                            <?php endif; ?>
                            <div class="admin-search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" placeholder="Order #, name, email..."
                                    value="<?= sanitize($search) ?>" />
                            </div>
                            <input type="date" name="date" class="filter-select" value="<?= $date_f ?>"
                                onchange="this.form.submit()" />
                            <button type="submit" class="btn btn-outline btn-sm"><i class="bi bi-search"></i></button>
                            <?php if ($search || $date_f): ?><a href="orders.php?status=<?= $status_f ?>"
                                    class="btn btn-outline btn-sm"><i class="bi bi-x"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div style="overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                    <?php $items_cnt = $db->query("SELECT SUM(quantity) c FROM order_items WHERE order_id={$o['id']}")->fetch_assoc()['c']; ?>
                                    <tr>
                                        <td><span style="font-weight:700;color:var(--primary-light);font-size:0.85rem">
                                                <?= $o['order_number'] ?>
                                            </span></td>
                                        <td>
                                            <div style="font-weight:600;font-size:0.88rem">
                                                <?= sanitize($o['user_name']) ?>
                                            </div>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">
                                                <?= sanitize($o['user_email']) ?>
                                            </div>
                                        </td>
                                        <td><span style="font-weight:600">
                                                <?= $items_cnt ?? 0 ?> items
                                            </span></td>
                                        <td><strong>$
                                                <?= number_format($o['total'], 2) ?>
                                            </strong></td>
                                        <td><span
                                                style="font-size:0.8rem;text-transform:uppercase;font-weight:600;color:var(--text-secondary)">
                                                <?= $o['payment_method'] ?>
                                            </span></td>
                                        <td>
                                            <select onchange="updateStatus(<?= $o['id'] ?>, this.value)" class="filter-select"
                                                style="padding:5px 10px;border-radius:50px;font-size:0.78rem;font-weight:700">
                                                <?php foreach ($statuses as $s): ?>
                                                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>>
                                                        <?= ucfirst($s) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td style="font-size:0.8rem;color:var(--text-muted)">
                                            <?= date('M j, Y', strtotime($o['created_at'])) ?>
                                        </td>
                                        <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm"><i
                                                    class="bi bi-eye"></i> View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
                                                <h3>No orders found</h3>
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
                                    <a href="?page=<?= $i ?>&status=<?= $status_f ?>&search=<?= urlencode($search) ?>"
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

    <script src="../assets/js/app.js"></script>
    <script>
        async function updateStatus(orderId, status) {
            const res = await Ajax.post('orders.php', { ajax: 1, id: orderId, status });
            if (res.success) {
                Toast.success(res.message);
                // Update button states if on detail page
                document.querySelectorAll('[id^="status-btn-"]').forEach(btn => {
                    const s = btn.id.replace('status-btn-', '');
                    btn.className = 'btn btn-sm ' + (s === status ? 'btn-primary' : 'btn-outline');
                });
            } else {
                Toast.error(res.error || 'Failed to update status');
            }
        }
    </script>
</body>

</html>