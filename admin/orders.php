<?php
// Make sure BASE_URL is defined
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$message = '';
$error = '';

// Update order status
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        $message = "Order status updated successfully!";
    } else {
        $error = "Failed to update order status.";
    }
}

// Filter orders by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get orders with filters
$sql = "
    SELECT o.*, u.username, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE DATE(o.order_date) BETWEEN ? AND ?
";

$params = [$date_from, $date_to];
$types = "ss";

if ($status_filter != 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get order statistics
$stats = [];

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        COALESCE(SUM(total_amount), 0) as total_revenue
    FROM orders 
    WHERE DATE(order_date) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <h1>Manage Orders</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Order Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['total_orders']; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['pending']; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🚚</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['shipped']; ?></h3>
                            <p>Shipped</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-details">
                            <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <p>Revenue</p>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_from">From</label>
                                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">To</label>
                                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Orders</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="<?php echo BASE_URL; ?>admin/orders.php" class="btn btn-outline">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Orders Table -->
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($order['email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="status-select status-<?php echo $order['status']; ?>">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <?php
                                    $stmt = $conn->prepare("
                                        SELECT COUNT(*) as count, SUM(quantity) as total_qty 
                                        FROM order_items WHERE order_id = ?
                                    ");
                                    $stmt->bind_param("i", $order['order_id']);
                                    $stmt->execute();
                                    $item_stats = $stmt->get_result()->fetch_assoc();
                                    echo $item_stats['count'] . ' items (' . $item_stats['total_qty'] . ' qty)';
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="<?php echo BASE_URL; ?>admin/order-detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
</body>
</html>