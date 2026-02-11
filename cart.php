<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=cart.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get user's cart
$stmt = $conn->prepare("
    SELECT c.*, p.product_name, p.price, p.image_url, p.stock_quantity
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1>Shopping Cart</h1>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <p>Your cart is empty.</p>
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <div class="cart-item-details">
                                    <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                                    <div class="quantity-control">
                                        <button onclick="handleUpdateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>)" 
                                                <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                                        <span><?php echo $item['quantity']; ?></span>
                                        <button onclick="handleUpdateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                                <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>+</button>
                                    </div>
                                </div>
                                <div class="cart-item-total">
                                    <p>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    <button onclick="handleRemoveFromCart(<?php echo $item['cart_id']; ?>)" class="btn btn-danger">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <h3>Order Summary</h3>
                        <div class="summary-line">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="summary-line">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        <div class="summary-line total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
                        <a href="products.php" class="btn btn-outline btn-block">Continue Shopping</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/app.js"></script>
</body>
</html>