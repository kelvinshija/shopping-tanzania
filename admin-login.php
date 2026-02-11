<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';

// If already logged in as admin, redirect to admin panel
if ($auth->isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        // Check if user is admin
        if ($_SESSION['user_role'] == 'admin') {
            header('Location: ' . BASE_URL . 'admin/index.php');
            exit;
        } else {
            $error = 'This account does not have admin privileges';
            // Logout the non-admin user
            $auth->logout();
        }
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .admin-login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .admin-login-box h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        
        .admin-login-box .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .admin-login-box .form-group {
            margin-bottom: 20px;
        }
        
        .admin-login-box label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .admin-login-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .admin-login-box input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .admin-login-box button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .admin-login-box button:hover {
            transform: translateY(-2px);
        }
        
        .admin-login-box .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .admin-login-box .admin-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 13px;
            color: #666;
        }
        
        .admin-login-box .admin-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
            font-size: 16px;
        }
        
        .admin-login-box .admin-info p {
            margin: 5px 0;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-site a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <h1>Admin Login</h1>
            <p class="subtitle"><?php echo SITE_NAME; ?> Administrator Panel</p>
            
            <?php if ($error): ?>
                <div class="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit">Login to Admin Panel</button>
            </form>
            
            <div class="admin-info">
                <h3>Default Admin Credentials:</h3>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
                <p style="color: #856404; margin-top: 10px;">⚠️ Please change these credentials after first login!</p>
            </div>
            
            <div class="back-to-site">
                <a href="<?php echo BASE_URL; ?>index.php">← Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>