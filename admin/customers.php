<?php
require_once '../config/helpers.php';
requireAdmin();

$db = getDB();
$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page_num - 1) * $per_page;
$search = sanitize($_GET['search'] ?? '');
$where = $search ? "WHERE u.role='user' AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')" : "WHERE u.role='user'";
$total = $db->query("SELECT COUNT(*) c FROM users u $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

$customers = $db->query("
    SELECT u.*, COUNT(DISTINCT o.id) as order_count, COALESCE(SUM(o.total),0) as total_spent,
    COUNT(DISTINCT pl.product_id) as wishlist_count
    FROM users u
    LEFT JOIN orders o ON u.id=o.user_id
    LEFT JOIN product_likes pl ON u.id=pl.user_id
    $where GROUP BY u.id ORDER BY u.created_at DESC LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customers — Admin — ElecStore</title>
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
                    <h1 class="page-title">Customers</h1>
                    <p class="page-subtitle">
                        <?= number_format($total) ?> registered customers
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <form method="GET" style="display:flex;gap:10px;flex:1">
                        <div class="admin-search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" placeholder="Search by name or email..."
                                value="<?= sanitize($search) ?>" />
                        </div>
                        <button type="submit" class="btn btn-outline btn-sm"><i class="bi bi-search"></i>
                            Search</button>
                        <?php if ($search): ?><a href="customers.php" class="btn btn-outline btn-sm"><i
                                    class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Wishlist</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px">
                                            <div
                                                style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">
                                                <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                            </div>
                                            <span style="font-weight:600">
                                                <?= sanitize($c['name']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="color:var(--text-muted);font-size:0.85rem">
                                        <?= sanitize($c['email']) ?>
                                    </td>
                                    <td><strong>
                                            <?= $c['order_count'] ?>
                                        </strong></td>
                                    <td><strong style="color:var(--success)">$
                                            <?= number_format($c['total_spent'], 2) ?>
                                        </strong></td>
                                    <td><span style="color:var(--accent)"><i class="bi bi-heart-fill"></i>
                                            <?= $c['wishlist_count'] ?>
                                        </span></td>
                                    <td style="font-size:0.82rem;color:var(--text-muted)">
                                        <?= date('M j, Y', strtotime($c['created_at'])) ?>
                                    </td>
                                    <td><span class="status-badge status-<?= $c['is_active'] ? 'delivered' : 'cancelled' ?>">
                                            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state" style="padding:32px">
                                            <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                                            <h3>No customers found</h3>
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
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?><a href="?page=<?= $i ?>&search=<?= $search ?>"
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