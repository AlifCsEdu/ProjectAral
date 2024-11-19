<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

// Set content type header
header('Content-Type: application/json');

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Get cart items for the current user
    $query = "SELECT c.id as cart_id, c.quantity, f.* 
              FROM cart c 
              JOIN food_items f ON c.food_item_id = f.id 
              WHERE c.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total
    $total = 0;
    foreach ($cartItems as $item) {
        $total += floatval($item['price']) * intval($item['quantity']);
    }

    // Format items for response
    $formattedItems = [];
    foreach ($cartItems as $item) {
        $formattedItems[] = [
            'id' => $item['food_item_id'],
            'cart_id' => $item['cart_id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'quantity' => intval($item['quantity']),
            'image_path' => $item['image_path'] ? $item['image_path'] : 'assets/images/default-food.jpg'
        ];
    }

    $response = [
        'success' => true,
        'items' => $formattedItems,
        'total' => floatval($total),
        'count' => count($cartItems)
    ];

    // Debug: Log the response
    error_log("Cart Preview Response: " . json_encode($response));
    
    // Ensure no output before this point
    if (ob_get_length()) ob_clean();
    
    echo json_encode($response);
    exit;
    
} catch (Exception $e) {
    error_log("Cart preview error: " . $e->getMessage());
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Error loading cart'
    ]);
    exit;
}
