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

// Handle category deletion
if (isset($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check if category has products
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product_count = $result->fetch_assoc()['count'];
    
    if ($product_count > 0) {
        $error = "Cannot delete category. It has $product_count product(s).";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        if ($stmt->execute()) {
            $message = "Category deleted successfully!";
        } else {
            $error = "Failed to delete category.";
        }
    }
}

// Handle category add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    if (empty($category_name)) {
        $error = "Category name is required";
    } else {
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            // Update
            $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
            $stmt->bind_param("ssi", $category_name, $description, $_POST['category_id']);
            
            if ($stmt->execute()) {
                $message = "Category updated successfully!";
            } else {
                $error = "Failed to update category.";
            }
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $category_name, $description);
            
            if ($stmt->execute()) {
                $message = "Category added successfully!";
            } else {
                $error = "Failed to add category.";
            }
        }
    }
}

// Get all categories with product counts
$stmt = $conn->prepare("
    SELECT 
        c.*,
        COUNT(p.product_id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_category = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <div class="admin-header">
                    <h1>Manage Categories</h1>
                    <button class="btn btn-primary" onclick="openCategoryModal()">Add New Category</button>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Categories Grid -->
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                                <span class="product-badge"><?php echo $category['product_count']; ?> products</span>
                            </div>
                            
                            <p class="category-description">
                                <?php echo htmlspecialchars($category['description'] ?? 'No description'); ?>
                            </p>
                            
                            <div class="category-footer">
                                <span class="category-date">
                                    Created: <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                </span>
                                
                                <div class="category-actions">
                                    <a href="?edit=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                    <?php if ($category['product_count'] == 0): ?>
                                        <a href="?delete=<?php echo $category['category_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Category Modal -->
    <div id="categoryModal" class="modal <?php echo $edit_category ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
                <button class="close-btn" onclick="closeCategoryModal()">&times;</button>
            </div>
            
            <form method="POST">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="category_name">Category Name *</label>
                    <input type="text" id="category_name" name="category_name" 
                           value="<?php echo $edit_category ? htmlspecialchars($edit_category['category_name']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo $edit_category ? htmlspecialchars($edit_category['description'] ?? '') : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeCategoryModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openCategoryModal() {
            document.getElementById('categoryModal').classList.add('show');
        }
        
        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('show');
            window.location.href = '<?php echo BASE_URL; ?>admin/categories.php';
        }
        
        <?php if ($edit_category): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openCategoryModal();
        });
        <?php endif; ?>
    </script>
    
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
</body>
</html>