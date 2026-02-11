<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$conn = $db->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single product
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            echo json_encode($product);
        } else {
            // Get all products with filters
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
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($products);
        }
        break;
}
?>