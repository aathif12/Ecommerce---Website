<?php
require_once '../config/helpers.php';
requireAdmin();

// Stats
$db = getDB();
$stats = [];
$stats['total_products'] = $db->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$stats['total_orders'] = $db->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$stats['total_users'] = $db->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'];
$stats['total_revenue'] = $db->query("SELECT COALESCE(SUM(total),0) c FROM orders WHERE status='delivered'")->fetch_assoc()['c'];
$stats['pending_orders'] = $db->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$stats['total_likes'] = $db->query("SELECT COUNT(*) c FROM product_likes")->fetch_assoc()['c'];

// Monthly revenue (last 6 months)
$monthly = $db->query("
  SELECT DATE_FORMAT(created_at,'%b') as month, COALESCE(SUM(total),0) as revenue
  FROM orders WHERE status IN ('delivered','shipped')
  AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY YEAR(created_at), MONTH(created_at)
  ORDER BY created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recent_orders = $db->query("
  SELECT o.*, u.name as user_name
  FROM orders o JOIN users u ON o.user_id=u.id
  ORDER BY o.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Most liked products
$top_liked = $db->query("
  SELECT p.id, p.name, p.image, p.brand, COUNT(pl.id) as likes
  FROM products p LEFT JOIN product_likes pl ON p.id=pl.product_id
  GROUP BY p.id ORDER BY likes DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Order status breakdown
$order_status = $db->query("
  SELECT status, COUNT(*) as count FROM orders GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

$monthly_labels = array_column($monthly, 'month');
$monthly_data = array_column($monthly, 'revenue');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard — ElecStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
</head>

<body class="admin-layout">

    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <?php include 'includes/topbar.php'; ?>

        <div class="admin-content">
            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Welcome back,
                        <?= sanitize($_SESSION['name']) ?>! Here's what's happening today.
                    </p>
                </div>
                <div style="display:flex;gap:10px">
                    <a href="products.php?action=add" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add
                        Product</a>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(108,99,255,0.12);color:var(--primary-light)"><i
                            class="bi bi-bag-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= number_format($stats['total_orders']) ?>
                        </div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-trend up"><i class="bi bi-arrow-up-right"></i> Live</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(46,204,113,0.12);color:var(--success)"><i
                            class="bi bi-currency-dollar"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">$
                            <?= number_format($stats['total_revenue'], 0) ?>
                        </div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-trend up"><i class="bi bi-arrow-up-right"></i> Delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(52,152,219,0.12);color:var(--info)"><i
                            class="bi bi-people"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= number_format($stats['total_users']) ?>
                        </div>
                        <div class="stat-label">Customers</div>
                    </div>
                    <div class="stat-trend up"><i class="bi bi-person-plus"></i> Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,107,107,0.12);color:var(--accent)"><i
                            class="bi bi-heart"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= number_format($stats['total_likes']) ?>
                        </div>
                        <div class="stat-label">Total Likes</div>
                    </div>
                    <div class="stat-trend"><i class="bi bi-graph-up"></i> Product</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(243,156,18,0.12);color:var(--warning)"><i
                            class="bi bi-clock"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= $stats['pending_orders'] ?>
                        </div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                    <div class="stat-trend" style="color:var(--warning)"><i class="bi bi-exclamation-circle"></i>
                        Attention</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(108,99,255,0.12);color:var(--primary-light)"><i
                            class="bi bi-box-seam"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= $stats['total_products'] ?>
                        </div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-trend up"><i class="bi bi-shop"></i> In Store</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="admin-grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-graph-up-arrow" style="color:var(--primary-light)"></i>
                            Revenue (Last 6 Months)</h3>
                    </div>
                    <div class="card-body" style="height:260px">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-pie-chart" style="color:var(--accent)"></i> Order Status
                        </h3>
                    </div>
                    <div class="card-body" style="height:260px;display:flex;align-items:center;justify-content:center">
                        <canvas id="statusChart" style="max-height:220px;max-width:220px"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Orders & Top Liked -->
            <div class="admin-grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-receipt" style="color:var(--primary-light)"></i> Recent
                            Orders</h3>
                        <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $o): ?>
                                    <tr>
                                        <td><span style="font-weight:700;color:var(--primary-light)">
                                                <?= $o['order_number'] ?>
                                            </span></td>
                                        <td>
                                            <?= sanitize($o['user_name']) ?>
                                        </td>
                                        <td><strong>$
                                                <?= number_format($o['total'], 2) ?>
                                            </strong></td>
                                        <td><span class="status-badge status-<?= $o['status'] ?>">
                                                <?= ucfirst($o['status']) ?>
                                            </span></td>
                                        <td>
                                            <a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-state" style="padding:32px">No orders yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-heart-fill" style="color:var(--accent)"></i> Most Liked
                            Products</h3>
                        <a href="products.php" class="btn btn-outline btn-sm">All Products</a>
                    </div>
                    <div class="card-body" style="padding:0">
                        <?php foreach ($top_liked as $i => $p): ?>
                            <div
                                style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
                                <span
                                    style="font-size:1.2rem;font-weight:900;color:var(--text-muted);width:22px;text-align:right">
                                    <?= $i + 1 ?>
                                </span>
                                <img src="../assets/images/products/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>"
                                    style="width:44px;height:44px;object-fit:cover;border-radius:10px;background:var(--bg-input)"
                                    onerror="this.src='../assets/images/placeholder.jpg'">
                                <div style="flex:1;min-width:0">
                                    <div
                                        style="font-weight:600;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= sanitize($p['name']) ?>
                                    </div>
                                    <div style="font-size:0.78rem;color:var(--text-muted)">
                                        <?= sanitize($p['brand']) ?>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:4px;color:var(--accent);font-weight:700">
                                    <i class="bi bi-heart-fill"></i>
                                    <?= $p['likes'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($top_liked)): ?>
                            <div class="empty-state" style="padding:32px">No likes yet</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /admin-content -->
    </div><!-- /admin-main -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
        const labels = <?= json_encode($monthly_labels ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) ?>;
        const revenues = <?= json_encode(array_map('floatval', $monthly_data ?: [0, 0, 0, 0, 0, 0])) ?>;
        const statusData = <?= json_encode($order_status) ?>;

        // Revenue Chart
        const rCtx = document.getElementById('revenueChart');
        if (rCtx) {
            new Chart(rCtx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: revenues,
                        backgroundColor: 'rgba(108,99,255,0.5)',
                        borderColor: '#6C63FF',
                        borderWidth: 2,
                        borderRadius: 8,
                        hoverBackgroundColor: 'rgba(108,99,255,0.75)',
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#8892A4' } },
                        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#8892A4', callback: v => '$' + v } }
                    }
                }
            });
        }

        // Status Donut Chart
        const sCtx = document.getElementById('statusChart');
        if (sCtx && statusData.length) {
            const colors = { pending: '#F39C12', confirmed: '#3498DB', processing: '#6C63FF', shipped: '#5dde8f', delivered: '#2ECC71', cancelled: '#E74C3C', refunded: '#95a5a6' };
            new Chart(sCtx, {
                type: 'doughnut',
                data: {
                    labels: statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1)),
                    datasets: [{
                        data: statusData.map(s => parseInt(s.count)),
                        backgroundColor: statusData.map(s => colors[s.status] || '#6C63FF'),
                        borderWidth: 0,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#8892A4', padding: 12, font: { size: 12 } } }
                    }
                }
            });
        }
    </script>
</body>

</html>