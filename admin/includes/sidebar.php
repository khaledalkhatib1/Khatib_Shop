<div class="admin-sidebar">
    <div class="sidebar-header">
        <h2>ElectroShop</h2>
        <span>Admin Panel</span>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'add-product.php' || basename($_SERVER['PHP_SELF']) == 'edit-product.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' || basename($_SERVER['PHP_SELF']) == 'add-category.php' || basename($_SERVER['PHP_SELF']) == 'edit-category.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' || basename($_SERVER['PHP_SELF']) == 'order-details.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'edit-user.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li>
                <a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' || basename($_SERVER['PHP_SELF']) == 'view-message.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Messages
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../index.php" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Site
        </a>
        <a href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
