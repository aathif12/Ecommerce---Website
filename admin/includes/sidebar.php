<?php
$current = basename($_SERVER['PHP_SELF']);
$pending = (new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME))->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">⚡</div>
        <div>
            <div class="sidebar-logo-text">Elec<span>Store</span></div>
            <div class="sidebar-tag">Admin Panel</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">
            <div class="sidebar-section-title">Overview</div>
            <a href="dashboard.php" class="sidebar-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Catalog</div>
            <a href="products.php" class="sidebar-link <?= $current === 'products.php' ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <a href="categories.php" class="sidebar-link <?= $current === 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-grid"></i> Categories
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Commerce</div>
            <a href="orders.php" class="sidebar-link <?= $current === 'orders.php' ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i> Orders
                <?php if ($pending): ?><span class="badge">
                        <?= $pending ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="customers.php" class="sidebar-link <?= $current === 'customers.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Customers
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Analytics</div>
            <a href="analytics.php" class="sidebar-link <?= $current === 'analytics.php' ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i> Analytics
            </a>
            <a href="likes.php" class="sidebar-link <?= $current === 'likes.php' ? 'active' : '' ?>">
                <i class="bi bi-heart"></i> Likes / Wishlist
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="user-name">
                    <?= sanitize($_SESSION['name']) ?>
                </div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <a href="../logout.php" class="sidebar-link" style="margin-top:8px;color:var(--danger)">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</aside>