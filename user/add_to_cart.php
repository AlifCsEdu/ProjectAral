<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Check if required parameters are present
if (!isset($_POST['food_item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$food_item_id = $_POST['food_item_id'];
$quantity = (int)$_POST['quantity'];

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if item exists and is available
    $check_query = "SELECT id, is_available FROM food_items WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$food_item_id]);
    $food_item = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$food_item) {
        echo json_encode(['success' => false, 'message' => 'Food item not found']);
        exit;
    }

    if (!$food_item['is_available']) {
        echo json_encode(['success' => false, 'message' => 'Food item is not available']);
        exit;
    }

    // Check if item already exists in cart
    $cart_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND food_item_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->execute([$user_id, $food_item_id]);
    $cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // Update existing cart item
        $new_quantity = $cart_item['quantity'] + $quantity;
        $update_query = "UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$new_quantity, $cart_item['id']]);
    } else {
        // Add new cart item
        $insert_query = "INSERT INTO cart (user_id, food_item_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->execute([$user_id, $food_item_id, $quantity]);
    }

    // Get updated cart count
    $count_query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute([$user_id]);
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_items = (int)($count_result['count'] ?? 0);

    echo json_encode([
        'success' => true, 
        'message' => 'Item added to cart successfully',
        'cart_count' => $total_items
    ]);

} catch (PDOException $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
