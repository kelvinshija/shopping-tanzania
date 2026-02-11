<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . 'admin-login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$message = '';
$error = '';

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_count = $result->fetch_assoc()['count'];
    
    if ($order_count > 0) {
        $error = "Cannot delete product. It exists in $order_count order(s).";
    } else {
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $message = "Product deleted successfully!";
        } else {
            $error = "Failed to delete product.";
        }
    }
}

// Handle product add/edit - NOW WITH IMAGE URL SUPPORT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    
    // IMPORTANT: Get image URL from form input
    $image_url = trim($_POST['image_url']);
    
    // If no image URL provided, use placeholder
    if (empty($image_url)) {
        $image_url = BASE_URL . 'assets/images/placeholder.jpg';
    }
    
    // Validate
    if (empty($product_name)) {
        $error = "Product name is required";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0";
    } elseif ($stock_quantity < 0) {
        $error = "Stock quantity cannot be negative";
    } else {
        if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
            // Update existing product
            $stmt = $conn->prepare("
                UPDATE products 
                SET product_name = ?, category_id = ?, description = ?, 
                    price = ?, stock_quantity = ?, image_url = ?
                WHERE product_id = ?
            ");
            $stmt->bind_param("sisdisi", $product_name, $category_id, $description, 
                            $price, $stock_quantity, $image_url, $_POST['product_id']);
        } else {
            // Insert new product
            $stmt = $conn->prepare("
                INSERT INTO products (product_name, category_id, description, price, stock_quantity, image_url) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sisdis", $product_name, $category_id, $description, 
                            $price, $stock_quantity, $image_url);
        }
        
        if ($stmt->execute()) {
            $message = isset($_POST['product_id']) ? "Product updated successfully!" : "Product added successfully!";
        } else {
            $error = "Failed to save product.";
        }
    }
}

// Get all products
$stmt = $conn->prepare("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY category_name");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 260px;
            background: #2c3e50;
            color: white;
            padding: 30px 0;
        }
        .admin-sidebar h2 {
            padding: 0 20px;
            margin-bottom: 30px;
            font-size: 20px;
        }
        .admin-sidebar ul {
            list-style: none;
        }
        .admin-sidebar li {
            margin-bottom: 5px;
        }
        .admin-sidebar a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: #34495e;
            border-left: 4px solid #3498db;
        }
        .admin-main {
            flex: 1;
            padding: 30px;
        }
        .admin-content {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 { color: #2c3e50; margin: 0; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover { background: #2980b9; }
        .btn-outline {
            background: transparent;
            border: 1px solid #3498db;
            color: #3498db;
        }
        .btn-outline:hover {
            background: #3498db;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        tr:hover { background: #f8f9fa; }
        .product-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-instock {
            background: #d4edda;
            color: #155724;
        }
        .status-lowstock {
            background: #fff3cd;
            color: #856404;
        }
        .status-outofstock {
            background: #f8d7da;
            color: #721c24;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 { margin: 0; font-size: 20px; }
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .form-group {
            padding: 0 20px;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 0 20px;
        }
        .form-actions {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .image-preview {
            margin: 0 20px 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 150px;
            object-fit: contain;
            border-radius: 5px;
        }
        .preview-placeholder {
            color: #666;
            font-style: italic;
        }
        .help-text {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        .text-center { text-align: center; }
        .mt-3 { margin-top: 15px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <h2><?php echo SITE_NAME; ?> Admin</h2>
            <ul>
                <li><a href="index.php">📊 Dashboard</a></li>
                <li><a href="products.php" class="active">📦 Products</a></li>
                <li><a href="categories.php">🏷️ Categories</a></li>
                <li><a href="orders.php">🛒 Orders</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="<?php echo BASE_URL; ?>logout.php" style="border-top: 1px solid #34495e; margin-top: 20px;">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-content">
                <div class="admin-header">
                    <h1>Manage Products</h1>
                    <button class="btn btn-primary" onclick="openModal()">+ Add New Product</button>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Products Table -->
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>#<?php echo $product['product_id']; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($product['image_url'] ?? BASE_URL . 'assets/images/placeholder.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                             class="product-thumbnail"
                                             onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.jpg'">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <?php if ($product['stock_quantity'] > 10): ?>
                                            <span class="status-badge status-instock">In Stock</span>
                                        <?php elseif ($product['stock_quantity'] > 0): ?>
                                            <span class="status-badge status-lowstock">Low Stock</span>
                                        <?php else: ?>
                                            <span class="status-badge status-outofstock">Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="?edit=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                        <a href="?delete=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this product?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Product Modal -->
    <div id="productModal" class="modal <?php echo $edit_product ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['product_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"
                                <?php echo ($edit_product && $edit_product['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" 
                               value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                               value="<?php echo $edit_product ? $edit_product['stock_quantity'] : ''; ?>" required>
                    </div>
                </div>
                
                <!-- IMAGE URL INPUT - NEW SECTION -->
                <div class="form-group">
                    <label for="image_url">Product Image URL</label>
                    <input type="url" id="image_url" name="image_url" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['image_url'] ?? '') : ''; ?>" 
                           placeholder="https://example.com/images/product.jpg"
                           onchange="previewImage()">
                    <div class="help-text">
                        📸 Paste direct link to image (JPG, PNG, GIF). Leave empty for placeholder.
                    </div>
                    <div class="help-text">
                        🔗 Examples: 
                        <a href="#" onclick="document.getElementById('image_url').value='https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300'; previewImage(); return false;">Sample Image 1</a> | 
                        <a href="#" onclick="document.getElementById('image_url').value='https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300'; previewImage(); return false;">Sample Image 2</a>
                    </div>
                </div>
                
                <!-- Image Preview -->
                <div class="image-preview" id="imagePreview">
                    <?php if ($edit_product && !empty($edit_product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($edit_product['image_url']); ?>" 
                             alt="Product preview" 
                             onerror="this.parentNode.innerHTML='<span class=\'preview-placeholder\'>❌ Image failed to load. Check URL.</span>'">
                    <?php else: ?>
                        <span class="preview-placeholder">🔍 Image preview will appear here</span>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('productModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
            window.location.href = 'products.php';
        }
        
        function previewImage() {
            const url = document.getElementById('image_url').value;
            const preview = document.getElementById('imagePreview');
            
            if (url) {
                preview.innerHTML = `<img src="${url}" alt="Preview" style="max-width: 100%; max-height: 150px; object-fit: contain; border-radius: 5px;" onerror="this.parentNode.innerHTML='<span class=\'preview-placeholder\'>❌ Image failed to load. Check URL.</span>'">`;
            } else {
                preview.innerHTML = '<span class="preview-placeholder">🔍 Image preview will appear here</span>';
            }
        }
        
        <?php if ($edit_product): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal();
        });
        <?php endif; ?>
    </script>
</body>
</html>