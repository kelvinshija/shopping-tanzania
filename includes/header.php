<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>index.php">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
            </div>
            
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>">Products</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/index.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">My Account</a></li>
                        <li><a href="<?php echo BASE_URL; ?>cart.php" class="cart-icon <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                            Cart
                            <span class="cart-count">0</span>
                        </a></li>
                        <li><a href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>cart.php" class="cart-icon <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                            Cart
                            <span class="cart-count">0</span>
                        </a></li>
                        <li><a href="<?php echo BASE_URL; ?>login.php" class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>register.php" class="<?php echo $current_page == 'register.php' ? 'active' : ''; ?>">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>