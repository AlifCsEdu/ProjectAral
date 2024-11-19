<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cart_id = $_POST['cart_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;

if (!$cart_id || !$quantity) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify cart item belongs to user
    $check_query = "SELECT 1 FROM cart WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$cart_id, $_SESSION['user_id']]);
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }

    if ($quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Quantity cannot be less than 1']);
        exit;
    }

    // Update quantity
    $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    
    if ($update_stmt->execute([$quantity, $cart_id, $_SESSION['user_id']])) {
        // Get updated cart total
        $total_query = "SELECT SUM(c.quantity * f.price) as total 
                       FROM cart c 
                       JOIN food_items f ON c.food_item_id = f.id 
                       WHERE c.user_id = ?";
        $total_stmt = $conn->prepare($total_query);
        $total_stmt->execute([$_SESSION['user_id']]);
        $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cart updated successfully',
            'total' => $total
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
