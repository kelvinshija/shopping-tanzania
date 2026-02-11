<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get user details
$user = $auth->getUserById($_SESSION['user_id']);

// Get user's orders
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY order_date DESC 
    LIMIT 10
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="profile-container">
        <h1>My Profile</h1>
        
        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <span><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="text-muted">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="profile-main">
                <div class="profile-section">
                    <h2>Account Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Phone:</label>
                            <span><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Address:</label>
                            <span><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-section">
                    <h2>Recent Orders</h2>
                    <?php if (empty($orders)): ?>
                        <p>No orders yet.</p>
                        <a href="products.php" class="btn btn-primary">Start Shopping</a>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>