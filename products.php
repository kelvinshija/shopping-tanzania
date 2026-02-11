<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get categories for filter
$stmt = $conn->prepare("SELECT * FROM categories");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get products with filters
$where = ["1=1"];
$params = [];
$types = "";

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where[] = "category_id = ?";
    $params[] = $_GET['category'];
    $types .= "i";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where[] = "product_name LIKE ?";
    $params[] = "%{$_GET['search']}%";
    $types .= "s";
}

$sql = "SELECT * FROM products WHERE " . implode(" AND ", $where);
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px 0;
            margin-bottom: 40px;
        }
        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo h1 {
            color: #2c3e50;
            font-size: 24px;
        }
        .logo a {
            color: #2c3e50;
            text-decoration: none;
        }
        nav ul {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        nav ul li a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }
        nav ul li a:hover {
            color: #3498db;
        }
        .cart-count {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 12px;
            margin-left: 5px;
        }
        
        /* Products Layout */
        .products-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        /* Filters Sidebar */
        .filters {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .filters h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .filter-group {
            margin-bottom: 20px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #3498db;
            outline: none;
        }
        
        /* Products Grid */
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .products-header h2 {
            color: #2c3e50;
            font-size: 24px;
        }
        .product-count {
            color: #666;
            font-size: 14px;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        
        /* Product Card */
        .product-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .product-card h3 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 10px;
            line-height: 1.4;
            height: 44px;
            overflow: hidden;
        }
        .product-card .price {
            font-size: 20px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 10px;
        }
        .product-card .stock {
            font-size: 13px;
            margin-bottom: 15px;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .product-card .stock:contains('In Stock') {
            background: #d4edda;
            color: #155724;
        }
        .product-card .stock:contains('Out of Stock') {
            background: #f8d7da;
            color: #721c24;
        }
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            flex: 1;
        }
        .btn-primary {
            background: #green;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid #3498db;
            color: #3498db;
        }
        .btn-outline:hover {
            background: #3498db;
            color: white;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* No Products */
        .no-products {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 10px;
            color: #666;
            font-size: 16px;
        }
        
        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }
        .footer-section h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .footer-section ul {
            list-style: none;
        }
        .footer-section ul li {
            margin-bottom: 10px;
        }
        .footer-section ul li a {
            color: #bdc3c7;
            text-decoration: none;
        }
        .footer-section ul li a:hover {
            color: white;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #34495e;
            color: #bdc3c7;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .products-layout {
                grid-template-columns: 1fr;
            }
            .filters {
                position: static;
            }
            header .container {
                flex-direction: column;
                gap: 15px;
            }
            nav ul {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>index.php">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>products.php" class="active">Products</a></li>
                    <li><a href="<?php echo BASE_URL; ?>cart.php">Cart <span class="cart-count">0</span></a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/index.php">Admin</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>profile.php">My Account</a></li>
                        <li><a href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="container">
            <div class="products-layout">
                <!-- Sidebar Filters -->
                <aside class="filters">
                    <h3>Filter Products</h3>
                    <form method="GET" action="products.php">
                        <div class="filter-group">
                            <label for="search">Search Products</label>
                            <input type="text" id="search" name="search" 
                                   placeholder="Search by name..."
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 2;">Apply Filters</button>
                            <a href="products.php" class="btn btn-outline" style="flex: 1;">Clear</a>
                        </div>
                    </form>
                </aside>
                
                <!-- Products Grid -->
                <div class="products-main">
                    <div class="products-header">
                        <h2>Our Products</h2>
                        <span class="product-count"><?php echo count($products); ?> products found</span>
                    </div>
                    
                    <div class="product-grid">
                        <?php if (empty($products)): ?>
                            <div class="no-products">
                                <p style="font-size: 18px; margin-bottom: 20px;">😕 No products found</p>
                                <a href="products.php" class="btn btn-primary">Clear Filters</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <div class="product-card">
                                    <!-- PRODUCT IMAGE - HII NDIO SEHEMU MUHIMU -->
                                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? BASE_URL . 'assets/images/placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                         onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.jpg'; this.onerror=null;">
                                    
                                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    
                                    <div class="price">Tzs<?php echo number_format($product['price'], 2); ?></div>
                                    
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <div class="stock" style="background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 4px; display: inline-block;">
                                            ✓ In Stock (<?php echo $product['stock_quantity']; ?>)
                                        </div>
                                    <?php else: ?>
                                        <div class="stock" style="background: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 4px; display: inline-block;">
                                            ✗ Out of Stock
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-actions">
                                        <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline">View</a>
                                        <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                                class="btn btn-primary" 
                                                <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>Your premier online shopping destination for quality products at great prices in Tanzania (ShoppingTz)</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>products.php">Products</a></li>
                        <li><a href="<?php echo BASE_URL; ?>cart.php">Cart</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul>
                        <li>Email: hashunique8@gmail.com</li>
                        <li>Phone: 255 765 191 323</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    // Simple add to cart function
    function addToCart(productId) {
        alert('Product added to cart! (Product ID: ' + productId + ')');
        // Update cart count
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = parseInt(cartCount.textContent) + 1;
        }
    }
    </script>
</body>
</html>