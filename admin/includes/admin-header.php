<?php
// Make sure BASE_URL is available
if (!defined('BASE_URL')) {
    require_once '../../includes/config.php';
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
?>
<header class="admin-header">
    <div class="admin-header-container">
        <div class="admin-logo">
            <a href="<?php echo BASE_URL; ?>admin/index.php">
                <h2><?php echo SITE_NAME; ?> Admin</h2>
            </a>
        </div>
        
        <div class="admin-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
            <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </div>
</header>