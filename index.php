<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth = new Auth();
$db = Database::getInstance();
$conn = $db->getConnection();

// Get featured products
$stmt = $conn->prepare("SELECT * FROM products ORDER BY RAND() LIMIT 8");
$stmt->execute();
$featured_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories
$stmt = $conn->prepare("SELECT * FROM categories LIMIT 6");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <h1>Welcome to <?php echo SITE_NAME; ?></h1>
                <p>Discover amazing products at great prices</p>
                <a href="products.php" class="btn btn-primary">Shop Now</a>
            </div>
        </section>
        
        <!-- Categories Section -->
        <section class="categories">
            <div class="container">
                <h2>Shop by Category</h2>
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                            <p><?php echo htmlspecialchars($category['description']); ?></p>
                            <a href="products.php?category=<?php echo $category['category_id']; ?>" class="btn btn-outline">View Products</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Featured Products -->
        <section class="featured-products">
            <div class="container">
                <h2>Featured Products</h2>
                <div id="product-grid" class="product-grid">
                    <?php foreach ($featured_products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? '/assets/images/placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="price">Tzs<?php echo number_format($product['price'], 2); ?></p>
                            <p class="stock"><?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?></p>
                            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/app.js"></script>
</body>
</html>