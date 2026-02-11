<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get user's cart
        $stmt = $conn->prepare("
            SELECT c.*, p.product_name, p.price, p.image_url, p.stock_quantity
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'cart' => $cart]);
        break;
        
    case 'POST':
        // Add item to cart
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Check if product exists and has stock
        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $data['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        if ($product['stock_quantity'] < $data['quantity']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit;
        }
        
        // Add to cart or update quantity
        $stmt = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $stmt->bind_param("iiii", $_SESSION['user_id'], $data['product_id'], $data['quantity'], $data['quantity']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product added to cart']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
        }
        break;
        
    case 'PUT':
        // Update cart item quantity
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['cart_id']) || !isset($data['quantity'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Verify cart item belongs to user
        $stmt = $conn->prepare("
            UPDATE cart 
            SET quantity = ? 
            WHERE cart_id = ? AND user_id = ?
        ");
        $stmt->bind_param("iii", $data['quantity'], $data['cart_id'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
        }
        break;
        
    case 'DELETE':
        // Remove item from cart
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing cart item ID']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $_GET['id'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
        }
        break;
}
?>