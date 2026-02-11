<?php
// Make sure BASE_URL is defined
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

$auth = new Auth();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php?redirect=admin/index.php');
    exit;
}

if (!$auth->isAdmin()) {
    // If logged in but not admin, show error and logout
    session_destroy();
    header('Location: ' . BASE_URL . 'admin-login.php?error=not_admin');
    exit;
}

// Rest of your code...

$db = Database::getInstance();
$conn = $db->getConnection();

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$stats['total_products'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Recent orders
$stmt = $conn->prepare("
    SELECT o.*, u.username, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.order_date DESC 
    LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Low stock products
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE stock_quantity < 10 
    ORDER BY stock_quantity ASC 
    LIMIT 5
");
$stmt->execute();
$low_stock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Monthly sales (last 6 months)
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute();
$monthly_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <h1>Dashboard</h1>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['total_products']; ?></h3>
                            <p>Total Products</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['total_orders']; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-details">
                            <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3>Monthly Sales (Last 6 Months)</h3>
                        <canvas id="salesChart"></canvas>
                    </div>
                    
                    <div class="chart-card">
                        <h3>Low Stock Alert</h3>
                        <div class="alert-list">
                            <?php foreach ($low_stock as $product): ?>
                                <div class="alert-item <?php echo $product['stock_quantity'] == 0 ? 'critical' : 'warning'; ?>">
                                    <span class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></span>
                                    <span class="stock-count">Stock: <?php echo $product['stock_quantity']; ?></span>
                                    <a href="<?php echo BASE_URL; ?>admin/products.php?edit=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline">Update</a>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($low_stock)): ?>
                                <p class="no-alerts">No low stock products</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="recent-orders">
                    <h3>Recent Orders</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>admin/orders.php?view=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const monthlyData = <?php echo json_encode(array_reverse($monthly_sales)); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    label: 'Revenue',
                    data: monthlyData.map(d => d.revenue),
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
    
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
</body>
</html>