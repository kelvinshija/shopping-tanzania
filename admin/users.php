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

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Don't allow deleting own account
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        // Check if user has orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_count = $result->fetch_assoc()['count'];
        
        if ($order_count > 0) {
            $error = "Cannot delete user. They have $order_count order(s).";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = "User deleted successfully!";
            } else {
                $error = "Failed to delete user.";
            }
        }
    }
}

// Handle user role update
if (isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot change your own role!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        if ($stmt->execute()) {
            $message = "User role updated successfully!";
        } else {
            $error = "Failed to update user role.";
        }
    }
}

// Get all users
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT o.order_id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <h1>Manage Users</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- User Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-details">
                            <h3><?php echo count($users); ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-details">
                            <h3><?php 
                                $customers = array_filter($users, function($u) { return $u['role'] == 'customer'; });
                                echo count($customers);
                            ?></h3>
                            <p>Customers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👑</div>
                        <div class="stat-details">
                            <h3><?php 
                                $admins = array_filter($users, function($u) { return $u['role'] == 'admin'; });
                                echo count($admins);
                            ?></h3>
                            <p>Admins</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-details">
                            <h3><?php 
                                $new_users = array_filter($users, function($u) { 
                                    return strtotime($u['created_at']) > strtotime('-30 days'); 
                                });
                                echo count($new_users);
                            ?></h3>
                            <p>New (30 days)</p>
                        </div>
                    </div>
                </div>
                
                <!-- Users Table -->
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['user_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <select name="role" onchange="this.form.submit()" <?php echo $user['user_id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                            <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                </td>
                                <td><?php echo $user['total_orders']; ?></td>
                                <td>$<?php echo number_format($user['total_spent'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="<?php echo BASE_URL; ?>admin/user-detail.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
</body>
</html>