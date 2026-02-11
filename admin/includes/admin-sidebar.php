<?php
// Make sure BASE_URL is available
if (!defined('BASE_URL')) {
    require_once '../../includes/config.php';
}
?>
<aside class="admin-sidebar">
    <nav class="admin-nav">
        <ul>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/index.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/products.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📦</span>
                    Products
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/categories.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏷️</span>
                    Categories
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/orders.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🛒</span>
                    Orders
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/users.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span>
                    Users
                </a>
            </li>
        </ul>
    </nav>
</aside>