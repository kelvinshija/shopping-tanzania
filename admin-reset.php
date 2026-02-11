<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth = new Auth();
$message = '';
$error = '';

// Only allow if no admin exists or for emergency reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_admin'])) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Delete existing admin
    $conn->query("DELETE FROM users WHERE username = 'admin'");
    
    // Create new admin
    $username = 'admin';
    $email = 'admin@ecommerce.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $full_name = 'Administrator';
    $role = 'admin';
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $password, $full_name, $role);
    
    if ($stmt->execute()) {
        $message = "Admin account reset successfully! Username: admin, Password: admin123";
    } else {
        $error = "Failed to reset admin account.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .reset-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeeba;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .btn-reset {
            background-color: #dc3545;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .btn-reset:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h1>Reset Admin Account</h1>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> This will delete the existing admin account and create a new one with default credentials. Use this only if you've lost access to your admin account.
        </div>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
            <p><a href="admin-login.php" class="btn" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Go to Admin Login</a></p>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
            <form method="POST" onsubmit="return confirm('Are you sure you want to reset the admin account? This action cannot be undone.');">
                <button type="submit" name="reset_admin" class="btn-reset">Reset Admin Account</button>
            </form>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="admin-login.php">Back to Admin Login</a>
        </p>
    </div>
</body>
</html>